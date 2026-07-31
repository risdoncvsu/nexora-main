<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Defect;
use Modules\Procurement\Services\RequisitionStatusWriter;
use Modules\Procurement\Support\SchemaProbe;

class RequisitionController extends Controller
{
    private function isRootTesting(?Request $request = null): bool
    {
        $user = $request?->user() ?? auth()->user();

        return (bool) config('nexora.root_admin_module_testing') && $user?->role === 'root_admin';
    }

    private function clientId(): int
    {
        return (int) session('employee_client_id');
    }

    /**
     * Apply the tenant filter to an external requisitions query.
     *
     * update()/destroy() previously ran a bare `where('id', $requisition)` on
     * whichever external database happened to hold that id — no client check at
     * all. Requisition ids are sequential integers, so any signed-in
     * Procurement user could edit or delete another tenant's requisition by
     * guessing a number.
     */
    private function scopeRequisitionToClient($query, $connection, ?Request $request = null): void
    {
        if ($this->isRootTesting($request)) {
            return;
        }

        $name = is_string($connection) ? $connection : $connection->getName();

        if (SchemaProbe::hasColumn($name, 'requisitions', 'client_id')) {
            $query->where('requisitions.client_id', $this->clientId());
        }
    }

    /**
     * Inventory owns defect records. Procurement can coordinate the supplier
     * replacement lifecycle, but all access stays scoped to the signed-in
     * client's Inventory data.
     */
    private function defectsForCurrentClient(Request $request)
    {
        $query = Defect::query()->orderByDesc('created_at');

        if (! (config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin')) {
            $query->where('client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    /** Inventory defect feed retained for API compatibility. */
    public function defects(Request $request)
    {
        return response()->json(
            $this->defectsForCurrentClient($request)->get()->map(fn (Defect $defect) => [
                'id' => $defect->id,
                'defect_no' => sprintf('DEF-%06d', $defect->id),
                'part_name' => $defect->part_name,
                'quantity' => $defect->quantity,
                'description' => $defect->description,
                'source' => $defect->source,
                'reported_by' => $defect->created_by,
                'status' => $defect->status,
                'date' => optional($defect->created_at)->toIso8601String(),
            ])
        );
    }

    /**
     * Track supplier-return progress without fabricating an Inventory receipt.
     * Physical stock still changes only through Inventory stock receiving.
     */
    public function updateDefect(Request $request, int $defect)
    {
        $data = $request->validate([
            'action' => 'required|string|in:reject,return',
        ]);

        $record = $this->defectsForCurrentClient($request)->whereKey($defect)->firstOrFail();
        // Legacy rows may still say "Open"; both spellings mean Pending.
        $current = strtolower(self::normaliseDefectStatus($record->status));

        // Procurement only decides the first step. Once the defect has been
        // returned to the supplier, raising a purchase order takes it from
        // Processing through In Transit, Delivered and Completed — exactly the
        // same pipeline as every other request.
        $transitions = [
            'reject' => ['pending' => 'Rejected'],
            'return' => ['pending' => 'Returned to Supplier'],
        ];
        $next = $transitions[$data['action']][$current] ?? null;

        if (! $next) {
            return response()->json([
                'message' => sprintf('This action is unavailable while the defect is %s.', $record->status),
            ], 422);
        }

        $record->update(['status' => $next]);

        return response()->json([
            'status' => 'ok',
            'defect_status' => $next,
            'receiving_created' => false,
            'message' => 'Defect replacement status updated. Inventory stock is updated only when an incoming shipment is approved.',
        ]);
    }

    /**
     * Inventory raises a defect as "Open"; Procurement shows and stores it as
     * "Pending" so it starts the same way every other request does.
     */
    public static function normaliseDefectStatus(?string $status): string
    {
        $value = trim((string) $status);

        if ($value === '' || strcasecmp($value, 'Open') === 0) {
            return 'Pending';
        }

        return $value;
    }

    private function getRequisitionConnections(): array
    {
        $connections = [];

        foreach (['order_fulfillment', 'inventory'] as $connectionName) {
            if (SchemaProbe::hasTable($connectionName, 'requisitions')) {
                $connections[] = DB::connection($connectionName);
            }
        }

        if ($connections === []) {
            throw new \RuntimeException('No external requisition source is available.');
        }

        return $connections;
    }

    /**
     * Find which external connection (order_fulfillment or inventory) actually
     * holds this client's requisition with this id.
     *
     * Returns null when no source holds it *for this client* — callers must
     * treat that as a 404 rather than falling back to "whichever database
     * answered first", which is what the old implementation did and which
     * meant a write could land on the wrong database and still report success.
     */
    private function findRequisitionConnectionFor($id, ?Request $request = null)
    {
        foreach ($this->getRequisitionConnections() as $connection) {
            $query = $connection->table('requisitions')->where('id', $id);
            $this->scopeRequisitionToClient($query, $connection, $request);

            if ($query->exists()) {
                return $connection;
            }
        }

        return null;
    }

    private function requisitionHasColumn($connection, string $column): bool
    {
        $name = is_string($connection) ? $connection : $connection->getName();

        return SchemaProbe::hasColumn($name, 'requisitions', $column);
    }

    private function getRequisitionSelectFields($connection): array
    {
        if ($connection->getName() === 'order_fulfillment') {
            return [
                'id',
                'req_number as requisition_number',
                'item',
                'qty as qty',
                'department',
                'requested_by',
                'priority',
                DB::raw("'Pending' as status"),
                'date_requested as request_date',
                'notes',
                'created_at',
                'updated_at',
            ];
        }

        // Inventory requisitions: id, client_id, req_id, part_name, quantity,
        // department, requested_by, notes, date_requested, status, priority.
        // (No `destination` column — that was Manufacturing's.)
        return [
            'id',
            'req_id as requisition_number',
            'part_name as item',
            'quantity as qty',
            'department',
            'requested_by',
            'priority',
            'status',
            'date_requested as request_date',
            'notes',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Bring POs forward when every logged delivery has reached Inventory.
     * This also repairs orders received before Inventory wrote the terminal
     * PO status back, such as orders that previously had a placeholder row.
     */
    private function reconcileReceivedPurchaseOrders($purchaseOrders): void
    {
        $purchaseOrderIds = $purchaseOrders->pluck('id')->filter()->values()->all();
        if ($purchaseOrderIds === []) {
            return;
        }

        $deliveriesByPurchaseOrder = DB::connection('procurement')
            ->table('deliveries')
            ->whereIn('purchase_order_id', $purchaseOrderIds)
            ->get(['purchase_order_id', 'status'])
            ->groupBy('purchase_order_id');

        $pending = [];

        foreach ($purchaseOrders as $purchaseOrder) {
            $statuses = $deliveriesByPurchaseOrder->get($purchaseOrder->id, collect())
                ->pluck('status')
                ->map(fn ($status) => strtolower(trim((string) $status)))
                ->filter()
                ->values();

            if ($statuses->isEmpty()
                || $statuses->contains(fn ($status) => ! in_array($status, ['delivered', 'completed', 'cancelled'], true))
                || ! $statuses->contains(fn ($status) => in_array($status, ['delivered', 'completed'], true))) {
                continue;
            }

            $targetStatus = $statuses->every(fn ($status) => in_array($status, ['completed', 'cancelled'], true))
                ? 'completed'
                : 'delivered';

            // Skip rows that already carry the target status. Without this
            // guard (the PurchaseOrderController copy has it, this one did
            // not) every single page load re-issued the same no-op UPDATE for
            // every reconciled order, forever.
            if (strtolower((string) $purchaseOrder->status) === $targetStatus) {
                continue;
            }

            $pending[$targetStatus][] = $purchaseOrder->id;
            $purchaseOrder->status = $targetStatus;
        }

        // One UPDATE per target status instead of one per purchase order.
        foreach ($pending as $targetStatus => $ids) {
            DB::connection('procurement')->table('purchase_orders')
                ->whereIn('id', $ids)
                ->whereIn('status', ['approved', 'processing'])
                ->update(['status' => $targetStatus, 'updated_at' => now()]);
        }
    }

    /**
     * Requisitions list page (filters, sortable table, add requisition modal).
     */
    public function index(Request $request)
    {
        $requisitions = collect();

        foreach ($this->getRequisitionConnections() as $connection) {
            $connectionRequisitions = $connection
                ->table('requisitions')
                ->select($this->getRequisitionSelectFields($connection));

            $this->scopeRequisitionToClient($connectionRequisitions, $connection, $request);

            $connectionRequisitions = $connectionRequisitions
                ->orderBy('created_at', 'desc')
                ->get();

            // Sources with a real status column (Inventory) are the source of
            // truth — Procurement writes back to them, so their status must not
            // be overwritten by the PO-derived fallback below.
            $hasStatusColumn = $this->requisitionHasColumn($connection, 'status');

            foreach ($connectionRequisitions as $req) {
                $req->source_connection = $connection->getName();
                $req->status_authoritative = $hasStatusColumn;
                $requisitions->push($req);
            }
        }

        // Defect replacements share this work queue with regular purchase
        // requisitions. Inventory remains the source of truth for defect
        // status and stock; this only normalises records for the shared UI.
        foreach ($this->defectsForCurrentClient($request)->get() as $defect) {
            $requisitions->push((object) [
                'id' => $defect->id,
                'requisition_number' => sprintf('DEF-%06d', $defect->id),
                'item' => $defect->part_name,
                'qty' => $defect->quantity,
                'department' => 'Inventory',
                'requested_by' => $defect->created_by ?: 'Inventory',
                'priority' => 'Defect',
                // A defect that Inventory has just raised is Pending here, the
                // same starting point as any other request. Legacy rows stored
                // as "Open" are shown as Pending too.
                'status' => self::normaliseDefectStatus($defect->status),
                'request_date' => optional($defect->created_at)->toDateString(),
                'notes' => $defect->description,
                'created_at' => $defect->created_at,
                'updated_at' => $defect->updated_at,
                'source_connection' => 'inventory_defect',
                'record_type' => 'defect',
                'status_authoritative' => true,
                'defect_source' => $defect->source ?: 'Inventory',
            ]);
        }

        $requisitions = $requisitions->sortByDesc('created_at')->values();
        $requisitionRefs = $requisitions->pluck('requisition_number')->filter()->all();

        // Purchase orders are owned by Procurement, not Inventory or Order
        // Fulfillment. Looking for them in an external requisition database
        // meant fulfilled requests could never be recognized as closed.
        $purchaseOrders = collect();
        try {
            $procurement = DB::connection('procurement');
            if ($procurement->getSchemaBuilder()->hasTable('purchase_orders')) {
                $purchaseOrderQuery = $procurement
                    ->table('purchase_orders')
                    ->whereIn('requisition_reference', $requisitionRefs);

                if (! $this->isRootTesting($request)
                    && SchemaProbe::hasColumn('procurement', 'purchase_orders', 'client_id')) {
                    $purchaseOrderQuery->where('client_id', $this->clientId());
                }

                $purchaseOrders = $purchaseOrderQuery
                    ->get()
                    ->keyBy('requisition_reference');

                $this->reconcileReceivedPurchaseOrders($purchaseOrders);
            }
        } catch (\Exception $e) {
            // Leave the queue usable when the Procurement connection is unavailable.
        }

        // Status writes discovered while rendering are collected here and
        // flushed in one batched UPDATE per target status after the map. The
        // old code issued a separate UPDATE inside the loop for every
        // reconciled requisition — a GET that fired N writes, and two
        // concurrent page loads raced each other.
        $inventoryStatusWrites = [];

        // Defect status changes discovered while rendering, flushed after the
        // map in one UPDATE per target status.
        $defectStatusWrites = [];

        $requisitions = $requisitions->map(function ($req) use ($purchaseOrders, &$inventoryStatusWrites, &$defectStatusWrites) {
            $ref = $req->requisition_number;
            $po = $purchaseOrders->get($ref);

            if ($po) {
                $poStatus = strtolower(trim($po->status ?? 'pending'));
                $currentStatus = strtolower(trim($req->status ?? 'pending'));

                // A requisition's fulfillment status follows its purchase order:
                //   PO pending/approved -> Processing  (a PO exists for it)
                //   PO processing       -> In Transit  (logged in Deliveries)
                //   PO delivered        -> Delivered   (shipment delivered)
                //   PO completed        -> Completed
                $derived = [
                    'pending' => 'Processing',
                    'approved' => 'Processing',
                    'processing' => 'In Transit',
                    'delivered' => 'Delivered',
                    'completed' => 'Completed',
                ];

                // Inventory owns its requisition rows. When Inventory has
                // received the final delivery, persist the matching terminal
                // status there so the UI and database stay in agreement.
                $advanceable = ['pending', 'approved', 'processing', 'in transit', 'intransit', 'delivered', ''];

                // A defect replacement follows the same pipeline once it has
                // been returned to the supplier and a PO raised against it:
                //   Returned to Supplier -> Processing -> In Transit
                //     -> Delivered -> Completed
                // Its status lives in Inventory's defects table, so the derived
                // value is written back there rather than to a requisitions row.
                if (($req->record_type ?? null) === 'defect') {
                    if (isset($derived[$poStatus])
                        && in_array($currentStatus, array_merge($advanceable, ['returned to supplier', 'open']), true)
                        && $currentStatus !== strtolower($derived[$poStatus])) {
                        $defectStatusWrites[$derived[$poStatus]][] = $req->id;
                        $req->status = $derived[$poStatus];
                    }

                    $req->po_number = $po->po_number;
                    $req->po_status = $po->status;

                    return $req;
                }

                if (! empty($req->status_authoritative)
                    && ($req->source_connection ?? null) === 'inventory'
                    && in_array($poStatus, ['delivered', 'completed'], true)
                    && in_array($currentStatus, $advanceable, true)) {
                    $inventoryStatusWrites[$derived[$poStatus]][] = $req->id;
                    $req->status = $derived[$poStatus];
                } elseif (empty($req->status_authoritative)
                    && isset($derived[$poStatus])
                    && in_array($currentStatus, $advanceable, true)) {
                    $req->status = $derived[$poStatus];
                }

                $req->po_number = $po->po_number;
                $req->po_status = $po->status;
            }

            return $req;
        });

        // Resolve defect/adjustment details for replacement requisitions
        $requisitions = $requisitions->map(function ($req) {
            if (!str_contains($req->notes ?? '', '[defect_id:')) {
                return $req;
            }
            preg_match('/[defect_id:(\d+)]/', $req->notes ?? '', $m);
            $defectId = (int) ($m[1] ?? 0);
            if ($defectId < 1) {
                return $req;
            }
            $defect = DB::connection('inventory')
                ->table('defects')
                ->where('id', $defectId)
                ->first();
            if ($defect) {
                $req->defect_info = $defect;
                if ($defect->source === 'Adjustment' && !empty($defect->source_id)) {
                    $req->adjustment_info = DB::connection('inventory')
                        ->table('stock_adjustments')
                        ->where('id', (int) $defect->source_id)
                        ->first();
                }
            }
            return $req;
        });

        // One UPDATE per distinct target status for defects too.
        foreach ($defectStatusWrites as $targetStatus => $ids) {
            try {
                $query = DB::connection('inventory')->table('defects')->whereIn('id', $ids);
                if (! $this->isRootTesting($request)) {
                    $query->where('client_id', $this->clientId());
                }
                $query->update(['status' => $targetStatus, 'updated_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        // One UPDATE per distinct target status, tenant-scoped, instead of one
        // per row inside the map above.
        foreach ($inventoryStatusWrites as $targetStatus => $ids) {
            try {
                $query = DB::connection('inventory')->table('requisitions')->whereIn('id', $ids);
                $this->scopeRequisitionToClient($query, 'inventory', $request);
                $query->update(['status' => $targetStatus, 'updated_at' => now()]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        // Requisitions are an auditable request history, not only an active
        // work queue. Delivered and completed rows must remain available to
        // the requester and Procurement; the existing status filters let a
        // user narrow the table to active work when needed.
        $requisitions = $requisitions->values();

        $statusCounts = $requisitions->map(function ($req) {
            return strtolower(str_replace(' ', '', $req->status ?? 'Pending'));
        })->countBy();

        return view('procurement::pages.requisitions', compact('requisitions', 'statusCounts'));
    }

    /**
     * Handle the "+ New Requisition" modal submit (submitAddReq in app-forms.js).
     */
    public function store(Request $request)
    {
        // Requisitions are raised in Inventory / Order Fulfillment, never here.
        // This used to return 200 "ok", which the browser treated as success:
        // it inserted a row into the table and toasted "submitted for
        // approval" for a record that was never written anywhere. Answer with
        // a real refusal so the client cannot fake a success.
        return response()->json([
            'status' => 'error',
            'message' => 'Requisitions are created in Inventory or Order Fulfillment, not in Procurement.',
        ], 422);
    }

    public function update(Request $request, $requisition)
    {
        $validated = $request->validate([
            'status' => 'nullable|string|max:20',
            'ref' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $newStatus = null;

        // Status changes go through the writer so the Pending -> Approved /
        // Rejected -> Processing -> Completed rules are enforced here on the
        // server, not just by hiding buttons in the UI.
        if (! empty($validated['status'])) {
            $target = RequisitionStatusWriter::normalise($validated['status']);

            if (! RequisitionStatusWriter::isKnownStatus($target)) {
                return response()->json([
                    'status' => 'error',
                    'message' => sprintf('Unknown requisition status "%s".', $validated['status']),
                ], 422);
            }

            $writer = new RequisitionStatusWriter;

            // Prefer the unique requisition reference — the numeric id can
            // collide across the Inventory / Order Fulfillment databases, which
            // would target the wrong record. Fall back to id if no ref is sent.
            $result = ! empty($validated['ref'])
                ? $writer->transitionByReference($validated['ref'], $target)
                : $writer->transitionById($requisition, $target);

            if (! $result['ok']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'] ?? 'Unable to update this requisition.',
                ], $result['code'] ?? 422);
            }

            $newStatus = $result['status'];
        }

        // Notes are free-form and unaffected by the status lifecycle.
        if (array_key_exists('notes', $validated) && $validated['notes'] !== null) {
            $connection = $this->findRequisitionConnectionFor($requisition, $request);

            if (! $connection) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Requisition not found.',
                ], 404);
            }

            if ($this->requisitionHasColumn($connection, 'notes')) {
                $query = $connection->table('requisitions')->where('id', $requisition);
                $this->scopeRequisitionToClient($query, $connection, $request);
                $query->update([
                    'notes' => $validated['notes'],
                    'updated_at' => now(),
                ]);
            }
        }

        return response()->json(['status' => 'ok', 'requisition_status' => $newStatus]);
    }

    public function destroy(Request $request, $requisition)
    {
        $connection = $this->findRequisitionConnectionFor($requisition, $request);

        if (! $connection) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requisition not found.',
            ], 404);
        }

        $query = $connection->table('requisitions')->where('id', $requisition);
        $this->scopeRequisitionToClient($query, $connection, $request);
        $deleted = $query->delete();

        if ($deleted === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Requisition not found.',
            ], 404);
        }

        return response()->json(['status' => 'ok']);
    }
}
