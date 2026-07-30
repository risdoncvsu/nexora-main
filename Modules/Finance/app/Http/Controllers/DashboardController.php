<?php

namespace Modules\Finance\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Account;
use Modules\Finance\Models\Invoice;
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
        app(StorefrontInvoiceSynchronizer::class)->syncForCurrentClient();

        $invoices = Invoice::query()
            ->orderByDesc('issue_date')
            ->orderByDesc('invoice_id')
            ->get();

        $today = Carbon::today();
        $paid = (float) $invoices
            ->filter(fn (Invoice $invoice): bool => strtolower((string) $invoice->payment_status) === 'paid')
            ->sum('paid_amount');
        $unpaid = (float) $invoices->sum('outstanding_amount');
        $overdue = (float) $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->due_date !== null
                && Carbon::parse($invoice->due_date)->lt($today)
                && (float) $invoice->outstanding_amount > 0)
            ->sum('outstanding_amount');
        $invoiceTotal = (float) $invoices->sum(fn (Invoice $invoice): float => (float) $invoice->paid_amount + (float) $invoice->outstanding_amount);

        $startOfWeek = $today->copy()->startOfWeek();
        $weeklyInvoices = $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->issue_date !== null && Carbon::parse($invoice->issue_date)->gte($startOfWeek));
        $weekLabels = collect(range(0, 6))->map(fn (int $day): string => $startOfWeek->copy()->addDays($day)->format('D'));
        $invoiceValues = $weekLabels->map(function (string $label, int $day) use ($weeklyInvoices, $startOfWeek): float {
            $date = $startOfWeek->copy()->addDays($day)->toDateString();

            return (float) $weeklyInvoices
                ->filter(fn (Invoice $invoice): bool => Carbon::parse($invoice->issue_date)->toDateString() === $date)
                ->sum(fn (Invoice $invoice): float => (float) $invoice->paid_amount + (float) $invoice->outstanding_amount);
        });
        $paidValues = $weekLabels->map(function (string $label, int $day) use ($weeklyInvoices, $startOfWeek): float {
            $date = $startOfWeek->copy()->addDays($day)->toDateString();

            return (float) $weeklyInvoices
                ->filter(fn (Invoice $invoice): bool => Carbon::parse($invoice->issue_date)->toDateString() === $date)
                ->sum('paid_amount');
        });

        $recentActivity = $invoices->take(8)->map(function (Invoice $invoice): array {
            $isPaid = strtolower((string) $invoice->payment_status) === 'paid';

            return [
                'date' => $invoice->issue_date?->format('M d, Y') ?? '—',
                'desc' => 'Invoice '.($invoice->reference_number ?: '#'.$invoice->invoice_id),
                'category' => 'E-Commerce order',
                'amount' => (float) $invoice->paid_amount + (float) $invoice->outstanding_amount,
                'status' => $isPaid ? 'Success' : 'Pending',
            ];
        })->values();

        $assets = $this->accountBalance('asset');
        $liabilities = $this->accountBalance('liability');
        $procurementExpenses = $this->procurementExpenses();
        $cashOnHand = max(0, $assets + $paid - $procurementExpenses);
        $netIncome = $paid - $procurementExpenses;
        $thisMonthPaid = (float) $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->issue_date !== null && Carbon::parse($invoice->issue_date)->isCurrentMonth())
            ->sum('paid_amount');
        $lastMonthPaid = (float) $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->issue_date !== null && Carbon::parse($invoice->issue_date)->isSameMonth(now()->subMonth()))
            ->sum('paid_amount');
        $revenueChangePct = $lastMonthPaid > 0
            ? round((($thisMonthPaid - $lastMonthPaid) / $lastMonthPaid) * 100)
            : ($thisMonthPaid > 0 ? 100 : 0);
        $equity = $assets - $liabilities;

        return view('finance::dashboard', [
            'financeDashboard' => [
                'paid' => $paid,
                'unpaid' => $unpaid,
                'overdue' => $overdue,
                'invoice_total' => $invoiceTotal,
                'week_labels' => $weekLabels->values(),
                'invoice_values' => $invoiceValues->values(),
                'paid_values' => $paidValues->values(),
                'recent_activity' => $recentActivity,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'cash_on_hand' => $cashOnHand,
                'cash_inflow' => $paid,
                'cash_outflow' => $procurementExpenses,
                'net_income' => $netIncome,
                'revenue_change_pct' => $revenueChangePct,
            ],
        ]);
    }

    /**
     * Procurement owns purchase orders. An unavailable or partially migrated
     * Procurement database must not hide valid Finance invoice data.
     */
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

    private function isRootAdmin(): bool
    {
        return config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
    }
}
