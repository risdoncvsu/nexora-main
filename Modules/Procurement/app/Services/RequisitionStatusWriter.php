<?php

namespace Modules\Procurement\Services;

use Illuminate\Support\Facades\DB;
use Modules\Procurement\Support\SchemaProbe;

/**
 * Writes requisition status back to the external requisition sources
 * (Inventory, Order Fulfillment). Procurement owns these transitions:
 *
 *   Pending    -> Rejected              (Procurement declines it)
 *   Pending    -> Processing            (a PO was created for it)
 *   Processing -> In Transit            (its shipment left the supplier)
 *   In Transit -> Delivered -> Completed
 *   Rejected / Completed are terminal.
 *
 * There is no Approve step: raising the purchase order IS the approval, so
 * Inventory and Order Fulfillment requests now follow the same path. Approved
 * is still accepted as an inbound value so requisitions already sitting in
 * that state can move on instead of getting stuck.
 *
 * Casing matches the column default ('Pending'), i.e. title case.
 *
 * A requisition may span several rows sharing one req_id; every transition
 * moves the whole group together.
 *
 * Every lookup and every write is scoped to the signed-in client. Without that
 * scope this class would happily move another tenant's requisition group — the
 * reference and the numeric id are both guessable.
 */
class RequisitionStatusWriter
{
    public const PENDING = 'Pending';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';
    public const PROCESSING = 'Processing';
    public const IN_TRANSIT = 'In Transit';
    public const DELIVERED = 'Delivered';
    public const COMPLETED = 'Completed';

    /** Requisition sources, in lookup order. */
    private const CONNECTIONS = ['order_fulfillment', 'inventory'];

    /** Allowed target statuses, keyed by the normalised current status. */
    private const TRANSITIONS = [
        // Creating the purchase order is what moves a request forward, so
        // Pending goes straight to Processing.
        'pending' => [self::REJECTED, self::PROCESSING, self::IN_TRANSIT, self::DELIVERED, self::COMPLETED],
        // Delivery records may be imported after a shipment has already
        // reached its destination, so a status may legitimately skip ahead
        // during reconciliation.
        'approved' => [self::PROCESSING, self::IN_TRANSIT, self::DELIVERED, self::COMPLETED],
        'processing' => [self::IN_TRANSIT, self::DELIVERED, self::COMPLETED],
        'intransit' => [self::DELIVERED, self::COMPLETED],
        'delivered' => [self::COMPLETED],
        'rejected' => [],
        'completed' => [],
    ];

    /** Every status Procurement is allowed to ask for. */
    public static function isKnownStatus(string $status): bool
    {
        return in_array($status, self::knownStatuses(), true);
    }

    /** @return list<string> */
    public static function knownStatuses(): array
    {
        return [
            self::PENDING, self::APPROVED, self::REJECTED, self::PROCESSING,
            self::IN_TRANSIT, self::DELIVERED, self::COMPLETED,
        ];
    }

    /**
     * Normalise arbitrary input to the canonical label.
     *
     * The old implementation was ucfirst(strtolower($status)), which turned
     * "intransit" into "Intransit" and "In Transit" into "In transit" —
     * neither is a known status, so every in-transit write from the delivery
     * flow was rejected with a 422 that nothing surfaced. Matching on a
     * collapsed key fixes all three spellings.
     */
    public static function normalise(string $status): string
    {
        $key = self::statusKey($status);

        foreach (self::knownStatuses() as $known) {
            if (self::statusKey($known) === $key) {
                return $known;
            }
        }

        return ucfirst(strtolower(trim($status)));
    }

    /** Collapse "In Transit" / "in transit" / "intransit" / "in-transit" to one key. */
    private static function statusKey(?string $status): string
    {
        return preg_replace('/[^a-z]/', '', strtolower(trim((string) $status))) ?? '';
    }

    private static function clientId(): int
    {
        return (int) session('employee_client_id');
    }

    private static function isRootTesting(): bool
    {
        return (bool) config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
    }

    /**
     * Apply the tenant filter when the source table carries a client_id.
     * Root-admin module testing is the single documented bypass.
     */
    private static function scopeToClient($query, string $connection): void
    {
        if (self::isRootTesting()) {
            return;
        }

        if (SchemaProbe::hasColumn($connection, 'requisitions', 'client_id')) {
            $query->where('requisitions.client_id', self::clientId());
        }
    }

    /**
     * Locate a requisition by primary key or by its requisition number, across
     * the configured sources — always within the signed-in client.
     *
     * @return array{connection:\Illuminate\Database\Connection,source:string,refColumn:?string,row:object,hasStatus:bool}|null
     */
    private function locate($id = null, ?string $reference = null): ?array
    {
        foreach (self::CONNECTIONS as $source) {
            try {
                if (! SchemaProbe::hasTable($source, 'requisitions')) {
                    continue;
                }

                $connection = DB::connection($source);

                // Inventory uses req_id; Order Fulfillment uses req_number.
                $refColumn = SchemaProbe::hasColumn($source, 'requisitions', 'req_id')
                    ? 'req_id'
                    : (SchemaProbe::hasColumn($source, 'requisitions', 'req_number') ? 'req_number' : null);

                $query = $connection->table('requisitions');
                if ($id !== null) {
                    $query->where('id', $id);
                } elseif ($refColumn !== null && $reference !== null) {
                    $query->where($refColumn, $reference);
                } else {
                    continue;
                }

                self::scopeToClient($query, $source);

                $row = $query->first();
                if (! $row) {
                    continue;
                }

                return [
                    'connection' => $connection,
                    'source' => $source,
                    'refColumn' => $refColumn,
                    'row' => $row,
                    'hasStatus' => SchemaProbe::hasColumn($source, 'requisitions', 'status'),
                ];
            } catch (\Throwable $e) {
                // Skip broken or unavailable external connections.
            }
        }

        return null;
    }

    /**
     * Current stored status, or null when the source has no status column
     * (Order Fulfillment) or the requisition can't be found.
     */
    public function statusOfReference(string $reference): ?string
    {
        $found = $this->locate(null, $reference);

        if (! $found || ! $found['hasStatus']) {
            return null;
        }

        return trim((string) ($found['row']->status ?? self::PENDING));
    }

    public function transitionById($id, string $target): array
    {
        return $this->apply($this->locate($id, null), $target);
    }

    public function transitionByReference(string $reference, string $target): array
    {
        return $this->apply($this->locate(null, $reference), $target);
    }

    /**
     * @return array{ok:bool,status?:string,updated?:int,code?:int,message?:string}
     */
    private function apply(?array $found, string $target): array
    {
        if (! $found) {
            return ['ok' => false, 'code' => 404, 'message' => 'Requisition not found.'];
        }

        $target = self::normalise($target);

        if (! self::isKnownStatus($target)) {
            return ['ok' => false, 'code' => 422, 'message' => sprintf('Unknown requisition status "%s".', $target)];
        }

        if (! $found['hasStatus']) {
            // Order Fulfillment has no status column — there is nothing to
            // persist and no lifecycle to enforce. Treat it as a benign no-op
            // success (not a 409) so the caller's optimistic UI update stands
            // instead of surfacing an error. `persisted:false` signals that the
            // change won't survive a reload for this source.
            return ['ok' => true, 'status' => $target, 'updated' => 0, 'persisted' => false];
        }

        $current = trim((string) ($found['row']->status ?? self::PENDING));

        // Idempotent: asking for the status it already has is a no-op success.
        if (self::statusKey($current) === self::statusKey($target)) {
            return ['ok' => true, 'status' => $target, 'updated' => 0, 'persisted' => true];
        }

        $allowed = self::TRANSITIONS[self::statusKey($current)] ?? [];
        if (! in_array($target, $allowed, true)) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => sprintf('Cannot change requisition from "%s" to "%s".', $current, $target),
            ];
        }

        $connection = $found['connection'];
        $source = $found['source'];
        $refColumn = $found['refColumn'];
        $reference = $refColumn !== null ? ($found['row']->{$refColumn} ?? null) : null;

        $query = $connection->table('requisitions');

        // Move every row in the req_id group together; fall back to the single
        // row when there's no reference column.
        if ($refColumn !== null && $reference !== null && $reference !== '') {
            $query->where($refColumn, $reference);
        } else {
            $query->where('id', $found['row']->id);
        }

        // The group update is tenant-scoped too: one req_id must never fan out
        // across clients.
        self::scopeToClient($query, $source);

        // Deliberately a single UPDATE rather than an explicit
        // beginTransaction/commit block. One UPDATE is already atomic in
        // PostgreSQL — the whole req_id group moves all-or-nothing — and these
        // requisition databases sit behind a pooled (PgBouncer/Neon) endpoint
        // where a multi-statement transaction can have its BEGIN and COMMIT
        // land on different backends, silently discarding the write.
        $updated = $query->update([
            'status' => $target,
            'updated_at' => now(),
        ]);

        return ['ok' => true, 'status' => $target, 'updated' => $updated, 'persisted' => true];
    }
}
