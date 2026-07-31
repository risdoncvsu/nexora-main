<?php

namespace Modules\Finance\Http\Controllers;

use Modules\Finance\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class InvoiceController extends Controller
{
    /**
     * Calculate dashboard trend.
     */
    private function calculateTrend($current, $previous)
    {
        if ($previous > 0) {
            $percent = (($current - $previous) / $previous) * 100;
        } else {
            $percent = $current > 0 ? 100 : 0;
        }

        return [
            'percent' => round($percent, 1),
            'trend'   => $percent >= 0 ? '↑' : '↓',
            'color'   => $percent >= 0
                ? 'text-emerald-400'
                : 'text-red-400',
        ];
    }
   private function getInvoiceValue(Invoice $invoice): float
{
    $subtotal = (float) $invoice->invoice_amount;

    $shipping = (float) ($invoice->shipping_fee ?? 0);

    $discount = (float) ($invoice->discount ?? 0);

    $vatRate = 0.12;

    $vat = $subtotal * $vatRate;

    return $subtotal + $shipping + $vat - $discount;
}

    /**
     * Calculate overdue balance.
     */
private function calculateOverdue(Collection $invoices): float
{
    return (float) $invoices->sum(function ($invoice) {

        $isOverdue =
            Carbon::parse($invoice->due_date)->isPast()
            &&
            $invoice->outstanding_amount > 0;

        return $isOverdue
            ? $invoice->outstanding_amount
            : 0;

    });
}

    /**
     * Dashboard
     */
   public function index()
{

    $invoices = Invoice::with('order')
    ->latest('issue_date')
    ->get();


    $currentMonth = Carbon::now();
$lastMonth = Carbon::now()->subMonth();


$currentInvoices = $invoices->filter(function ($invoice) use ($currentMonth) {

    return Carbon::parse($invoice->issue_date)->year == $currentMonth->year
        && Carbon::parse($invoice->issue_date)->month == $currentMonth->month;

});


$lastInvoices = $invoices->filter(function ($invoice) use ($lastMonth) {

    return Carbon::parse($invoice->issue_date)->year == $lastMonth->year
        && Carbon::parse($invoice->issue_date)->month == $lastMonth->month;

});
        /*
        |--------------------------------------------------------------------------
        | Current / Previous Month Queries
        |--------------------------------------------------------------------------
        */
        /*
|--------------------------------------------------------------------------
| Total Invoice Value
|--------------------------------------------------------------------------
*/

$currentTotal = $currentInvoices->sum('outstanding_amount')
    + $currentInvoices->sum('paid_amount');

$lastTotal = $lastInvoices->sum('outstanding_amount')
    + $lastInvoices->sum('paid_amount');

$totalStats = $this->calculateTrend(
    $currentTotal,
    $lastTotal
);
       /*
|--------------------------------------------------------------------------
| Paid
|--------------------------------------------------------------------------
*/

$currentPaid = $currentInvoices
    ->where('payment_status', 'Paid')
    ->sum('paid_amount');

$lastPaid = $lastInvoices
    ->where('payment_status', 'Paid')
    ->sum('paid_amount');

$paidStats = $this->calculateTrend(
    $currentPaid,
    $lastPaid
);

    /*
|--------------------------------------------------------------------------
| Pending
|--------------------------------------------------------------------------
*/

$currentPending = $currentInvoices
    ->where('status', 'Pending')
    ->sum('outstanding_amount');

$lastPending = $lastInvoices
    ->where('status', 'Pending')
    ->sum('outstanding_amount');

$pendingStats = $this->calculateTrend(
    $currentPending,
    $lastPending
);

      /*
|--------------------------------------------------------------------------
| Overdue
|--------------------------------------------------------------------------
*/

$currentOverdue = $this->calculateOverdue(
    $currentInvoices
);

$lastOverdue = $this->calculateOverdue(
    $lastInvoices
);

$overdueStats = $this->calculateTrend(
    $currentOverdue,
    $lastOverdue
);

        return view('finance::invoicedash', [

            'invoices' => $invoices,

            'currentTotal' => $currentTotal,
            'lastTotal' => $lastTotal,
            'percent' => $totalStats['percent'],
            'trend' => $totalStats['trend'],
            'color' => $totalStats['color'],

            'currentPaid' => $currentPaid,
            'lastPaid' => $lastPaid,
            'paidPercent' => $paidStats['percent'],
            'paidTrend' => $paidStats['trend'],
            'paidColor' => $paidStats['color'],

            'currentPending' => $currentPending,
            'lastPending' => $lastPending,
            'pendingPercent' => $pendingStats['percent'],
            'pendingTrend' => $pendingStats['trend'],
            'pendingColor' => $pendingStats['color'],

            'currentOverdue' => $currentOverdue,
            'lastOverdue' => $lastOverdue,
            'overduePercent' => $overdueStats['percent'],
            'overdueTrend' => $overdueStats['trend'],
            'overdueColor' => $overdueStats['color'],
        ]);
    }


   /**
 * Update invoice payment information
 */
public function update(Request $request, Invoice $invoice)
{
    $validated = $request->validate([


        'paid_amount' => 'required|numeric|min:0',

        'payment_method' => 'nullable|string|max:100',
        'payment_details' => 'nullable|string',
        'reference_number' => 'nullable|string|max:100',

    ]);

    // Invoice total
    $subtotal = (float) $invoice->invoice_amount;

$shipping = (float) ($invoice->shipping_fee ?? 0);

$discount = (float) ($invoice->discount ?? 0);

// Same VAT used by the Blade (12%)
$vatRate = 0.12;

$vat = $subtotal * $vatRate;

$grandTotal = (float) $request->grand_total;

    // Prevent overpayment
    if ($validated['paid_amount'] > $grandTotal) {

        return back()->withErrors([
            'paid_amount' => 'Paid amount cannot exceed the grand total.'
        ]);

    }

    $paid = (float) $validated['paid_amount'];

    $outstanding = max(0, $grandTotal - $paid);

    /*
    |--------------------------------------------------------------------------
    | Automatically determine payment status
    |--------------------------------------------------------------------------
    */

    if ($paid <= 0) {

        $paymentStatus = 'Unpaid';

    } elseif ($paid < $grandTotal) {

        $paymentStatus = 'Pending';

    } else {

        $paymentStatus = 'Paid';

    }

    /*
    |--------------------------------------------------------------------------
    | Automatically determine invoice status
    |--------------------------------------------------------------------------
    */

    if ($paymentStatus === 'Paid') {

    $invoiceStatus = 'Paid';

} else {

    $invoiceStatus = 'Pending';

}

    $invoice->update([

        'status' => $invoiceStatus,

        'payment_status' => $paymentStatus,

        'paid_amount' => $paid,

        'outstanding_amount' => $outstanding,

        'payment_method' => $validated['payment_method'],

        'payment_details' => $validated['payment_details'],

        'reference_number' => $validated['reference_number'],

    ]);

    return back()->with(
        'success',
        'Invoice updated successfully.'
    );
}

    /**
     * Reject invoice
     */
   public function reject(Invoice $invoice)
    {
        $invoice->update([
            'status' => 'Rejected',
            'payment_status' => 'Rejected',
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
