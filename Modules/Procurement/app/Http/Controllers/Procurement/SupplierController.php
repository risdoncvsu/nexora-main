<?php

namespace Modules\Procurement\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\Models\Warehouse;
use Modules\Procurement\Support\SchemaProbe;

class SupplierController extends Controller
{
    /**
     * Replace a supplier's product catalog, including each product's category
     * when the categories column exists (see EnsureProcurementClientColumns).
     *
     * Delete-then-insert has to be atomic: this used to run as a bare DELETE
     * followed by one INSERT per product, so a failure partway through wiped
     * the supplier's catalog and left it partially rebuilt.
     */
    private function replaceSupplierProducts(int $supplierId, array $products): void
    {
        $hasCategories = SchemaProbe::hasColumn('procurement', 'supplier_products', 'categories');
        $now = now();

        $rows = [];
        foreach ($products as $product) {
            if (empty($product['name'])) {
                continue;
            }

            $row = [
                'supplier_id' => $supplierId,
                'name' => $product['name'],
                'sku' => $product['sku'] ?? null,
                'unit_price' => $product['price'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($hasCategories) {
                $row['categories'] = $product['category'] ?? null;
            }

            $rows[] = $row;
        }

        DB::connection('procurement')->transaction(function () use ($supplierId, $rows) {
            DB::connection('procurement')->table('supplier_products')->where('supplier_id', $supplierId)->delete();

            if ($rows !== []) {
                DB::connection('procurement')->table('supplier_products')->insert($rows);
            }
        });
    }
    private function table(string $name)
    {
        $query = DB::connection('procurement')->table($name);

        if (! (config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin')) {
            $query->where($name.'.client_id', (int) session('employee_client_id'));
        }

        return $query;
    }

    /**
     * Supplier directory page (filters, sortable table, add supplier modal).
     */
    public function index(Request $request)
    {
        $suppliers = $this->table('suppliers')->orderBy('created_at', 'desc')->get();

        // "N POs placed" on each supplier card. One grouped query for the whole
        // page rather than a count per card.
        $supplierIds = $suppliers->pluck('id')->filter()->all();
        $poCounts = $supplierIds
            ? $this->table('purchase_orders')
                ->whereIn('supplier_id', $supplierIds)
                ->selectRaw('supplier_id, count(*) as total')
                ->groupBy('supplier_id')
                ->pluck('total', 'supplier_id')
            : collect();

        if ($request->wantsJson() || $request->ajax()) {
            $data = $suppliers->map(function ($s) {
                $products = [];
                if (!empty($s->product_items)) {
                    $decoded = json_decode($s->product_items, true);
                    if (is_array($decoded)) $products = $decoded;
                }
                return [
                    'id' => $s->id,
                    'name' => $s->name,
                    'brand' => $s->brand,
                    'warehouse_id' => $s->warehouse_id,
                    'products' => $products,
                ];
            });
            return response()->json(['status' => 'ok', 'data' => $data]);
        }

        return view('procurement::pages.suppliers', compact('suppliers', 'poCounts'));
    }

    /**
     * Handle the "+ Add Supplier" modal submit (submitAddSupplier in app-forms.js).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sid'         => 'nullable|string|max:50',
            'name'        => 'required|string|max:150',
            'contact'     => 'required|string|max:150',
            'email'       => 'required|email|max:150',
            'phone'       => 'required|string|max:30',
            'address'     => 'required|string|max:255',
            'brand'       => 'nullable|string|max:100',
            'status'      => 'nullable|string|max:20',
            'productsJson'=> 'nullable|string',
            'warehouse_id'=> 'nullable|integer',
        ]);

        // Warehouse is optional (matches nexora — no warehouse on the supplier
        // form). Only validate one when provided.
        $warehouse = null;
        if (! empty($validated['warehouse_id'])) {
            $warehouse = Warehouse::query()
                ->whereKey((int) $validated['warehouse_id'])
                ->where('status', 'active')
                ->first();
        }

        $products = [];
        if ($request->filled('productsJson')) {
            $decoded = json_decode($request->input('productsJson'), true);
            if (is_array($decoded)) {
                $products = $decoded;
            }
        }

        $supplierId = DB::connection('procurement')->table('suppliers')->insertGetId([
            'client_id' => (int) session('employee_client_id'),
            'name' => $validated['name'],
            'contact_person' => $validated['contact'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'brand' => $validated['brand'] ?? null,
            'warehouse_id' => $warehouse?->id,
            'status' => $validated['status'] ?? 'active',
            'product_items' => json_encode($products),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->replaceSupplierProducts($supplierId, $products);

        // NOTE: original code called an ErpIntegrationService here with an
        // undefined $supplier variable and no `use` import for the class —
        // that would have crashed every "Add Supplier" submit. Removed for
        // now; tell me if you actually have that service and I'll wire it
        // back in correctly.

        return response()->json(['status' => 'ok', 'data' => ['id' => $supplierId] + $validated]);
    }

    public function update(Request $request, $supplier)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:150',
            'contact' => 'nullable|string|max:150',
            'email' => 'nullable|email|max:150',
            'phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:100',
            'warehouse_id' => 'nullable|integer',
            'productsJson' => 'nullable|string',
        ]);

        if (array_key_exists('warehouse_id', $validated) && $validated['warehouse_id'] !== null) {
            $warehouse = Warehouse::query()
                ->whereKey((int) $validated['warehouse_id'])
                ->where('status', 'active')
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'Select an active warehouse belonging to your client.',
                ]);
            }

            $validated['warehouse_id'] = $warehouse->id;
        }

        $update = [
            'name' => $validated['name'] ?? DB::raw('name'),
            'contact_person' => $validated['contact'] ?? DB::raw('contact_person'),
            'email' => $validated['email'] ?? DB::raw('email'),
            'phone' => $validated['phone'] ?? DB::raw('phone'),
            'address' => $validated['address'] ?? DB::raw('address'),
            'brand' => $validated['brand'] ?? DB::raw('brand'),
            'warehouse_id' => $validated['warehouse_id'] ?? DB::raw('warehouse_id'),
            'updated_at' => now(),
        ];

        // Confirm the supplier belongs to this client *before* touching its
        // product catalog — replaceSupplierProducts() deletes by supplier_id
        // and was previously reached even when the following scoped UPDATE
        // matched nothing.
        $exists = $this->table('suppliers')->where('id', $supplier)->exists();

        if (! $exists) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier not found.',
            ], 404);
        }

        // Products (name/sku/price/category) are edited as a whole list in the
        // modal; when sent, they replace the supplier's product catalog.
        if ($request->filled('productsJson')) {
            $decoded = json_decode($request->input('productsJson'), true);
            $products = is_array($decoded) ? $decoded : [];
            $update['product_items'] = json_encode($products);
            $this->replaceSupplierProducts((int) $supplier, $products);
        }

        $this->table('suppliers')->where('id', $supplier)->update($update);

        return response()->json(['status' => 'ok']);
    }

    public function destroy($supplier)
    {
        // supplier_products cascades in the database; purchase_orders and
        // deliveries carry their own FK behaviour. Answer 404 rather than "ok"
        // for an id this client does not own.
        $deleted = $this->table('suppliers')->where('id', $supplier)->delete();

        if ($deleted === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Supplier not found.',
            ], 404);
        }

        return response()->json(['status' => 'ok']);
    }
}
