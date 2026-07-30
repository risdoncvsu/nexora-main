<?php

namespace Modules\Finance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StorefrontInvoiceSynchronizer
{
    /**
     * Backfill Finance only for storefront orders that predate the invoice
     * integration. Existing Finance records remain authoritative.
     */
    public function syncForCurrentClient(): void
    {
        try {
            $financeSchema = Schema::connection('finance');
            $ecommerceSchema = Schema::connection('ecommerce');
            if (! $financeSchema->hasTable('invoice') || ! $ecommerceSchema->hasTable('orders')) {
                return;
            }

            $invoiceColumns = $financeSchema->getColumnListing('invoice');
            if (! in_array('nexora_client_id', $invoiceColumns, true) || ! in_array('order_id', $invoiceColumns, true)) {
                return;
            }

            $orders = DB::connection('ecommerce')->table('orders');
            if (! $this->isRootAdmin()) {
                $clientId = session('employee_client_id');
                if (! $clientId || ! $ecommerceSchema->hasColumn('orders', 'client_id')) {
                    return;
                }
                $orders->where('client_id', $clientId);
            }

            $orders->orderBy('created_at')->each(function (object $order) use ($invoiceColumns): void {
                $clientId = (int) ($order->client_id ?? 0);
                $orderId = (string) ($order->id ?? '');
                if ($clientId <= 0 || $orderId === '') {
                    return;
                }

                $finance = DB::connection('finance')->table('invoice');
                $existing = $finance->where('order_id', $orderId)->first();
                if ($existing) {
                    if (($existing->nexora_client_id ?? null) === null) {
                        $finance->where('order_id', $orderId)->whereNull('nexora_client_id')->update([
                            'nexora_client_id' => $clientId,
                            'updated_at' => now(),
                        ]);
                    }
                    return;
                }

                $total = (float) ($order->total ?? 0);
                $shipping = (float) ($order->shipping_fee ?? 0);
                $paid = strtolower((string) ($order->payment_status ?? '')) === 'paid';
                $orderDate = ! empty($order->created_at)
                    ? Carbon::parse($order->created_at)->toDateString()
                    : now()->toDateString();
                $values = [
                    'nexora_client_id' => $clientId,
                    'order_id' => $orderId,
                    'issue_date' => $orderDate,
                    'due_date' => null,
                    'invoice_amount' => max(0, $total - $shipping),
                    'discount' => 0,
                    'shipping_fee' => $shipping,
                    'paid_amount' => $paid ? $total : 0,
                    'outstanding_amount' => $paid ? 0 : $total,
                    'payment_status' => $paid ? 'Paid' : 'Unpaid',
                    'status' => $paid ? 'Paid' : 'Pending',
                    'payment_date' => $paid ? $orderDate : null,
                    'reference_number' => 'ECOM-'.$orderId,
                    'payment_details' => 'Backfilled from ecommerce order',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                DB::connection('finance')->table('invoice')->insert(array_intersect_key($values, array_flip($invoiceColumns)));
            });
        } catch (\Throwable) {
            // New checkout records use the regular ERP integration path.
        }
    }

    private function isRootAdmin(): bool
    {
        return config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
    }
}
