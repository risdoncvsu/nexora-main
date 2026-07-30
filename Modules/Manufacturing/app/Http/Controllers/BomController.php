<?php

namespace Modules\Manufacturing\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Inventory\Models\Item;
use Modules\Inventory\Services\PackingMaterialCatalog;
use Modules\Manufacturing\Models\ProductBom;

class BomController extends Controller
{
    public function index()
    {
        return view('manufacturing::boms.index', [
            'boms' => ProductBom::with('items')->latest()->get(),
            // Inventory owns the physical quantities.  Read its live
            // client-scoped catalogue and annotate each item with the stock
            // available for production instead of copying a second catalogue
            // into Manufacturing.
            'inventoryItems' => Item::query()->with('category')
                ->leftJoin('stock_levels as stock_levels', function ($join): void {
                    $join->on('stock_levels.item_id', '=', 'items.id')
                        ->on('stock_levels.client_id', '=', 'items.client_id');
                })
                ->select([
                    'items.id', 'items.sku', 'items.name',
                    DB::raw('COALESCE(SUM(stock_levels.stock - COALESCE(stock_levels.reserved_quantity, 0)), 0) as available_quantity'),
                ])
                ->groupBy('items.id', 'items.sku', 'items.name')
                ->orderBy('items.name')
                ->get()
                ->reject(fn (Item $item): bool => PackingMaterialCatalog::definition((string) $item->name) !== null)
                ->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string'],
            'bom_type' => ['nullable', 'in:prebuilt,packaging'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'integer'],
            'items.*.quantity_required' => ['required', 'integer', 'min:1'],
        ]);

        $clientId = (int) session('employee_client_id');
        abort_unless($clientId > 0, 403);

        $itemIds = collect($validated['items'])->pluck('inventory_item_id')->unique()->values();
        $inventoryItems = Item::query()->whereIn('id', $itemIds)->get()->keyBy('id');

        if ($inventoryItems->count() !== $itemIds->count()) {
            return back()->withErrors(['items' => 'Every BOM component must belong to this client inventory.'])->withInput();
        }

        $componentIsPackaging = function (Item $item): bool {
            $category = strtolower((string) optional($item->category)->name);
            return str_contains($category, 'packag')
                || str_contains($category, 'packing')
                || PackingMaterialCatalog::definition((string) $item->name) !== null;
        };
        $requestedType = $validated['bom_type'] ?? 'prebuilt';
        $matchesRequestedType = $requestedType === 'packaging'
            ? $inventoryItems->every($componentIsPackaging)
            : $inventoryItems->every(fn (Item $item): bool => ! $componentIsPackaging($item));

        if (! $matchesRequestedType) {
            return back()->withErrors(['items' => 'Use only packaging materials for a packaging BoM, and non-packaging inventory for a prebuilt BoM.'])->withInput();
        }

        DB::connection('manufacturing')->transaction(function () use ($validated, $inventoryItems, $requestedType): void {
            $attributes = [
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => 'active',
            ];
            if (Schema::connection('manufacturing')->hasColumn('product_boms', 'bom_type')) {
                $attributes['bom_type'] = $requestedType;
            }
            $bom = ProductBom::create($attributes);

            foreach ($validated['items'] as $component) {
                $item = $inventoryItems->get($component['inventory_item_id']);
                $bom->items()->create([
                    'inventory_item_id' => $item->id,
                    'item_sku' => $item->sku,
                    'item_name' => $item->name,
                    'quantity_required' => $component['quantity_required'],
                ]);
            }
        });

        return redirect()->route('manufacturing.dashboard', [
            'page' => 'orders',
            'sub' => 'boms',
            'bomType' => $requestedType,
        ])->with('success', 'Bill of Materials created. It is now available to E-commerce.');
    }

    public function destroy(ProductBom $bom): RedirectResponse
    {
        $bom->items()->delete();
        $bom->delete();

        return redirect()->route('manufacturing.dashboard', [
            'page' => 'orders',
            'sub' => 'boms',
        ])->with('success', 'Bill of Materials removed.');
    }
}
