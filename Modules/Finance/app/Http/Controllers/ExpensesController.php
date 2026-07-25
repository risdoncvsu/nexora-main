<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->string('range', '6months')->toString();
        $empty = [
            'expenseData' => ['categories' => [], 'budgetCap' => 0, 'months' => [], 'selectedRange' => 'LAST 6 MONTHS'],
            'expenses' => collect(), 'procurementTotal' => 0, 'overallExpenses' => 0,
            'procurementPercent' => 0, 'labels' => [], 'totals' => [], 'range' => $range,
        ];

        try {
            $schema = Schema::connection('procurement');
            if (! $schema->hasTable('purchase_orders')) {
                return view('finance::expensesdash', $empty);
            }

            $query = DB::connection('procurement')->table('purchase_orders');
            $isRootAdmin = config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
            if (! $isRootAdmin) {
                $clientId = session('employee_client_id');
                if (! $clientId || ! $schema->hasColumn('purchase_orders', 'client_id')) {
                    return view('finance::expensesdash', $empty);
                }
                $query->where('client_id', $clientId);
            }

            $from = match ($range) {
                'week' => now()->subDays(6),
                'month' => now()->subMonth(),
                'year' => now()->subYear(),
                default => now()->subMonths(6),
            };
            $dateFormat = match ($range) {
                'week' => "TO_CHAR(order_date, 'Dy')",
                'month' => "CONCAT('Week ', EXTRACT(WEEK FROM order_date)::int)",
                default => "TO_CHAR(order_date, 'YYYY-MM')",
            };

            // `amount` is already the total for a PO. Multiplying by qty again
            // double-counts multi-item orders.
            $expenses = (clone $query)->select('po_number', 'item', 'brand', 'qty', 'amount', 'status', 'created_at')->latest('id')->get();
            $procurementTotal = (float) (clone $query)->sum('amount');
            $monthly = (clone $query)->selectRaw("{$dateFormat} as month, COALESCE(SUM(amount), 0) as total")
                ->where('order_date', '>=', $from)->groupByRaw($dateFormat)->orderByRaw('MIN(order_date)')->get();
            $thisMonthExpenses = (float) (clone $query)->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount');

            $expenseData = [
                'budgetCap' => $procurementTotal,
                'months' => $monthly->pluck('month')->values()->all(),
                'selectedRange' => match ($range) { 'week' => 'LAST WEEK', 'month' => 'LAST MONTH', 'year' => 'LAST YEAR', default => 'LAST 6 MONTHS' },
                'categories' => [[
                    'key' => 'procurement', 'label' => 'Procurement', 'color' => '#4ca6ff',
                    'capacity' => $procurementTotal, 'value' => $thisMonthExpenses, 'prevValue' => 0,
                    'trend' => $monthly->pluck('total')->map(fn ($total) => (float) $total)->values()->all(),
                ]],
            ];

            return view('finance::expensesdash', [
                'expenseData' => $expenseData,
                'expenses' => $expenses,
                'procurementTotal' => $procurementTotal,
                'range' => $range,
                'overallExpenses' => $procurementTotal,
                'procurementPercent' => $procurementTotal > 0 ? 100 : 0,
                'labels' => $monthly->pluck('month')->all(),
                'totals' => $monthly->pluck('total')->map(fn ($total) => (float) $total)->all(),
            ]);
        } catch (\Throwable) {
            return view('finance::expensesdash', $empty);
        }
    }
}
