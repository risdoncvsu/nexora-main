<?php

namespace Modules\Procurement\Support;

use Illuminate\Support\Facades\DB;

/**
 * One definition of "how many requisitions are Pending / Completed".
 *
 * This has to derive a requisition's status the same way the Requisitions page
 * does, not just read the source table's status column. Order Fulfillment's
 * requisitions table has NO status column at all — every status those rows
 * display is derived from their linked purchase order. Counting the column
 * therefore reported zero Completed while the page plainly showed three.
 *
 * The rules below mirror RequisitionController@index exactly:
 *
 *   - Inventory owns a real status column. Procurement writes back to it, so
 *     the stored value wins, except that a delivered/completed PO advances a
 *     still-open requisition to Delivered/Completed.
 *   - Order Fulfillment has no status column. Those rows start as Pending and
 *     take whatever their PO implies.
 *
 * A requisition is Pending when nothing has happened to it yet — which, for a
 * source with no status column, means it has no purchase order.
 */
final class RequisitionTally
{
    /** Requisition sources, in lookup order. */
    private const CONNECTIONS = ['order_fulfillment', 'inventory'];

    /** Purchase order status -> the requisition status it implies. */
    private const DERIVED_FROM_PO = [
        'pending' => 'Processing',
        'approved' => 'Processing',
        'processing' => 'In Transit',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
    ];

    /** Requisition statuses a purchase order is still allowed to move. */
    private const ADVANCEABLE = ['pending', 'approved', 'processing', 'in transit', 'intransit', 'delivered', ''];

    /**
     * @return array{total:int, pending:int, completed:int, byStatus:array<string,int>}
     */
    public static function counts(int $clientId, bool $rootTesting = false): array
    {
        $empty = ['total' => 0, 'pending' => 0, 'completed' => 0, 'byStatus' => []];

        if ($clientId <= 0 && ! $rootTesting) {
            return $empty;
        }

        $poStatusByReference = self::purchaseOrderStatusByReference($clientId, $rootTesting);

        $byStatus = [];
        $total = 0;

        foreach (self::CONNECTIONS as $connectionName) {
            try {
                if (! SchemaProbe::hasTable($connectionName, 'requisitions')) {
                    continue;
                }

                $connection = DB::connection($connectionName);
                $hasStatus = SchemaProbe::hasColumn($connectionName, 'requisitions', 'status');
                $referenceColumn = SchemaProbe::hasColumn($connectionName, 'requisitions', 'req_id')
                    ? 'req_id'
                    : (SchemaProbe::hasColumn($connectionName, 'requisitions', 'req_number') ? 'req_number' : null);

                $query = $connection->table('requisitions')
                    ->select(array_values(array_filter([
                        'id',
                        $referenceColumn,
                        $hasStatus ? 'status' : null,
                    ])));

                if (! $rootTesting && SchemaProbe::hasColumn($connectionName, 'requisitions', 'client_id')) {
                    $query->where('client_id', $clientId);
                }

                foreach ($query->get() as $row) {
                    $total++;

                    $reference = $referenceColumn
                        ? strtoupper(trim((string) ($row->{$referenceColumn} ?? '')))
                        : '';
                    $poStatus = $reference !== '' ? ($poStatusByReference[$reference] ?? null) : null;

                    $effective = self::effectiveStatus(
                        $hasStatus ? (string) ($row->status ?? 'Pending') : 'Pending',
                        $hasStatus,
                        $connectionName,
                        $poStatus
                    );

                    $key = self::statusKey($effective);
                    $byStatus[$key] = ($byStatus[$key] ?? 0) + 1;
                }
            } catch (\Throwable $e) {
                // Skip unavailable external sources rather than breaking the page.
            }
        }

        return [
            'total' => $total,
            'pending' => $byStatus['pending'] ?? 0,
            'completed' => $byStatus['completed'] ?? 0,
            'byStatus' => $byStatus,
        ];
    }

    public static function pending(int $clientId, bool $rootTesting = false): int
    {
        return self::counts($clientId, $rootTesting)['pending'];
    }

    /**
     * The status a requisition actually displays, given its stored value and
     * the state of the purchase order raised against it.
     */
    private static function effectiveStatus(string $stored, bool $hasStatus, string $source, ?string $poStatus): string
    {
        $current = strtolower(trim($stored)) ?: 'pending';

        if ($poStatus === null || ! isset(self::DERIVED_FROM_PO[$poStatus])) {
            return $stored !== '' ? $stored : 'Pending';
        }

        if (! in_array($current, self::ADVANCEABLE, true)) {
            // Rejected / Cancelled requisitions are terminal — a purchase order
            // must not drag them back into the active pipeline.
            return $stored;
        }

        // Inventory stores its own status, so only a delivered/completed PO
        // (i.e. work that has actually finished) overrides it.
        if ($hasStatus && $source === 'inventory') {
            return in_array($poStatus, ['delivered', 'completed'], true)
                ? self::DERIVED_FROM_PO[$poStatus]
                : $stored;
        }

        // No status column: the purchase order is the only signal there is.
        return self::DERIVED_FROM_PO[$poStatus];
    }

    /**
     * Uppercased requisition reference -> lowercased purchase order status.
     *
     * @return array<string, string>
     */
    private static function purchaseOrderStatusByReference(int $clientId, bool $rootTesting): array
    {
        try {
            if (! SchemaProbe::hasColumn('procurement', 'purchase_orders', 'requisition_reference')) {
                return [];
            }

            $query = DB::connection('procurement')->table('purchase_orders')
                ->whereNotNull('requisition_reference');

            if (! $rootTesting && SchemaProbe::hasColumn('procurement', 'purchase_orders', 'client_id')) {
                $query->where('client_id', $clientId);
            }

            $map = [];
            foreach ($query->get(['requisition_reference', 'status']) as $po) {
                $reference = strtoupper(trim((string) $po->requisition_reference));
                if ($reference === '') {
                    continue;
                }
                $map[$reference] = strtolower(trim((string) $po->status));
            }

            return $map;
        } catch (\Throwable $e) {
            // Keep the badge usable if the Procurement database is unavailable.
            return [];
        }
    }

    /** Collapse "In Transit" / "in-transit" / "intransit" to one key. */
    private static function statusKey(?string $status): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(trim((string) $status))) ?: 'pending';
    }
}
