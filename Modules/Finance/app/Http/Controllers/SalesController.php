<?php

namespace Modules\Finance\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Finance\Models\Invoice;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->string('range', 'week')->toString();
        $range = in_array($range, ['week', 'last_week', 'month', 'year'], true) ? $range : 'week';
        $allTimeSales = (float) Invoice::query()
    ->whereRaw("LOWER(COALESCE(payment_status,'')) = ?", ['paid'])
    ->sum('paid_amount');
        try {
            $query = Invoice::query()->with('order');

            match ($range) {
                'last_week' => $query->whereBetween('issue_date', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()]),
                'month' => $query->whereMonth('issue_date', now()->month)->whereYear('issue_date', now()->year),
                'year' => $query->whereYear('issue_date', now()->year),
                default => $query->whereBetween('issue_date', [now()->startOfWeek(), now()->endOfWeek()]),
            };

            // Invoice is client-scoped by its model, so every calculation below
            // remains limited to the signed-in employee's company.
            $paidInvoices = $query
                ->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', ['paid'])
                ->get();

            $totalSales = (float) $paidInvoices->sum('paid_amount');
            $products = [];

            foreach ($paidInvoices as $invoice) {
                $order = $invoice->order;
                $name = trim((string) ($order?->product_name ?: 'Order #'.$invoice->order_id));
                $products[$name] = ($products[$name] ?? 0) + (float) $invoice->paid_amount;
            }

            arsort($products);
            $topProducts = collect($products)
                ->map(fn (float $value, string $name): array => ['name' => $name, 'value' => $value])
                ->values()
                ->take(5)
                ->values();

            $revenueStreams = $topProducts
                ->groupBy(fn (array $product): string => $this->streamForProduct($product['name']))
                ->map(fn ($products, string $label): array => ['label' => $label, 'value' => (float) $products->sum('value')])
                ->values();
            $trendValues = $this->trendValues($paidInvoices, $range);

            return view('finance::salesdash', [
                'totalSales' => $totalSales,          // filtered
                'allTimeSales' => $allTimeSales,      // never filtered
                'topProducts' => $topProducts,
                'revenueStreams' => $revenueStreams,
                'trendValues' => $trendValues,
                'rangeLabel' => match ($range) {
                    'last_week' => 'Last week',
                    'month' => 'This month',
                    'year' => 'This year',
                    default => 'This week',
                },
            ]);
        } catch (\Throwable) {
            return view('finance::salesdash', [
                'totalSales' => 0,
                'topProducts' => collect(),
                'revenueStreams' => collect(),
                'trendValues' => [],
                'rangeLabel' => 'This week',
            ]);
        }
    }

    private function streamForProduct(string $name): string
    {
        $name = strtolower($name);

        return match (true) {
            str_contains($name, 'laptop') || str_contains($name, 'computer') => 'Computers',
            str_contains($name, 'rtx'), str_contains($name, 'graphics'), str_contains($name, 'gpu') => 'Components',
            str_contains($name, 'monitor'), str_contains($name, 'display') => 'Displays',
            str_contains($name, 'router'), str_contains($name, 'wifi') => 'Networking',
            str_contains($name, 'speaker'), str_contains($name, 'keyboard'), str_contains($name, 'mouse') => 'Accessories',
            default => 'Other',
        };
    }

    private function trendValues($invoices, string $range): array
    {
        if ($range === 'year') {
            return collect(range(1, 12))->map(fn (int $month): float => (float) $invoices
                ->filter(fn (Invoice $invoice): bool => $invoice->issue_date?->month === $month)
                ->sum('paid_amount'))->all();
        }

        if ($range === 'month') {
            return collect(range(0, 3))->map(function (int $week): float {
                $start = now()->startOfMonth()->addWeeks($week);
                $end = $week === 3 ? now()->endOfMonth() : $start->copy()->endOfWeek();

                return (float) Invoice::query()
                    ->whereRaw('LOWER(COALESCE(payment_status, \'\')) = ?', ['paid'])
                    ->whereBetween('issue_date', [$start, $end])
                    ->sum('paid_amount');
            })->all();
        }

        $start = $range === 'last_week' ? now()->subWeek()->startOfWeek() : now()->startOfWeek();

        return collect(range(0, 6))->map(fn (int $day): float => (float) $invoices
            ->filter(fn (Invoice $invoice): bool => $invoice->issue_date?->isSameDay($start->copy()->addDays($day)) ?? false)
            ->sum('paid_amount'))->all();
    }
}
