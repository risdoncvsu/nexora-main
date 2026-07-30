<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Procurement\Support\RequisitionTally;
use Modules\Procurement\Support\SchemaProbe;

class DashboardController extends Controller
{
    /**
     * Compact peso format for the dashboard summaries: ₱9.6k, ₱1.2m, ₱1b.
     * Amounts under 1,000 are shown in full (₱950).
     */
    private function compactPeso($value): string
    {
        $value = (float) $value;
        $abs = abs($value);

        [$divisor, $suffix] = match (true) {
            $abs >= 1_000_000_000 => [1_000_000_000, 'b'],
            $abs >= 1_000_000 => [1_000_000, 'm'],
            $abs >= 1_000 => [1_000, 'k'],
            default => [1, ''],
        };

        $scaled = $value / $divisor;
        $formatted = $suffix === ''
            ? number_format($scaled, 0)
            : rtrim(rtrim(number_format($scaled, 1), '0'), '.'); // 9.6k, 1k (not 1.0k)

        return '₱'.$formatted.$suffix;
    }

    /**
     * Requisition counts come from the shared tally so the dashboard card, the
     * sidebar badge and the live-stats poll can never disagree — they used to
     * be three separate implementations.
     *
     * @return array{total:int, pending:int, completed:int}
     */
    private function externalRequisitionCounts(bool $rootTesting = false): array
    {
        return RequisitionTally::counts((int) session('employee_client_id'), $rootTesting);
    }

    /**
     * Lightweight JSON counts polled by the dashboard + sidebar so cards and
     * nav badges update live (no page refresh). Every query is wrapped so a
     * missing table/column/connection can never turn this into a 500.
     */
    public function liveStats(Request $request)
    {
        $db = DB::connection('procurement');
        $clientId = (int) session('employee_client_id');
        $rootTesting = config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin';

        $table = function (string $name) use ($db, $clientId, $rootTesting) {
            $query = $db->table($name);
            if (! $rootTesting) {
                $query->where($name.'.client_id', $clientId);
            }

            return $query;
        };

        $safe = function (callable $cb): int {
            try {
                return (int) $cb();
            } catch (\Throwable $e) {
                return 0;
            }
        };

        // Requisitions live on the external Order Fulfillment / Manufacturing
        // databases (same source the Requisitions page and sidebar badge use),
        // never on the procurement connection.
        $requisitionCounts = $this->externalRequisitionCounts($rootTesting);

        $poCount = $safe(fn () => $table('purchase_orders')->count());
        $supplierCount = $safe(fn () => $table('suppliers')->where('status', 'active')->count());
        $deliveryCount = $safe(fn () => $table('deliveries')->count());
        $pendingDeliveries = $safe(fn () => $table('deliveries')->whereIn('status', ['pending', 'scheduled', 'intransit'])->count());
        $totalSpend = 0.0;
        try {
            $totalSpend = (float) $table('purchase_orders')
                ->whereNotIn('status', ['pending', 'rejected', 'cancelled'])
                ->sum('amount');
        } catch (\Throwable $e) {
            // leave at 0
        }

        return response()->json([
            'cards' => [
                'activePos' => $poCount,
                'suppliers' => $supplierCount,
                // The Requisitions card counts what still needs action, not the
                // full history — same number the sidebar badge shows.
                'requisitions' => $requisitionCounts['pending'],
                'deliveries' => $deliveryCount,
            ],
            // Sub-labels are polled too, so they stop disagreeing with the
            // number above them until the next page load.
            'subs' => [
                'activePos' => $poCount > 0 ? $this->compactPeso($totalSpend).' total spend' : 'No purchase orders yet',
                'suppliers' => $supplierCount > 0 ? 'Active suppliers' : 'No supplier data yet',
                'requisitions' => $requisitionCounts['completed'] > 0
                    ? $requisitionCounts['completed'].' completed'
                    : 'None completed yet',
                'deliveries' => $deliveryCount > 0
                    ? ($pendingDeliveries > 0 ? $pendingDeliveries.' in progress' : 'All shipments settled')
                    : 'No deliveries yet',
            ],
            'badges' => [
                'purchaseOrders' => $safe(fn () => $table('purchase_orders')->where('status', 'pending')->count()),
                'requisitions' => $requisitionCounts['pending'],
                'deliveries' => $pendingDeliveries,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $db = DB::connection('procurement');
        $clientId = (int) session('employee_client_id');
        $rootTesting = config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin';

        $table = function (string $name) use ($db, $clientId, $rootTesting) {
            $query = $db->table($name);

            if (! $rootTesting) {
                $query->where($name.'.client_id', $clientId);
            }

            return $query;
        };

        $poCount = $table('purchase_orders')->count();
        $poStatusBreakdown = $table('purchase_orders')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $supplierCount = $table('suppliers')->where('status', 'active')->count();
        // From the external requisition sources, not procurement.requisitions.
        // The card shows what still needs action (Pending); the sub-label shows
        // how many have been Completed.
        $requisitionTally = $this->externalRequisitionCounts($rootTesting);
        $requisitionCount = $requisitionTally['pending'];
        $requisitionCompletedCount = $requisitionTally['completed'];
        $deliveryCount = $table('deliveries')->count();
        $pendingDeliveries = $table('deliveries')
            ->whereIn('status', ['pending', 'scheduled', 'intransit'])
            ->count();

        $recentPOs = $table('purchase_orders')
            ->select('id', 'po_number', 'supplier_id', 'qty', 'amount', 'status', 'priority', 'order_date', 'expected_delivery_date', 'item', 'brand')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $supplierIds = $recentPOs->pluck('supplier_id')->filter()->unique()->all();
        $suppliersMap = $supplierIds
            ? $table('suppliers')->whereIn('id', $supplierIds)->pluck('name', 'id')->all()
            : [];

        $recentDeliveries = $table('deliveries')
            ->select('id', 'shipment_number', 'purchase_order_id', 'supplier_id', 'status', 'delivery_date', 'estimated_arrival', 'actual_arrival', 'carrier')
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $deliverySupplierIds = $recentDeliveries->pluck('supplier_id')->filter()->unique()->all();
        $deliverySuppliersMap = $deliverySupplierIds
            ? $table('suppliers')->whereIn('id', $deliverySupplierIds)->pluck('name', 'id')->all()
            : [];

        // "Spend by Category" is split per ordered item's category
        // (purchase_order_items.category). Until the schema migration adds that
        // column, fall back to each PO's primary category stored in `brand`.
        $hasItemCategory = SchemaProbe::hasColumn('procurement', 'purchase_order_items', 'category');

        // Spend figures only count POs that represent committed money. A PO that
        // is still Pending, or was Rejected/Cancelled, must not contribute to
        // total spend, category spend, or top-supplier spend.
        $uncountedStatuses = ['pending', 'rejected', 'cancelled'];

        if ($hasItemCategory) {
            $spendByCategoryAll = $table('purchase_order_items')
                ->join('purchase_orders', 'purchase_order_items.purchase_order_id', '=', 'purchase_orders.id')
                ->select('purchase_order_items.category as category', DB::raw('SUM(purchase_order_items.amount) as total'))
                ->whereNotNull('purchase_order_items.category')
                ->where('purchase_order_items.category', '!=', '')
                ->whereNotIn('purchase_orders.status', $uncountedStatuses)
                ->groupBy('purchase_order_items.category')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) {
                    $row->formatted_total = $this->compactPeso($row->total);

                    return $row;
                })
                ->values();
        } else {
            $spendByCategoryAll = $table('purchase_orders')
                ->select('brand as category', DB::raw('SUM(amount) as total'))
                ->whereNotNull('brand')
                ->where('brand', '!=', '')
                ->whereNotIn('status', $uncountedStatuses)
                ->groupBy('brand')
                ->orderByDesc('total')
                ->get()
                ->map(function ($row) {
                    $row->formatted_total = $this->compactPeso($row->total);

                    return $row;
                })
                ->values();
        }

        // Top 5 for the compact dashboard panel; the full list feeds the
        // "View all" modal.
        $spendByCategory = $spendByCategoryAll->take(5)->values();

        $totalSpend = $table('purchase_orders')
            ->whereNotIn('status', $uncountedStatuses)
            ->sum('amount');

        $topSuppliers = $table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->select('suppliers.id', 'suppliers.name', DB::raw('SUM(purchase_orders.amount) as total_spend'))
            ->whereNotIn('purchase_orders.status', $uncountedStatuses)
            ->when(! $rootTesting, fn ($query) => $query->where('suppliers.client_id', $clientId))
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_spend')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $row->formatted_total_spend = $this->compactPeso($row->total_spend);

                return $row;
            });

        return view('procurement::pages.dashboard', [
            'poCount' => $poCount,
            'poStatusBreakdown' => $poStatusBreakdown,
            'supplierCount' => $supplierCount,
            'requisitionCount' => $requisitionCount,
            'requisitionCompletedCount' => $requisitionCompletedCount,
            'deliveryCount' => $deliveryCount,
            'pendingDeliveries' => $pendingDeliveries,
            'recentPOs' => $recentPOs,
            'suppliersMap' => $suppliersMap,
            'recentDeliveries' => $recentDeliveries,
            'deliverySuppliersMap' => $deliverySuppliersMap,
            'spendByCategory' => $spendByCategory,
            'spendByCategoryAll' => $spendByCategoryAll,
            'totalSpend' => $totalSpend,
            'totalSpendFormatted' => $this->compactPeso($totalSpend),
            'topSuppliers' => $topSuppliers,
            'lowStockAlerts' => collect(),
        ]);
    }
}
