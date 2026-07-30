<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Account;
use Carbon\Carbon;

class CashFlowController extends Controller
{
    public function index() 
{
    $currentAssets = Account::where('account_type', 'Asset')
        ->sum('balance');

    $currentLiabilities = Account::where('account_type', 'Liability')
        ->sum('balance');

    $currentRatio = $currentLiabilities > 0
        ? $currentAssets / $currentLiabilities
        : 0;


    $data = [
        'cashOnHand' => 0,
        'cashInflow' => 0,
        'cashOutflow' => 0,
        'netCashFlow' => 0,
        'beginningCashBalance' => 0,

        // Current Ratio
        'currentAssets' => $currentAssets,
        'currentLiabilities' => $currentLiabilities,
        'currentRatio' => $currentRatio,

        'trend' => [
    'week' => [
        'labels' => [],
        'inflow' => [],
        'outflow' => [],
    ],

    'month' => [
        'labels' => [],
        'inflow' => [],
        'outflow' => [],
    ],

    'year' => [
        'labels' => [],
        'inflow' => [],
        'outflow' => [],
    ],
],

    ];

    try {

        // Total paid invoices = cash received
        $paidInvoices = (float) Invoice::query()
            ->whereRaw(
                'LOWER(COALESCE(payment_status, \'\')) = ?',
                ['paid']
            )
            ->sum('paid_amount');


        // Total procurement expenses
        $procurementExpenses = $this->procurementExpenses();


        // Current cash balance
        $currentBalance = 500000;


        $cashInflow = $paidInvoices;
        $cashOutflow = $procurementExpenses;
        $netCashFlow = $cashInflow - $cashOutflow;


        $beginningCashBalance = $currentBalance;


       $data = array_merge($data, [

            'cashOnHand' => $currentBalance + $netCashFlow,

            'cashInflow' => $cashInflow,

            'cashOutflow' => $cashOutflow,

            'netCashFlow' => $netCashFlow,

            'beginningCashBalance' => $beginningCashBalance,

            'trend' => $this->cashFlowTrend(),

        ]);


    } catch (\Throwable $e) {

        // Keep dashboard working
    }
    return view('finance::cashflowdash', $data);
}

  private function cashFlowTrend(): array
{

    return [
        'week' => $this->generateTrend(
            Carbon::now()->subDays(6),
            Carbon::now(),
            'day'
        ),

        'month' => $this->generateTrend(
            Carbon::now()->subDays(29),
            Carbon::now(),
            'day'
        ),

        'year' => $this->generateTrend(
            Carbon::now()->subMonths(11)->startOfMonth(),
            Carbon::now(),
            'month'
        ),
    ];

}


private function generateTrend($start, $end, $type)
{

    $labels = [];
    $inflow = [];
    $outflow = [];


    $period = $start->copy();


    while($period <= $end){


        if($type === 'day'){

            $labels[] = $period->format('M d');


            $received = Invoice::query()
                ->whereDate(
                    'payment_date',
                    $period->toDateString()
                )
                ->whereRaw(
                    'LOWER(COALESCE(payment_status, \'\')) = ?',
                    ['paid']
                )
                ->sum('paid_amount');


            $spent = $this->procurementByDate(
                $period
            );


            $period->addDay();

        }
        else {


            $labels[] = $period->format('M');


            $received = Invoice::query()
                ->whereYear(
                    'payment_date',
                    $period->year
                )
                ->whereMonth(
                    'payment_date',
                    $period->month
                )
                ->whereRaw(
                    'LOWER(COALESCE(payment_status, \'\')) = ?',
                    ['paid']
                )
                ->sum('paid_amount');


            $spent = $this->procurementByMonth(
                $period
            );


            $period->addMonth();

        }


        $inflow[] = (float)$received;
        $outflow[] = (float)$spent;

    }


    return [
        'labels'=>$labels,
        'inflow'=>$inflow,
        'outflow'=>$outflow
    ];

}
private function procurementByDate($date)
{

    if(
        !Schema::connection('procurement')
        ->hasTable('purchase_orders')
    ){
        return 0;
    }


    return DB::connection('procurement')
        ->table('purchase_orders')
        ->whereDate(
            'order_date',
            $date->toDateString()
        )
        ->whereIn(
            DB::raw("LOWER(COALESCE(status,''))"),
            [
                'approved',
                'processing',
                'delivered',
                'completed'
            ]
        )
        ->sum(
            DB::raw('COALESCE(amount,0)')
        );

}



private function procurementByMonth($date)
{

    if(
        !Schema::connection('procurement')
        ->hasTable('purchase_orders')
    ){
        return 0;
    }


    return DB::connection('procurement')
        ->table('purchase_orders')
        ->whereYear(
            'order_date',
            $date->year
        )
        ->whereMonth(
            'order_date',
            $date->month
        )
        ->whereIn(
            DB::raw("LOWER(COALESCE(status,''))"),
            [
                'approved',
                'processing',
                'delivered',
                'completed'
            ]
        )
        ->sum(
            DB::raw('COALESCE(amount,0)')
        );

}
    private function procurementExpenses(): float
    {
        $schema = Schema::connection('procurement');


        if (! $schema->hasTable('purchase_orders')) {
            return 0;
        }


        $query = DB::connection('procurement')
            ->table('purchase_orders')
            ->whereIn(
                DB::raw('LOWER(COALESCE(status, \'\'))'),
                [
                    'approved',
                    'processing',
                    'delivered',
                    'completed'
                ]
            );


        if (! $this->isRootAdmin()) {

            $clientId = session('employee_client_id');


            if (
                ! $clientId ||
                ! $schema->hasColumn('purchase_orders', 'client_id')
            ) {
                return 0;
            }


            $query->where(
                'client_id',
                $clientId
            );
        }


        $amount = $schema->hasColumn('purchase_orders', 'amount')
            ? 'COALESCE(amount, 0)'

            : (
                $schema->hasColumn('purchase_orders', 'qty')
                &&
                $schema->hasColumn('purchase_orders', 'unit_price')

                ? 'COALESCE(qty, 0) * COALESCE(unit_price, 0)'

                : '0'
            );


        return (float) $query
            ->selectRaw(
                "COALESCE(SUM({$amount}), 0) AS total"
            )
            ->value('total');
    }


    private function isRootAdmin(): bool
    {
        return config('nexora.root_admin_module_testing')
            &&
            auth()->user()?->role === 'root_admin';
    }
}