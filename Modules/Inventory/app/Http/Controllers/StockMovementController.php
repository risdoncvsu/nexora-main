<?php

namespace Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Inventory\Models\StockMovement;
use Modules\Inventory\Models\Warehouse;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        // Only the three meaningful ledger events are exposed: goods entering,
        // goods leaving, and ecommerce reservations. Older transfer/adjustment
        // rows remain retained for audit purposes but are no longer presented
        // as separate movement classifications.
        $query = StockMovement::with(['item', 'warehouse', 'performer'])
            ->whereIn('type', ['inbound', 'outbound', 'reservation'])
            ->orderByDesc('created_at');

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($warehouse = $request->input('warehouse')) {
            $query->where('warehouse_id', $warehouse);
        }

        if ($reference = $request->input('reference')) {
            $query->where('reference', $reference);
        }

        if ($dateRange = $request->input('date_range')) {
            match ($dateRange) {
                'today' => $query->whereDate('created_at', today()),
                'this_week' => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]),
                'this_month' => $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]),
                default => null,
            };
        }

        if ($search = $request->input('search')) {
            $search = strtolower($search);
            $query->where(function ($builder) use ($search) {
                $builder->whereRaw('LOWER(reference) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('item', function ($items) use ($search) {
                        $items->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $totals = [
            'inbound' => StockMovement::where('type', 'inbound')->sum('quantity'),
            'outbound' => StockMovement::where('type', 'outbound')->sum('quantity'),
            'reservation' => StockMovement::where('type', 'reservation')->sum('quantity'),
        ];
        $totals['net'] = $totals['inbound'] - $totals['outbound'];

        return view('inventory::stock-movement', [
            'movements' => $query->paginate(10)->appends($request->query()),
            'warehouses' => Warehouse::where('status', 'active')->whereNull('deleted_at')->get(),
            'totals' => $totals,
            'activePage' => 'stock-movement',
        ]);
    }
}
