<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;

use Modules\Inventory\Models\Category;
use Modules\Inventory\Models\Procurement;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\StockReceiving;
use Modules\Inventory\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Services\PackingMaterialCatalog;

class StockReceivingController extends Controller
{
    private function syncPackingMaterialReceipt($inventory, int $clientId, string $itemName, int $quantity): void
    {
        $definition = PackingMaterialCatalog::definition($itemName);
        if (! $definition || ! $inventory->getSchemaBuilder()->hasTable('packing_materials')) {
            return;
        }

        $material = $inventory->table('packing_materials')
            ->where('client_id', $clientId)
            ->whereRaw('LOWER(name) = LOWER(?)', [$definition['name']])
            ->first();

        if ($material) {
            $inventory->table('packing_materials')
                ->where('id', $material->id)
                ->increment('stock_qty', $quantity, ['updated_at' => now()]);

            return;
        }

        $inventory->table('packing_materials')->insert([
            'client_id' => $clientId,
            'name' => $definition['name'],
            'stock_qty' => $quantity,
            'low_stock_threshold' => 5,
            'is_box' => $definition['is_box'],
            'box_size' => $definition['box_size'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function procurementDeliveriesQuery()
    {
        $schema = Schema::connection('procurement');
        $hasDeliverToWarehouse = $schema->hasColumn('deliveries', 'deliver_to_warehouse');

        $destinationWarehouse = $hasDeliverToWarehouse
            ? DB::raw('deliveries.deliver_to_warehouse as destination_warehouse_id')
            : DB::raw('NULL as destination_warehouse_id');

        $query = Procurement::query()
            ->leftJoin('suppliers', 'deliveries.supplier_id', '=', 'suppliers.id')
            ->leftJoin('purchase_orders', 'deliveries.purchase_order_id', '=', 'purchase_orders.id')
            ->select(
                'deliveries.*',
                'suppliers.name as supplier_name',
                $destinationWarehouse,
                DB::raw('NULL as po_category')
            );

        if (! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin')) {
            $query->where('purchase_orders.client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    private function findDeliveryForCurrentClient(int $deliveryId): Procurement
    {
        return $this->procurementDeliveriesQuery()
            ->where('deliveries.id', $deliveryId)
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        // Incoming stock is sourced from this client's Procurement deliveries.
        $baseQuery = $this->procurementDeliveriesQuery();
        $query = (clone $baseQuery)
            ->whereIn('deliveries.status', ['pending', 'intransit'])
            ->orderByDesc('deliveries.created_at');

        if ($search = $request->input('search')) {
            $search = strtolower($search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(deliveries.shipment_number) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(suppliers.name) LIKE ?', ["%{$search}%"]);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('deliveries.status', $status);
        }

        $deliveries = $query->paginate(10)->appends($request->query());

        // A shipment is only "processed" once every one of its lines has been
        // received (or the whole shipment was rejected). Counting any single row
        // as done hid partially-applied deliveries and made them unrecoverable.
        $shipmentNumbers = $deliveries->pluck('shipment_number')->filter()->unique();
        $receivedLineCounts = StockReceiving::query()
            ->whereIn('shipment_number', $shipmentNumbers)
            ->where('status', 'approved')
            ->selectRaw('shipment_number, COUNT(DISTINCT item_id) as lines_received')
            ->groupBy('shipment_number')
            ->pluck('lines_received', 'shipment_number');
        $rejectedShipments = StockReceiving::query()
            ->whereIn('shipment_number', $shipmentNumbers)
            ->where('status', 'rejected')
            ->pluck('shipment_number')
            ->all();

        // For kpi cards
        $pendingCount = (clone $baseQuery)->whereIn('deliveries.status', ['pending', 'intransit'])->count();
        $receivedTodayCount = StockReceiving::whereDate('processed_at', today())
            ->where('status', 'approved')
            ->distinct()
            ->count('shipment_number');
        $rejectedCount = StockReceiving::where('status', 'rejected')
            ->distinct()
            ->count('shipment_number');

        $deliveryProcessed = [];
        $warehouseNames = Warehouse::withTrashed()
            ->whereIn('name', $deliveries->pluck('destination_warehouse_id')->filter()->unique())
            ->pluck('name', 'name');

        $deliveries->getCollection()->transform(function ($delivery) use ($warehouseNames) {
            $delivery->destination_warehouse_name = $warehouseNames[$delivery->destination_warehouse_id] ?? 'No warehouse assigned';
            $delivery->supplier_name = $delivery->supplier_name ?? 'Unknown supplier';

            return $delivery;
        });

        $poIds = $deliveries->pluck('purchase_order_id')->filter()->unique()->values()->all();
        $deliveryItems = Procurement::itemsForPurchaseOrders($poIds);

        foreach ($deliveries as $delivery) {
            $items = $deliveryItems[$delivery->purchase_order_id] ?? [];
            $delivery->po_category = !empty($items) ? ($items[0]->categories ?? null) : null;

            $expectedLines = count(array_unique(array_map(fn ($i) => $i->sku, $items)));
            $receivedLines = (int) ($receivedLineCounts[$delivery->shipment_number] ?? 0);

            $deliveryProcessed[$delivery->id] = in_array($delivery->shipment_number, $rejectedShipments, true)
                || ($expectedLines > 0 && $receivedLines >= $expectedLines);
        }

        $deliveryItemsJson = json_encode(collect($deliveryItems)->map(function ($items) {
            return collect($items)->map(fn ($i) => [
                'name' => $i->item_name,
                'qty' => $i->qty,
                'sku' => $i->sku,
            ]);
        })->toArray());

        // Audit trail â€” past approved/rejected records
        $history = StockReceiving::with(['item', 'warehouse', 'processor'])
            ->orderByDesc('processed_at')
            ->limit(50)
            ->get();

        $historySuppliers = $this->procurementDeliveriesQuery()
            ->whereIn('deliveries.shipment_number', $history->pluck('shipment_number')->filter()->unique())
            ->pluck('supplier_name', 'deliveries.shipment_number');

        return view('inventory::stock-receiving', [
            'deliveries' => $deliveries,
            'deliveryProcessed' => $deliveryProcessed,
            'pendingCount' => $pendingCount,
            'receivedTodayCount' => $receivedTodayCount,
            'rejectedCount' => $rejectedCount,
            'history' => $history,
            'historySuppliers' => $historySuppliers,
            'filters' => $request->only(['search', 'status']),
            'activePage' => 'stock-receiving',
            'deliveryItemsJson' => $deliveryItemsJson,
        ]);
    }

    public function approve(Request $request, $deliveryId)
    {
        $delivery = $this->findDeliveryForCurrentClient((int) $deliveryId);

        if ($delivery->status !== 'intransit') {
            return back()->withErrors(["del_action_{$delivery->id}" => 'Only an in-transit shipment can be received. Log the supplier delivery in Procurement first.']);
        }

        // Procurement publishes new POs here as expected deliveries so the
        // warehouse can plan incoming stock.  They are not physical stock yet:
        // receiving is allowed only after Procurement approves the PO.
        if ($delivery->purchase_order_id) {
            $purchaseOrderStatus = DB::connection('procurement')
                ->table('purchase_orders')
                ->where('id', $delivery->purchase_order_id)
                ->value('status');

            if (! in_array(strtolower((string) $purchaseOrderStatus), ['approved', 'processing'], true)) {
                return back()->withErrors(["del_action_{$delivery->id}" => 'Procurement must approve this purchase order before Inventory can receive it.']);
            }
        }

        $warehouse = Warehouse::query()
            ->where('name', $delivery->destination_warehouse_id)
            ->where('status', 'active')
            ->first();

        if (! $warehouse) {
            return back()->withErrors(["del_action_{$delivery->id}" => 'This purchase order has no active destination warehouse.']);
        }

        $result = $this->executeApproval($delivery, ['warehouse_id' => $warehouse->id]);

        if ($result === true) {
            return back()->with('success', 'Delivery approved and stock updated.');
        }

        return back()->withErrors(["del_action_{$delivery->id}" => $result]);
    }

    private function executeApproval(Procurement $delivery, array $validated): true|string
    {
        $clientId = (int) session('employee_client_id');
        $employeeId = session('employee_id');
        $inv = DB::connection('inventory');

        $poItems = $delivery->getPurchaseOrderItems();

        if (empty($poItems)) {
            return 'Could not fetch delivery from procurement.';
        }

        $rejected = $inv->table('stock_receivings')
            ->where('client_id', $clientId)
            ->where('shipment_number', $delivery->shipment_number)
            ->where('status', 'rejected')
            ->exists();

        if ($rejected) {
            return 'This delivery has already been rejected and cannot be received.';
        }

        foreach ($poItems as $product) {
            $categoryName = $product->categories ?? 'Uncategorized Incoming Goods';
            $categoryId = $inv->table('categories')
                ->where('name', $categoryName)
                ->where('client_id', $clientId)
                ->value('id');

            if (!$categoryId) {
                $categoryId = $inv->table('categories')->insertGetId([
                    'name' => $categoryName,
                    'client_id' => $clientId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $itemId = $inv->table('items')
                ->where('sku', $product->sku)
                ->where('client_id', $clientId)
                ->value('id');

            if (!$itemId) {
                $itemId = $inv->table('items')->insertGetId([
                    'sku' => $product->sku,
                    'name' => $product->item_name,
                    'category_id' => $categoryId,
                    'unit_cost' => $product->unit_price,
                    'client_id' => $clientId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($categoryId) {
                $inv->table('items')
                    ->where('id', $itemId)
                    ->update(['category_id' => $categoryId, 'updated_at' => now()]);
            }

            $alreadyReceived = $inv->table('stock_receivings')
                ->where('client_id', $clientId)
                ->where('shipment_number', $delivery->shipment_number)
                ->where('item_id', $itemId)
                ->exists();

            if ($alreadyReceived) {
                $existingSl = $inv->table('stock_levels')
                    ->where('client_id', $clientId)
                    ->where('item_id', $itemId)
                    ->where('warehouse_id', $validated['warehouse_id'])
                    ->first();
                if (!$existingSl) {
                    $inv->table('stock_levels')->insert([
                        'item_id' => $itemId,
                        'warehouse_id' => $validated['warehouse_id'],
                        'stock' => $product->qty,
                        'reorder_threshold' => 10,
                        'client_id' => $clientId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                continue;
            }

            $inv->table('stock_receivings')->insert([
                'shipment_number' => $delivery->shipment_number,
                'item_id' => $itemId,
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $product->qty,
                'status' => 'approved',
                'processed_by' => $employeeId,
                'remarks' => $delivery->remarks,
                'client_id' => $clientId,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $existingSl = $inv->table('stock_levels')
                ->where('client_id', $clientId)
                ->where('item_id', $itemId)
                ->where('warehouse_id', $validated['warehouse_id'])
                ->first();

            if ($existingSl) {
                $inv->table('stock_levels')
                    ->where('id', $existingSl->id)
                    ->increment('stock', $product->qty, ['updated_at' => now()]);
            } else {
                $inv->table('stock_levels')->insert([
                    'item_id' => $itemId,
                    'warehouse_id' => $validated['warehouse_id'],
                    'stock' => $product->qty,
                    'reorder_threshold' => 10,
                    'client_id' => $clientId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $inv->table('stock_movements')->insert([
                'type' => 'inbound',
                'item_id' => $itemId,
                'warehouse_id' => $validated['warehouse_id'],
                'quantity' => $product->qty,
                'reference' => $delivery->shipment_number,
                'notes' => "From delivery - Shipment: {$delivery->shipment_number}",
                'performed_by' => $employeeId,
                'client_id' => $clientId,
                'created_at' => now(),
            ]);

            $this->syncPackingMaterialReceipt(
                $inv,
                $clientId,
                (string) $product->item_name,
                (int) $product->qty
            );
        }

        $inv->table('warehouses')
            ->where('id', $validated['warehouse_id'])
            ->update(['last_activity_at' => now()]);

        $procurement = DB::connection('procurement');
        $procurement
            ->table('deliveries')
            ->where('id', $delivery->id)
            ->update(['status' => 'delivered', 'updated_at' => now()]);

        // Receiving is the event that confirms goods reached the warehouse.
        // Keep the PO in sync, but do not mark a split PO as delivered until
        // every non-cancelled shipment attached to it has been received.
        if ($delivery->purchase_order_id) {
            $hasOutstandingDelivery = $procurement->table('deliveries')
                ->where('purchase_order_id', $delivery->purchase_order_id)
                ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
                ->exists();

            if (! $hasOutstandingDelivery) {
                $procurement->table('purchase_orders')
                    ->where('id', $delivery->purchase_order_id)
                    ->update(['status' => 'delivered', 'updated_at' => now()]);

                // A replacement is complete only after its final shipment is
                // actually received in Inventory. Resolve the exact defect
                // linked from the originating requisition, never a similarly
                // named record belonging to another client.
                if (Schema::connection('procurement')->hasColumn('purchase_orders', 'requisition_reference')) {
                    $requisitionReference = $procurement->table('purchase_orders')
                        ->where('id', $delivery->purchase_order_id)
                        ->where('client_id', $clientId)
                        ->value('requisition_reference');

                    // Inventory owns low-stock requisitions. Mark the request
                    // delivered at the same time the final supplier shipment
                    // is received, rather than leaving it at Processing.
                    if ($requisitionReference
                        && Schema::connection('inventory')->hasTable('requisitions')
                        && Schema::connection('inventory')->hasColumn('requisitions', 'req_id')) {
                        $inv->table('requisitions')
                            ->where('client_id', $clientId)
                            ->where('req_id', $requisitionReference)
                            ->whereRaw("LOWER(COALESCE(status, 'pending')) IN ('pending', 'approved', 'processing', 'in transit', 'intransit')")
                            ->update([
                                'status' => 'Delivered',
                                'updated_at' => now(),
                            ]);
                    }

                    $requestNotes = $requisitionReference
                        ? $inv->table('requisitions')
                            ->where('client_id', $clientId)
                            ->where('req_id', $requisitionReference)
                            ->value('notes')
                        : null;

                    if ($requestNotes && preg_match('/\[defect_id:(\d+)\]/', $requestNotes, $matches)) {
                        $inv->table('defects')
                            ->where('client_id', $clientId)
                            ->where('id', (int) $matches[1])
                            ->update(['status' => 'Resolved', 'updated_at' => now()]);
                    }
                }
            }
        }

        return true;
    }

    public function reject(Request $request, $deliveryId)
    {
        $validated = $request->validate([
            'reject_reason' => 'required|string',
        ]);

        $delivery = $this->findDeliveryForCurrentClient((int) $deliveryId);

        if (! in_array($delivery->status, ['pending', 'intransit'], true)) {
            return back()->with('error', 'This delivery has already been processed.');
        }

        $clientId = (int) session('employee_client_id');
        $inv = DB::connection('inventory');

        $existing = $inv->table('stock_receivings')
            ->where('client_id', $clientId)
            ->where('shipment_number', $delivery->shipment_number)
            ->pluck('status')
            ->all();

        if (in_array('approved', $existing, true)) {
            return back()->with('error', 'This delivery has already been received and cannot be rejected.');
        }

        if (in_array('rejected', $existing, true)) {
            return back()->with('error', 'This delivery has already been rejected.');
        }

        $inv->table('stock_receivings')->insert([
            'shipment_number' => $delivery->shipment_number,
            'item_id' => null,
            'warehouse_id' => null,
            'quantity' => $this->deliveryTotalQuantity($delivery),
            'status' => 'rejected',
            'processed_by' => session('employee_id'),
            'remarks' => $validated['reject_reason'],
            'client_id' => $clientId,
            'processed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Delivery rejected.');
    }

    /**
     * `deliveries` has no reliable per-shipment quantity column, so total the
     * purchase-order lines instead of reading `$delivery->qty`.
     */
    private function deliveryTotalQuantity(Procurement $delivery): int
    {
        return (int) array_sum(array_map(
            fn ($item) => (int) $item->qty,
            $delivery->getPurchaseOrderItems()
        ));
    }
}
