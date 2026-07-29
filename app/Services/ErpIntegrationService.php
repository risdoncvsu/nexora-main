<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Synchronous integration boundary for ERP transactions.
 *
 * Each module keeps ownership of its own database. This service only creates
 * the client-scoped counterpart records required by an ERP transaction and
 * records a durable ITSM audit entry for every outcome.
 */
class ErpIntegrationService
{
    /** @var array<string, array<int, string>> */
    private array $columns = [];

    public function recordAudit(int $clientId, string $event, string $module, array $details = []): void
    {
        if (! Schema::hasTable('erp_audit_logs')) {
            return;
        }

        DB::table('erp_audit_logs')->insert([
            'client_id' => $clientId,
            'event' => $event,
            'module' => $module,
            'actor_id' => session('employee_id') ?: auth()->id(),
            'details' => json_encode($details, JSON_THROW_ON_ERROR),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Propagate a committed storefront order into operational modules.
     * Inventory is reserved first; if a line cannot be fulfilled the checkout
     * is rejected before any downstream record is created.
     */
    public function propagateEcommerceOrder(int $clientId, object $order, iterable $items): void
    {
        $items = collect($items)->values();
        $reservationLines = $this->reserveInventory($clientId, (string) $order->id, $items);

        try {
            $customer = trim(($order->shipping_address['first_name'] ?? '').' '.($order->shipping_address['last_name'] ?? ''));
            $productSummary = $items->pluck('name')->filter()->unique()->implode(', ');
            $totalQuantity = (int) $items->sum('quantity');
            $amount = (float) $order->total;

            // A packaging BOM is an operational packing list, not a customer
            // computer build. It may support shipment preparation, but it must
            // never generate a Manufacturing benchmark/QC work order.
            $manufacturingItems = $items->reject(fn ($item) => $this->isPackagingBomLine($clientId, $item))->values();
            $manufacturingProductSummary = $manufacturingItems->pluck('name')->filter()->unique()->implode(', ');
            $manufacturingQuantity = (int) $manufacturingItems->sum('quantity');

            // Finance is a required customer-facing record. Create it before
            // operational integrations so a temporary Fulfillment,
            // Procurement, or Manufacturing schema issue cannot suppress an
            // invoice for a successfully accepted storefront order.
            $this->createFinanceInvoice($clientId, (string) $order->id, $amount, (float) $order->shipping_fee, (string) $order->payment_status);
            $this->createFulfillmentOrder($clientId, (string) $order->id, $customer, $productSummary, $totalQuantity, $amount, $order, $items);
            if ($manufacturingItems->isNotEmpty()) {
                $this->createManufacturingWorkOrder($clientId, (string) $order->id, $manufacturingProductSummary, $manufacturingQuantity);
            }
            $this->createProcurementRequisition($clientId, (string) $order->id, $productSummary, $totalQuantity, $amount);
            $this->writeBiSnapshot($clientId);

            $this->recordAudit($clientId, 'order.placed', 'ecommerce', [
                'order_id' => (string) $order->id,
                'reservations' => $reservationLines,
                'total' => $amount,
            ]);
        } catch (\Throwable $exception) {
            $this->recordAudit($clientId, 'order.propagation_failed', 'ecommerce', [
                'order_id' => (string) $order->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function inventoryAvailabilityChanged(int $clientId, int $itemId, string $event = 'inventory.stock_changed'): void
    {
        $inventory = DB::connection('inventory');
        $item = $inventory->table('items')->where('client_id', $clientId)->where('id', $itemId)->first();
        if (! $item) return;

        $available = (int) $inventory->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->sum(DB::raw('stock - reserved_quantity'));
        $soldOut = $available <= 0;

        // Standalone ecommerce tables vary by catalogue type. Update every
        // client-scoped catalogue row that uses the same product name, where
        // that table exposes stock or a sold-out flag.
        foreach (['products', 'components', 'cpus', 'gpus', 'rams', 'storages', 'laptops'] as $table) {
            if (! Schema::connection('ecommerce')->hasTable($table)) continue;
            $columns = $this->columns['ecommerce.'.$table] ??= Schema::connection('ecommerce')->getColumnListing($table);
            if (! in_array('name', $columns, true)) continue;
            $changes = [];
            if (in_array('stock', $columns, true)) $changes['stock'] = $available;
            if (in_array('is_sold_out', $columns, true)) $changes['is_sold_out'] = $soldOut;
            if (in_array('updated_at', $columns, true)) $changes['updated_at'] = now();
            if ($changes) DB::connection('ecommerce')->table($table)->where('client_id', $clientId)->whereRaw('LOWER(name) = ?', [mb_strtolower($item->name)])->update($changes);
        }

        if ($available <= 0 || $this->isLowStock($clientId, $itemId, $available)) {
            $this->createProcurementRequisition($clientId, 'STOCK-'.$itemId, $item->name, max(1, $this->recommendedQuantity($clientId, $itemId, $available)), (float) $item->unit_cost * max(1, $this->recommendedQuantity($clientId, $itemId, $available)));
        }

        $this->writeBiSnapshot($clientId);
        $this->recordAudit($clientId, $event, 'inventory', ['item_id' => $itemId, 'sku' => $item->sku, 'available_quantity' => $available]);
    }

    public function financeInvoiceChanged(int $clientId, object $invoice, bool $cancelled = false): void
    {
        $orderId = (string) ($invoice->order_id ?? '');
        if ($orderId !== '') {
            if ($cancelled) {
                DB::connection('order_fulfillment')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['status' => 'CANCELLED', 'updated_at' => now()]);
                DB::connection('ecommerce')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['status' => 'cancelled', 'updated_at' => now()]);
            } elseif (strtolower((string) $invoice->status) === 'paid') {
                DB::connection('ecommerce')->table('orders')->where('client_id', $clientId)->where('id', $orderId)->update(['payment_status' => 'paid', 'updated_at' => now()]);
            }
        }

        $this->writeBiSnapshot($clientId);
        $this->recordAudit($clientId, $cancelled ? 'invoice.cancelled' : 'invoice.updated', 'finance', ['invoice_id' => $invoice->getKey(), 'order_id' => $orderId, 'status' => $invoice->status]);
    }

    public function employeeRemoved(int $clientId, int $employeeId, string $email): void
    {
        // Employee sessions are server-side. Remove their current session state
        // and leave the audit record as the authoritative ITSM trail.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $employeeId)->delete();
        }
        $this->recordAudit($clientId, 'employee.deleted', 'hr', ['employee_id' => $employeeId, 'email' => $email]);
    }

    public function supplierChanged(int $clientId, object $supplier, bool $deleted = false): void
    {
        foreach (['inventory', 'manufacturing'] as $connection) {
            if (! Schema::connection($connection)->hasTable('integration_suppliers')) continue;
            $table = DB::connection($connection)->table('integration_suppliers');
            if ($deleted) {
                $table->where('client_id', $clientId)->where('source_supplier_id', $supplier->id)->delete();
                continue;
            }
            $table->updateOrInsert(
                ['client_id' => $clientId, 'source_supplier_id' => $supplier->id],
                ['name' => $supplier->name, 'email' => $supplier->email, 'phone' => $supplier->phone, 'category' => $supplier->category, 'status' => $supplier->status, 'updated_at' => now(), 'created_at' => now()]
            );
        }
        $this->recordAudit($clientId, $deleted ? 'supplier.deleted' : 'supplier.synced', 'procurement', ['supplier_id' => $supplier->id, 'name' => $supplier->name]);
    }

    public function inventoryProductDeleted(int $clientId, object $item): void
    {
        foreach (['products', 'components', 'cpus', 'gpus', 'rams', 'storages', 'laptops'] as $table) {
            if (! Schema::connection('ecommerce')->hasTable($table)) continue;
            $columns = $this->columns['ecommerce.'.$table] ??= Schema::connection('ecommerce')->getColumnListing($table);
            if (! in_array('name', $columns, true) || ! in_array('is_sold_out', $columns, true)) continue;
            $changes = ['is_sold_out' => true];
            if (in_array('updated_at', $columns, true)) $changes['updated_at'] = now();
            DB::connection('ecommerce')->table($table)->where('client_id', $clientId)->whereRaw('LOWER(name) = ?', [mb_strtolower($item->name)])->update($changes);
        }
        $this->recordAudit($clientId, 'inventory.item_deleted', 'inventory', ['item_id' => $item->id, 'sku' => $item->sku, 'name' => $item->name]);
    }

    /**
     * Repair ownership for storefront orders created before Order Fulfillment
     * gained client_id columns. We only touch fulfillment records whose UUID
     * is already owned by this same client in Ecommerce; manually-created
     * legacy orders remain unassigned rather than being exposed to a client.
     */
    public function reconcileFulfillmentClientOwnership(int $clientId): void
    {
        try {
            $fulfillmentSchema = Schema::connection('order_fulfillment');
            $ecommerceSchema = Schema::connection('ecommerce');

            if (! $ecommerceSchema->hasTable('orders')
                || ! $ecommerceSchema->hasColumn('orders', 'client_id')
                || ! $fulfillmentSchema->hasTable('orders')
                || ! $fulfillmentSchema->hasColumn('orders', 'client_id')) {
                return;
            }

            $orderIds = DB::connection('ecommerce')->table('orders')
                ->where('client_id', $clientId)
                ->pluck('id')
                ->filter()
                ->values();

            foreach ($orderIds->chunk(250) as $ids) {
                $ids = $ids->all();
                DB::connection('order_fulfillment')->table('orders')
                    ->whereIn('id', $ids)
                    ->whereNull('client_id')
                    ->update(['client_id' => $clientId, 'updated_at' => now()]);

                foreach (['order_items', 'shipments', 'returns'] as $table) {
                    if (! $fulfillmentSchema->hasTable($table)
                        || ! $fulfillmentSchema->hasColumn($table, 'client_id')
                        || ! $fulfillmentSchema->hasColumn($table, 'order_id')) {
                        continue;
                    }

                    DB::connection('order_fulfillment')->table($table)
                        ->whereIn('order_id', $ids)
                        ->whereNull('client_id')
                        ->update(['client_id' => $clientId, 'updated_at' => now()]);
                }
            }
        } catch (\Throwable $exception) {
            \Log::warning('Unable to reconcile Order Fulfillment client ownership.', [
                'client_id' => $clientId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Assign legacy Finance invoices only when their storefront order UUID is
     * owned by the current client. This restores visibility without assigning
     * unrelated historical accounting data to an arbitrary company.
     */
    public function reconcileFinanceInvoiceClientOwnership(int $clientId): void
    {
        try {
            $financeSchema = Schema::connection('finance');
            $ecommerceSchema = Schema::connection('ecommerce');

            if (! $financeSchema->hasTable('invoice')
                || ! $financeSchema->hasColumn('invoice', 'nexora_client_id')
                || ! $financeSchema->hasColumn('invoice', 'order_id')
                || ! $ecommerceSchema->hasTable('orders')
                || ! $ecommerceSchema->hasColumn('orders', 'client_id')) {
                return;
            }

            $orderIds = DB::connection('ecommerce')->table('orders')
                ->where('client_id', $clientId)
                ->pluck('id')
                ->filter()
                ->values();

            foreach ($orderIds->chunk(250) as $ids) {
                DB::connection('finance')->table('invoice')
                    ->whereIn('order_id', $ids->all())
                    ->whereNull('nexora_client_id')
                    ->update(['nexora_client_id' => $clientId, 'updated_at' => now()]);
            }
        } catch (\Throwable $exception) {
            \Log::warning('Unable to reconcile Finance invoice client ownership.', [
                'client_id' => $clientId,
                'exception' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * A storefront order cannot be accepted when it cannot be packed.
     * This is a live Inventory read; packing still performs its own locked
     * decrement later to protect against concurrent shipments.
     */
    public function assertPackingMaterialsAvailable(int $clientId): void
    {
        $inventory = DB::connection('inventory');
        $schema = $inventory->getSchemaBuilder();

        if (! $schema->hasTable('packing_materials')) {
            throw new RuntimeException('Online orders are unavailable because packing materials have not been configured.');
        }

        $materials = $inventory->table('packing_materials')
            ->where('client_id', $clientId)
            ->where('stock_qty', '>', 0);

        $missing = [];
        if (! (clone $materials)->where('is_box', true)->exists()) {
            $missing[] = 'a shipping box';
        }
        if (! (clone $materials)->whereRaw('LOWER(name) IN (?, ?)', ['foam inserts', 'foam insert'])->exists()) {
            $missing[] = 'foam inserts';
        }
        if (! (clone $materials)->whereRaw('LOWER(name) IN (?, ?, ?, ?)', ['silica gel packs', 'silica gel', 'gel', 'gels'])->exists()) {
            $missing[] = 'silica gel packs';
        }

        if ($missing === []) {
            return;
        }

        $this->recordAudit($clientId, 'order.blocked_missing_packing_materials', 'ecommerce', [
            'missing_materials' => $missing,
        ]);

        throw new RuntimeException('This store cannot accept orders right now: '.implode(', ', $missing).' are unavailable.');
    }

    /** @return array<int, array{item_id:int,warehouse_id:int,quantity:int}> */
    private function reserveInventory(int $clientId, string $orderId, iterable $items): array
    {
        $inventory = DB::connection('inventory');

        return $inventory->transaction(function () use ($inventory, $clientId, $orderId, $items): array {
            $reserved = [];
            $requirements = [];

            foreach ($items as $line) {
                $quantity = max(1, (int) $line->quantity);

                if (($line->product_type ?? null) === 'bom_listing') {
                    $configuration = is_array($line->configuration ?? null)
                        ? $line->configuration
                        : json_decode((string) ($line->configuration ?? ''), true);
                    $bomId = (int) ($configuration['bom_id'] ?? 0);
                    $components = $bomId
                        ? DB::connection('manufacturing')->table('product_bom_items')
                            ->where('client_id', $clientId)->where('bom_id', $bomId)->get()
                        : collect();

                    if ($components->isEmpty()) {
                        throw new RuntimeException("The Bill of Materials for '{$line->name}' is no longer available.");
                    }

                    foreach ($components as $component) {
                        $itemId = (int) $component->inventory_item_id;
                        $requirements[$itemId] = [
                            'name' => (string) $component->item_name,
                            'quantity' => ($requirements[$itemId]['quantity'] ?? 0)
                                + ($quantity * max(1, (int) $component->quantity_required)),
                        ];
                    }

                    continue;
                }

                $item = $inventory->table('items')
                    ->where('client_id', $clientId)
                    ->where(function ($query) use ($line): void {
                        $query->where('sku', (string) $line->product_id)
                            ->orWhereRaw('LOWER(name) = ?', [mb_strtolower((string) $line->name)]);
                    })
                    ->orderBy('id')
                    ->first();

                if (! $item) {
                    throw new RuntimeException("Inventory item '{$line->name}' is not mapped for this client.");
                }

                $requirements[(int) $item->id] = [
                    'name' => (string) $item->name,
                    'quantity' => ($requirements[(int) $item->id]['quantity'] ?? 0) + $quantity,
                ];
            }

            foreach ($requirements as $itemId => $requirement) {
                $existing = $inventory->table('order_reservations')
                    ->where('client_id', $clientId)
                    ->where('order_reference', $orderId)
                    ->where('item_id', $itemId)
                    ->where('status', 'reserved')
                    ->exists();

                if ($existing) {
                    continue;
                }

                $levels = $inventory->table('stock_levels')
                    ->where('client_id', $clientId)
                    ->where('item_id', $itemId)
                    ->whereRaw('stock > reserved_quantity')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                $available = $levels->sum(fn ($level) => (int) $level->stock - (int) $level->reserved_quantity);
                if ($available < $requirement['quantity']) {
                    throw new RuntimeException("Insufficient available inventory for '{$requirement['name']}'.");
                }

                $remaining = (int) $requirement['quantity'];
                foreach ($levels as $level) {
                    if ($remaining === 0) {
                        break;
                    }

                    $allocated = min($remaining, (int) $level->stock - (int) $level->reserved_quantity);
                    if ($allocated < 1) {
                        continue;
                    }

                    $inventory->table('stock_levels')->where('id', $level->id)->increment('reserved_quantity', $allocated, ['updated_at' => now()]);
                    $inventory->table('order_reservations')->insert([
                        'client_id' => $clientId, 'order_reference' => $orderId, 'source' => 'ecommerce',
                        'item_id' => $itemId, 'warehouse_id' => $level->warehouse_id, 'quantity' => $allocated,
                        'status' => 'reserved', 'reserved_at' => now(), 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $inventory->table('stock_movements')->insert([
                        'client_id' => $clientId, 'type' => 'reservation', 'item_id' => $itemId,
                        'warehouse_id' => $level->warehouse_id, 'quantity' => $allocated,
                        'reference' => 'ECOM-'.$orderId, 'reference_id' => $orderId,
                        'performed_by' => null, 'notes' => 'Reserved for ecommerce order', 'created_at' => now(),
                    ]);
                    $reserved[] = ['item_id' => $itemId, 'warehouse_id' => (int) $level->warehouse_id, 'quantity' => $allocated];
                    $remaining -= $allocated;
                }
            }

            return $reserved;
        });
    }

    private function createFulfillmentOrder(int $clientId, string $orderId, string $customer, string $product, int $quantity, float $amount, object $order, iterable $items): void
    {
        $db = DB::connection('order_fulfillment');
        $this->requireTable('order_fulfillment', 'orders');
        $schema = Schema::connection('order_fulfillment');
        $address = is_array($order->shipping_address) ? implode(', ', array_filter($order->shipping_address)) : null;

        // A retried checkout must not reset an order that Fulfillment has
        // already moved into packing or shipping. A legacy row with this
        // storefront UUID is safe to claim for this client; a row owned by a
        // different client is a data-integrity error, never a silent merge.
        $existingOrder = $db->table('orders')->where('id', $orderId)->first();
        if ($existingOrder) {
            if (isset($existingOrder->client_id) && (int) $existingOrder->client_id !== $clientId) {
                throw new RuntimeException('The matching Order Fulfillment record belongs to a different client.');
            }
            if (($existingOrder->client_id ?? null) === null && $schema->hasColumn('orders', 'client_id')) {
                $db->table('orders')->where('id', $orderId)->update(['client_id' => $clientId, 'updated_at' => now()]);
            }
        } else {
            $db->table('orders')->insert([
                'id' => $orderId,
                'client_id' => $clientId,
                'customer_name' => $customer ?: 'Customer',
                'product_name' => $product ?: 'Storefront order',
                'qty' => $quantity,
                'product_amount' => $amount,
                'status' => 'NEW',
                'address' => $address,
                'due_date' => now()->addDays(3)->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! $schema->hasTable('order_items')) {
            return;
        }

        if ($schema->hasColumn('order_items', 'client_id')) {
            $db->table('order_items')->where('order_id', $orderId)->whereNull('client_id')
                ->update(['client_id' => $clientId, 'updated_at' => now()]);
        }

        if ($db->table('order_items')->where('order_id', $orderId)->exists()) {
            return;
        }

        $lines = collect($items)->map(function ($item) use ($clientId, $orderId): array {
            $quantity = max(1, (int) data_get($item, 'quantity', 1));
            $unitPrice = (float) (data_get($item, 'price') ?? data_get($item, 'unit_price', 0));

            return [
                'client_id'      => $clientId,
                'order_id'       => $orderId,
                'product_name'   => (string) data_get($item, 'name', 'Storefront item'),
                'qty'            => $quantity,
                'product_amount' => $unitPrice,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        })->all();

        if ($lines) {
            $db->table('order_items')->insert($lines);
        }
    }

    private function createManufacturingWorkOrder(int $clientId, string $orderId, string $product, int $quantity): void
    {
        $db = DB::connection('manufacturing');
        $id = 'WO-'.strtoupper(substr(sha1($orderId), 0, 12));
        if ($db->table('work_orders')->where('id', $id)->exists()) return;
        $this->insertAvailable('manufacturing', 'work_orders', ['id' => $id, 'client_id' => $clientId, 'fulfillment_order_id' => $orderId, 'name' => $product ?: 'Storefront assembly', 'specs' => "Ecommerce order {$orderId}; quantity {$quantity}", 'status' => 'Pending', 'due' => now()->addDays(3)->toDateString(), 'due_date' => now()->addDays(3)->toDateString(), 'source' => 'Ecommerce '.$orderId, 'assigned' => null, 'work_order_type' => 'production', 'created_at' => now(), 'updated_at' => now()]);
    }

    private function isPackagingBomLine(int $clientId, mixed $line): bool
    {
        if ((string) data_get($line, 'product_type') !== 'bom_listing') {
            return false;
        }

        $configuration = data_get($line, 'configuration');
        if (! is_array($configuration)) {
            $configuration = json_decode((string) $configuration, true) ?: [];
        }
        $bomId = (int) ($configuration['bom_id'] ?? 0);
        if ($bomId < 1 || ! Schema::connection('manufacturing')->hasColumn('product_boms', 'bom_type')) {
            return false;
        }

        return DB::connection('manufacturing')->table('product_boms')
            ->where('id', $bomId)
            ->where('client_id', $clientId)
            ->where('bom_type', 'packaging')
            ->exists();
    }

    private function createProcurementRequisition(int $clientId, string $orderId, string $product, int $quantity, float $amount): void
    {
        $db = DB::connection('procurement');
        $number = 'AUTO-'.strtoupper(substr(sha1($orderId), 0, 10));
        if ($db->table('requisitions')->where('client_id', $clientId)->where('req_number', $number)->exists()) return;
        $this->insertAvailable('procurement', 'requisitions', ['client_id' => $clientId, 'req_number' => $number, 'item' => $product ?: 'Storefront order materials', 'qty' => $quantity, 'amount' => $amount, 'uom' => 'unit', 'delivery_status' => 'pending', 'department' => 'Manufacturing', 'requested_by' => 'ERP Integration', 'status' => 'pending', 'date_requested' => now()->toDateString(), 'notes' => "Auto-created for ecommerce order {$orderId}", 'created_at' => now(), 'updated_at' => now()]);
    }

    private function createFinanceInvoice(int $clientId, string $orderId, float $amount, float $shipping, string $paymentStatus): void
    {
        $db = DB::connection('finance');
        $this->requireTable('finance', 'invoice');
        $columns = $this->columns['finance.invoice'] ??= Schema::connection('finance')->getColumnListing('invoice');
        if (! in_array('nexora_client_id', $columns, true) || ! in_array('order_id', $columns, true)) {
            throw new RuntimeException('The Finance invoice schema is missing the client or storefront order reference columns.');
        }

        // Checkout retries are expected; use the client + ecommerce order as
        // the durable idempotency key even if a legacy database lacks a unique
        // constraint for it.
        $existingInvoice = $db->table('invoice')->where('order_id', $orderId)->first();
        if ($existingInvoice) {
            // Earlier storefront integrations created invoices before Finance
            // owned a client reference. Claim only that unassigned legacy
            // row; never allow an order from one client to be reused by
            // another client.
            if (($existingInvoice->nexora_client_id ?? null) === null) {
                $db->table('invoice')
                    ->where('order_id', $orderId)
                    ->whereNull('nexora_client_id')
                    ->update(['nexora_client_id' => $clientId, 'updated_at' => now()]);
            } elseif ((int) $existingInvoice->nexora_client_id !== $clientId) {
                throw new RuntimeException('The matching Finance invoice belongs to a different client.');
            }

            return;
        }

        $paid = strtolower($paymentStatus) === 'paid';
        $invoice = [
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'invoice_amount' => $amount - $shipping,
            'discount' => 0,
            'shipping_fee' => $shipping,
            'paid_amount' => $paid ? $amount : 0,
            'outstanding_amount' => $paid ? 0 : $amount,
            'payment_method' => null,
            'reference_number' => 'ECOM-'.$orderId,
            'payment_details' => 'Automatically generated from ecommerce checkout',
            'payment_status' => $paid ? 'Paid' : 'Unpaid',
            'status' => $paid ? 'Paid' : 'Pending',
            'payment_date' => $paid ? now()->toDateString() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Insert only columns available in the deployed Finance schema. This
        // keeps storefront orders compatible with both legacy and current
        // Finance databases while preserving the order/client linkage.
        $db->table('invoice')->insert(array_intersect_key([
            'nexora_client_id' => $clientId,
            'order_id' => $orderId,
            ...$invoice,
        ], array_flip($columns)));
    }

    private function writeBiSnapshot(int $clientId): void
    {
        if (! Schema::connection('business_intelligence')->hasTable('bi_snapshots')) return;
        $db = DB::connection('business_intelligence');
        $payload = ['event' => 'order.placed', 'captured_at' => now()->toIso8601String()];
        $db->table('bi_snapshots')->updateOrInsert(['client_id' => $clientId, 'source' => 'erp-integration'], ['payload' => json_encode($payload, JSON_THROW_ON_ERROR), 'captured_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
    }

    private function isLowStock(int $clientId, int $itemId, int $available): bool
    {
        $threshold = (int) DB::connection('inventory')->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->max('reorder_threshold');
        return $threshold > 0 && $available <= $threshold;
    }

    private function recommendedQuantity(int $clientId, int $itemId, int $available): int
    {
        $threshold = (int) DB::connection('inventory')->table('stock_levels')->where('client_id', $clientId)->where('item_id', $itemId)->max('reorder_threshold');
        return max(1, ($threshold * 2) - $available);
    }

    private function insertAvailable(string $connection, string $table, array $attributes): void
    {
        $columns = $this->columns[$connection.'.'.$table] ??= Schema::connection($connection)->getColumnListing($table);
        DB::connection($connection)->table($table)->insert(array_intersect_key($attributes, array_flip($columns)));
    }

    private function requireTable(string $connection, string $table): void
    {
        if (! Schema::connection($connection)->hasTable($table)) {
            throw new RuntimeException("The {$connection} {$table} schema is not installed.");
        }
    }
}
