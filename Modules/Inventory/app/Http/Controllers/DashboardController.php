<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Models\StockLevel;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;

class DashboardController extends Controller
{
    public function index()
    {
        $items = Item::all();
        $warehouses = Warehouse::where('status', 'active')->get();
        $movements = StockMovement::with(['item', 'warehouse'])
            ->whereIn('type', ['inbound', 'outbound', 'reservation'])
            ->orderByDesc('created_at')->take(5)->get();

        $totalItems = $items->count();
        $totalStockUnits = StockLevel::sum(DB::raw('GREATEST(stock - reserved_quantity, 0)'));
        $lowStockAlerts = StockLevel::where(function ($query) {
            $query->whereColumn('stock', '<=', 'reserved_quantity')
                ->orWhere(function ($threshold) {
                    $threshold->where('reorder_threshold', '>', 0)
                        ->whereRaw('(stock - reserved_quantity) <= reorder_threshold');
                });
        })->count();

        $criticalAlerts = StockLevel::with(['item', 'warehouse'])
            ->whereHas('item')
            ->whereHas('warehouse')
            ->where(function ($query) {
                $query->whereColumn('stock', '<=', 'reserved_quantity')
                    ->orWhere(function ($threshold) {
                        $threshold->where('reorder_threshold', '>', 0)
                            ->whereRaw('(stock - reserved_quantity) <= reorder_threshold');
                    });
            })
            ->orderByRaw('CASE WHEN stock <= reserved_quantity THEN 0 ELSE 1 END')
            ->take(10)
            ->get()
            ->map(function ($stockLevel) {
                $available = $stockLevel->stock - $stockLevel->reserved_quantity;

                return [
                    'name' => $stockLevel->item->name,
                    'item_id' => $stockLevel->item_id,
                    'warehouse' => $stockLevel->warehouse->name,
                    'type' => $available <= 0 ? 'out_of_stock' : 'low_stock',
                    'on_hand' => max(0, $available),
                    'threshold' => $stockLevel->reorder_threshold ?? 0,
                ];
            });

        $trendData = $this->getTrendData('this_week');
        $warehouseDistribution = $warehouses->map(fn ($warehouse) => [
            'name' => $warehouse->name,
            'total' => $warehouse->stockLevels()->sum(DB::raw('GREATEST(stock - reserved_quantity, 0)')),
        ])->filter(fn ($warehouse) => $warehouse['total'] > 0)->values();

        $recentMovements = $movements->map(fn ($movement) => [
            'type' => $movement->type,
            'item_name' => $movement->item?->name ?? 'Deleted',
            'quantity' => abs((int) $movement->quantity),
            'warehouse' => $movement->warehouse?->name ?? 'Deleted',
            'reference' => $movement->reference,
            'date' => $movement->created_at?->format('M d, Y h:i A'),
        ]);

        $pendingApprovalsCount = (int) DB::connection('inventory')->table('requisitions')
            ->where('status', 'pending')
            ->when(! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin') && session('employee_client_id'), fn ($q) => $q->where('client_id', (int) session('employee_client_id')))
            ->count();

        $pendingDeliveriesCount = (int) DB::connection('procurement')->table('deliveries')
            ->whereIn('status', ['pending', 'intransit'])
            ->when(! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin') && session('employee_client_id'), fn ($q) => $q->where('client_id', (int) session('employee_client_id')))
            ->count();

        return view('inventory::index', compact(
            'totalItems', 'totalStockUnits', 'lowStockAlerts', 'criticalAlerts',
            'trendData', 'warehouseDistribution', 'recentMovements',
            'pendingApprovalsCount', 'pendingDeliveriesCount'
        ))->with('activePage', 'dashboard');
    }

    public function trendData(Request $request)
    {
        return response()->json($this->getTrendData($request->input('period', 'this_week')));
    }

    private function getTrendData(string $period): array
    {
        [$start, $end] = match ($period) {
            'last_week' => [Carbon::now()->subWeek()->startOfWeek(), Carbon::now()->subWeek()->endOfWeek()],
            'this_month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'last_month' => [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()],
            default => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
        };

        $movements = StockMovement::whereBetween('created_at', [$start, $end])->get();
        $labels = [];
        $inbound = [];
        $outbound = [];

        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            $labels[] = $day->format('M d');
            $daily = $movements->filter(fn ($movement) => $movement->created_at->isSameDay($day));
            $inbound[] = $daily->where('type', 'inbound')->sum(fn ($movement) => abs($movement->quantity));
            $outbound[] = $daily->where('type', 'outbound')->sum(fn ($movement) => abs($movement->quantity));
        }

        return compact('labels', 'inbound', 'outbound');
    }
}
