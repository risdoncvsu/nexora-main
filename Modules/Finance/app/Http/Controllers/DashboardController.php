<?php

namespace Modules\Finance\Http\Controllers;

use Carbon\Carbon;
use Modules\Finance\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Services\StorefrontInvoiceSynchronizer;

class DashboardController extends Controller
{
    /**
     * The Finance application shell. Keep this separate from the iframe
     * overview so that /finance/dashboard can be the entry point without
     * loading the shell inside itself.
     */
    public function shell()
    {
        return view('finance::maindash');
    }

    /**
     * Client-scoped Finance overview displayed inside the application shell.
     */
    public function overview()
    {
        $allInvoices = Invoice::query()
            ->orderByDesc('issue_date')
            ->orderByDesc('invoice_id')
            ->get();

        
        $today = Carbon::today();
$period = request()->get('period', 'this_month');

switch ($period) {

    case 'today':
        $start = $today->copy()->startOfDay();
        $end = $today->copy()->endOfDay();
        break;

    case 'this_week':
        $start = $today->copy()->startOfWeek();
        $end = $today->copy()->endOfWeek();
        break;

    case 'this_month':
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();
        break;

    case 'this_year':
        $start = $today->copy()->startOfYear();
        $end = $today->copy()->endOfYear();
        break;

    default:
        $start = $today->copy()->startOfMonth();
        $end = $today->copy()->endOfMonth();
}
$filteredInvoices = $allInvoices->filter(function ($invoice) use ($start, $end) {

    if (!$invoice->issue_date) {
        return false;
    }

    return Carbon::parse($invoice->issue_date)
        ->between($start, $end);

});
$paid = (float) $filteredInvoices
    ->filter(fn($invoice) =>
        strtolower((string)$invoice->payment_status) === 'paid'
    )
    ->sum('paid_amount');

$unpaid = (float) $filteredInvoices
    ->sum('outstanding_amount');

$overdue = (float) $filteredInvoices
    ->filter(fn($invoice) =>
        $invoice->due_date &&
        Carbon::parse($invoice->due_date)->lt($today) &&
        $invoice->outstanding_amount > 0
    )
    ->sum('outstanding_amount');

$invoiceTotal = (float) $filteredInvoices
    ->sum(fn($invoice) =>
        $invoice->paid_amount +
        $invoice->outstanding_amount
    );

        switch ($period) {

    case 'today':
        $labels = collect(range(0, 23))->map(fn($h) => sprintf('%02d:00', $h));

        $invoiceValues = $labels->map(function ($hour) use ($filteredInvoices) {
            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->format('H:00') === $hour)
                ->sum(fn($i) => (float)$i->paid_amount + (float)$i->outstanding_amount);
        });

        $paidValues = $labels->map(function ($hour) use ($filteredInvoices) {
            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->format('H:00') === $hour)
                ->sum('paid_amount');
        });

        break;

    case 'this_week':

        $labels = collect(range(0, 6))
            ->map(fn($d) => $start->copy()->addDays($d)->format('D'));

        $invoiceValues = $labels->map(function ($label, $day) use ($filteredInvoices, $start) {

            $date = $start->copy()->addDays($day)->toDateString();

            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->toDateString() === $date)
                ->sum(fn($i) => (float)$i->paid_amount + (float)$i->outstanding_amount);

        });

        $paidValues = $labels->map(function ($label, $day) use ($filteredInvoices, $start) {

            $date = $start->copy()->addDays($day)->toDateString();

            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->toDateString() === $date)
                ->sum('paid_amount');

        });

        break;

    case 'this_month':

        $labels = collect(range(1, $start->daysInMonth))
            ->map(fn($d) => (string)$d);

        $invoiceValues = $labels->map(function ($day) use ($filteredInvoices) {

            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->day == $day)
                ->sum(fn($i) => (float)$i->paid_amount + (float)$i->outstanding_amount);

        });

        $paidValues = $labels->map(function ($day) use ($filteredInvoices) {

            return (float) $filteredInvoices
                ->filter(fn($i) => Carbon::parse($i->issue_date)->day == $day)
                ->sum('paid_amount');

        });

        break;

    case 'this_year':

        $labels = collect(range(1,12))
            ->map(fn($m) => Carbon::create()->month($m)->format('M'));

        $invoiceValues = $labels->map(function ($label,$month) use ($filteredInvoices){

            return (float)$filteredInvoices
                ->filter(fn($i)=>Carbon::parse($i->issue_date)->month == $month+1)
                ->sum(fn($i)=>(float)$i->paid_amount+(float)$i->outstanding_amount);

        });

        $paidValues = $labels->map(function ($label,$month) use ($filteredInvoices){

            return (float)$filteredInvoices
                ->filter(fn($i)=>Carbon::parse($i->issue_date)->month == $month+1)
                ->sum('paid_amount');

        });

        break;
}

        $recentActivity = $filteredInvoices->take(8)->map(function (Invoice $invoice): array {
            $isPaid = strtolower((string) $invoice->payment_status) === 'paid';

            return [
                'date' => $invoice->issue_date?->format('M d, Y') ?? '—',
                'desc' => 'Invoice '.($invoice->reference_number ?: '#'.$invoice->invoice_id),
                'category' => 'E-Commerce order',
                'amount' => (float) $invoice->paid_amount + (float) $invoice->outstanding_amount,
                'status' => $isPaid ? 'Success' : 'Pending',
            ];
        })->values();

        $schema = \Illuminate\Support\Facades\Schema::connection('procurement');
        $procurementTotal = 0;
        if ($schema->hasTable('purchase_orders')) {
            $amountColumn = $schema->hasColumn('purchase_orders', 'amount') ? 'amount' : null;
            $hasUnitPrice = $schema->hasColumn('purchase_orders', 'unit_price');
            $hasQuantity = $schema->hasColumn('purchase_orders', 'qty');
            $amountExpression = $amountColumn
                ? 'COALESCE(amount, 0)'
                : ($hasUnitPrice && $hasQuantity ? 'COALESCE(qty, 0) * COALESCE(unit_price, 0)' : '0');
            
            $q = \Illuminate\Support\Facades\DB::connection('procurement')->table('purchase_orders');
            $isRootAdmin = config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
            if (! $isRootAdmin) {
                $clientId = session('employee_client_id');
                if ($clientId && $schema->hasColumn('purchase_orders', 'client_id')) {
                    $q->where('client_id', $clientId);
                } else {
                    $q->whereRaw('1 = 0');
                }
            }
            $procurementTotal = (float) $q
                ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(COALESCE(status, \'\'))'), ['approved', 'processing', 'delivered', 'completed'])
                ->selectRaw("COALESCE(SUM({$amountExpression}), 0) AS total")
                ->value('total');
        }
                    $cashBalance = 500000;

            $cashInflow = $paid;

            $cashOutflow = $procurementTotal;

            $cashOnHand = $cashBalance + ($cashInflow - $cashOutflow);
        $assets = (float) \Modules\Finance\Models\Account::query()->where('account_type', 'Asset')->sum('balance');
        $liabilityBalance = (float) \Modules\Finance\Models\Account::query()->where('account_type', 'Liability')->sum('balance');
        $liabilities = $liabilityBalance;
        $expenses = $procurementTotal;
        $equity = $assets - $liabilities;
        $expensesBreakdown = [
            ['label' => 'Procurement', 'value' => $procurementTotal, 'color' => '#4ca6ff'],
        ];
        
        return view('finance::dashboard', [
            'financeDashboard' => [
                'paid' => $paid,
                'unpaid' => $unpaid,
                'overdue' => $overdue,
                'invoice_total' => $invoiceTotal,
                'week_labels' => $labels->values(),
                'invoice_values' => $invoiceValues->values(),
                'paid_values' => $paidValues->values(),
                'recent_activity' => $recentActivity,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'expenses' => $expenses,
                'expenses_breakdown' => $expensesBreakdown,

                'cash_on_hand' => $cashOnHand,
                'cash_balance' => $cashBalance,
                'cash_inflow' => $cashInflow,
                'cash_outflow' => $cashOutflow,
            ],
        ]);
    }
}
