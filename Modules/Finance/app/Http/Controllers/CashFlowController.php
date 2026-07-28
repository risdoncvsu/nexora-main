<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Invoice;

class CashFlowController extends Controller
{
    public function index()
    {
        $data = ['cashOnHand' => 0, 'cashInflow' => 0, 'cashOutflow' => 0, 'netCashFlow' => 0, 'beginningCashBalance' => 0];

        try {
            $assets = (float) Account::query()->where('account_type', 'Asset')->sum('balance');
            $liabilities = (float) Account::query()->where('account_type', 'Liability')->sum('balance');
            $paidInvoices = (float) Invoice::query()
                ->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', ['paid'])
                ->sum('paid_amount');
            $procurementExpenses = $this->procurementExpenses();

            $cashInflow = $paidInvoices + $assets;
            $cashOutflow = $procurementExpenses + $liabilities;
            $netCashFlow = $cashInflow - $cashOutflow;

            // Finance has no client-owned opening-balance table. Derive the
            // currently available balance from client-owned account and flow data
            // rather than reading the standalone, cross-company Balance table.
            $cashOnHand = max(0, $assets + $paidInvoices - $procurementExpenses);

            $data = compact('cashOnHand', 'cashInflow', 'cashOutflow', 'netCashFlow') + [
                'beginningCashBalance' => max(0, $cashOnHand - $netCashFlow),
            ];
        } catch (\Throwable) {
            // Keep the Finance shell usable while a newly provisioned client has
            // no finance/procurement data yet.
        }

        return view('finance::cashflowdash', $data);
    }

    private function procurementExpenses(): float
    {
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
    }

    private function isRootAdmin(): bool
    {
        return config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
    }
}
