<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Account;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->string('range', '6months')->toString();
        $empty = $this->emptyViewData($range);

        try {
            $schema = Schema::connection('procurement');
            if (! $schema->hasTable('purchase_orders')) {
                return view('finance::expensesdash', $empty);
            }

            $query = $this->purchaseOrders($schema);
            $amountColumn = $schema->hasColumn('purchase_orders', 'amount') ? 'amount' : null;
            $hasUnitPrice = $schema->hasColumn('purchase_orders', 'unit_price');
            $hasQuantity = $schema->hasColumn('purchase_orders', 'qty');
            $amountExpression = $amountColumn
                ? 'COALESCE(amount, 0)'
                : ($hasUnitPrice && $hasQuantity ? 'COALESCE(qty, 0) * COALESCE(unit_price, 0)' : '0');
            $unitPriceExpression = $hasUnitPrice
                ? 'COALESCE(unit_price, 0)'
                : ($amountColumn && $hasQuantity ? 'CASE WHEN COALESCE(qty, 0) = 0 THEN 0 ELSE COALESCE(amount, 0) / qty END' : '0');

            $approved = (clone $query)->whereIn(DB::raw('LOWER(COALESCE(status, \'\'))'), ['approved', 'processing', 'delivered', 'completed']);
            $thisMonthExpenses = (float) (clone $approved)
                ->whereBetween('order_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->selectRaw("COALESCE(SUM({$amountExpression}), 0) AS total")
                ->value('total');
            $previousMonth = now()->subMonth();
            $previousMonthExpense = (float) (clone $approved)
                ->whereMonth('order_date', $previousMonth->month)
                ->whereYear('order_date', $previousMonth->year)
                ->selectRaw("COALESCE(SUM({$amountExpression}), 0) AS total")
                ->value('total');
            $procurementTotal = (float) (clone $approved)->selectRaw("COALESCE(SUM({$amountExpression}), 0) AS total")->value('total');
            $liabilityTotal = (float) Account::query()->where('account_type', 'Liability')->sum('balance');

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
            $monthly = (clone $approved)
                ->where('order_date', '>=', $from)
                ->selectRaw("{$dateFormat} AS month, COALESCE(SUM({$amountExpression}), 0) AS total")
                ->groupByRaw($dateFormat)
                ->orderByRaw('MIN(order_date)')
                ->get();
            $materialRequests = (clone $query)
                ->select('id', 'po_number', 'item', 'brand', 'qty', 'status')
                ->selectRaw("{$unitPriceExpression} AS unit_price")
                ->latest('id')
                ->get();
            $statusCounts = (clone $query)
                ->selectRaw("LOWER(COALESCE(status, 'pending')) AS status, COUNT(*) AS total")
                ->groupByRaw("LOWER(COALESCE(status, 'pending'))")
                ->pluck('total', 'status');
            $overallExpenses = $procurementTotal + $liabilityTotal;

            return view('finance::expensesdash', [
                'expenseData' => [
                    'expenseThisMonth' => $thisMonthExpenses + $this->monthlyLiabilities(now()),
                    'previousMonthExpense' => $previousMonthExpense + $this->monthlyLiabilities($previousMonth),
                    'expenseAllTime' => $overallExpenses,
                    'budgetCap' => $procurementTotal,
                    'months' => $monthly->pluck('month')->values()->all(),
                    'selectedRange' => match ($range) { 'week' => 'LAST WEEK', 'month' => 'LAST MONTH', 'year' => 'LAST YEAR', default => 'LAST 6 MONTHS' },
                    'categories' => [
                        ['key' => 'procurement', 'label' => 'Procurement', 'color' => '#4ca6ff', 'capacity' => $overallExpenses, 'value' => $procurementTotal, 'trend' => $monthly->pluck('total')->map(fn($v) => (float)$v)->values()->all()],
                        ['key' => 'liability', 'label' => 'Liabilities', 'color' => '#ef4444', 'capacity' => $overallExpenses, 'value' => $liabilityTotal, 'trend' => array_fill(0, max(1, count($monthly)), $liabilityTotal)],
                    ],
                ],
                'materialRequests' => $materialRequests,
                'pendingCount' => (int) ($statusCounts['pending'] ?? 0),
                'approvedCount' => (int) ($statusCounts['approved'] ?? 0),
                'processingCount' => (int) ($statusCounts['processing'] ?? 0),
                'deliveredCount' => (int) ($statusCounts['delivered'] ?? 0),
                'completedCount' => (int) ($statusCounts['completed'] ?? 0),
                'rejectedCount' => (int) ($statusCounts['rejected'] ?? 0),
                'cancelledCount' => (int) ($statusCounts['cancelled'] ?? 0),
                'range' => $range,
            ]);
        } catch (\Throwable) {
            return view('finance::expensesdash', $empty);
        }
    }

    public function updateStatus(Request $request, int $id)
    {
        $data = $request->validate(['status' => ['required', 'in:approved,rejected']]);
        $schema = Schema::connection('procurement');
        abort_unless($schema->hasTable('purchase_orders'), 404);

        $values = ['status' => ucfirst($data['status'])];
        if ($schema->hasColumn('purchase_orders', 'updated_at')) {
            $values['updated_at'] = now();
        }

        $changed = $this->purchaseOrders($schema)
            ->where('id', $id)
            ->whereRaw("LOWER(COALESCE(status, '')) = ?", ['pending'])
            ->update($values);
        abort_unless($changed === 1, 404);

        return response()->json(['success' => true]);
    }

    private function purchaseOrders($schema): Builder
    {
        $query = DB::connection('procurement')->table('purchase_orders');
        $isRootAdmin = config('nexora.root_admin_module_testing') && auth()->user()?->role === 'root_admin';
        if (! $isRootAdmin) {
            $clientId = session('employee_client_id');
            if (! $clientId || ! $schema->hasColumn('purchase_orders', 'client_id')) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where('client_id', $clientId);
            }
        }

        return $query;
    }

    private function monthlyLiabilities($date): float
    {
        return (float) Account::query()
            ->where('account_type', 'Liability')
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->sum('balance');
    }

    private function emptyViewData(string $range): array
    {
        return [
            'expenseData' => ['categories' => [], 'budgetCap' => 0, 'expenseThisMonth' => 0, 'previousMonthExpense' => 0, 'expenseAllTime' => 0, 'months' => [], 'selectedRange' => 'LAST 6 MONTHS'],
            'materialRequests' => collect(), 'pendingCount' => 0, 'approvedCount' => 0, 'processingCount' => 0,
            'deliveredCount' => 0, 'completedCount' => 0, 'rejectedCount' => 0, 'cancelledCount' => 0, 'range' => $range,
        ];
    }
}
