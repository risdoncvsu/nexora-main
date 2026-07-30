<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Services\StorefrontInvoiceSynchronizer;

class CashFlowController extends Controller
{
    public function index()
    {
        app(StorefrontInvoiceSynchronizer::class)->syncForCurrentClient();

        $data = ['cashOnHand' => 0, 'cashInflow' => 0, 'cashOutflow' => 0, 'netCashFlow' => 0, 'beginningCashBalance' => 0];

        // A balance on an Asset account is an opening/current position, not a
        // new cash receipt. Only paid customer invoices belong in cash inflow.
        $assets = $this->accountBalance('asset');
        $paidInvoices = $this->paidInvoices();
        $procurementExpenses = $this->procurementExpenses();

        $cashInflow = $paidInvoices;
        $cashOutflow = $procurementExpenses;
        $netCashFlow = $cashInflow - $cashOutflow;
        $cashOnHand = max(0, $assets + $netCashFlow);

        $data = compact('cashOnHand', 'cashInflow', 'cashOutflow', 'netCashFlow') + [
            'beginningCashBalance' => $assets,
        ];

        return view('finance::cashflowdash', $data);
    }

    private function procurementExpenses(): float
    {
        try {
            $schema = Schema::connection('procurement');
            if (! $schema->hasTable('purchase_orders')) {
                return 0;
            }

            $query = DB::connection('procurement')->table('purchase_orders')
                ->whereIn(DB::raw('LOWER(COALESCE(status, \'\'))'), ['approved', 'processing', 'delivered', 'completed']);
            if (! $this->isRootAdmin()) {
                $clientId = session('employee_client_id');
                if (! $clientId || ! $schema->hasColumn('purchase_orders', 'client_id')) {
                    return 0;
                }
                $query->where('client_id', $clientId);
            }

            $amount = $schema->hasColumn('purchase_orders', 'amount')
                ? 'COALESCE(amount, 0)'
                : ($schema->hasColumn('purchase_orders', 'qty') && $schema->hasColumn('purchase_orders', 'unit_price')
                    ? 'COALESCE(qty, 0) * COALESCE(unit_price, 0)'
                    : '0');

            return (float) $query->selectRaw("COALESCE(SUM({$amount}), 0) AS total")->value('total');
        } catch (\Throwable) {
            return 0;
        }
    }

    private function accountBalance(string $type): float
    {
        try {
            return (float) Account::query()
                ->whereRaw('LOWER(COALESCE(account_type, \'\')) = ?', [strtolower($type)])
                ->sum('balance');
        } catch (\Throwable) {
            return 0;
        }
    }

    private function paidInvoices(): float
    {
        try {
            return (float) Invoice::query()
                ->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', ['paid'])
                ->sum('paid_amount');
        } catch (\Throwable) {
            return 0;
        }
    }

    private function isRootAdmin(): bool
    {
        return config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
    }
}
