<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\OrderFulfillment\Helpers\OrderCode;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\ReturnItem;

class ReturnController extends Controller
{
    public function index()
    {
        // Auto-promotion through the post-approval lifecycle (In Transit
        // to Warehouse -> Inspecting -> Refunded -> Completed) is handled
        // by the returns:progress-lifecycle scheduled command instead of
        // recalculating here, so it keeps running — and keeps the parent
        // Order synced via ReturnItem::booted() — even when nobody has
        // the Returns tab open.
        $returns = ReturnItem::all();

        // The Returns table/modal show the parent order's short ORD-001
        // code, not its raw UUID. returns.order_id already holds the real
        // order id (used for the Order sync in ReturnItem::booted()) — this
        // just resolves the matching order_number for display.
        $orderNumbersById = Order::whereIn('id', $returns->pluck('order_id')->filter()->unique()->values())
            ->pluck('order_number', 'id');

        $returns->each(function (ReturnItem $return) use ($orderNumbersById) {
            $return->order_code = OrderCode::format($orderNumbersById->get($return->order_id));
        });

        $pendingReturns = ReturnItem::where('status', 'Pending')->count();

        $refundedToday = ReturnItem::whereDate(
            'updated_at',
            today()
        )->where('status', 'Refunded')
         ->count();

        return view('order-fulfillment::return', compact(
            'returns',
            'pendingReturns',
            'refundedToday'
        ));
    }

    /**
     * AJAX: accept a customer-initiated return request — moves it from
     * Pending (awaiting review) to In Transit to Warehouse, the same
     * status admin-cancellation returns start at. From here it's picked
     * up by the same returns:progress-lifecycle promotion chain
     * (-> Inspecting -> Refunded -> Completed) as any other return.
     * Admin-cancellation returns are never created at Pending (they start
     * at 'In Transit to Warehouse' already), so this only ever applies to
     * genuine return requests, matching why return_blade.php hides the
     * Accept button for the admin-cancel reasons.
     *
     * POST /returns/{id}/accept
     */
    public function accept($id): JsonResponse
    {
        $return = ReturnItem::find($id);

        if (!$return) {
            return response()->json([
                'success' => false,
                'message' => 'Return not found.',
            ], 404);
        }

        if ($return->status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Return is already ' . $return->status . '.',
            ], 409);
        }

        $return->update([
            'status'     => 'In Transit to Warehouse',
            'resolution' => 'Pending',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'status'     => $return->status,
            'resolution' => $return->resolution,
        ]);
    }

    /**
     * AJAX: reject a customer-initiated return request — moves it from
     * Pending (awaiting review) to Declined. Same Pending-only guard and
     * admin-cancel exclusion as accept(), just the opposite outcome.
     *
     * POST /returns/{id}/decline
     */
    public function decline($id): JsonResponse
    {
        $return = ReturnItem::find($id);

        if (!$return) {
            return response()->json([
                'success' => false,
                'message' => 'Return not found.',
            ], 404);
        }

        if ($return->status !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Return is already ' . $return->status . '.',
            ], 409);
        }

        $return->update([
            'status'     => 'Declined',
            'resolution' => 'Declined',
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'status'     => $return->status,
            'resolution' => $return->resolution,
        ]);
    }

    /**
     * AJAX: move a return to any of its allowed statuses (Inspecting,
     * Refunded, Completed, ...). Generic on purpose so future Returns-tab
     * actions (a "Mark refunded" button, etc.) can reuse this one endpoint
     * instead of each getting their own bespoke route + controller method.
     *
     * Syncing the parent Order's status happens automatically via
     * ReturnItem::booted() whenever the update below actually changes
     * status — no need to call anything else here.
     *
     * POST /returns/{id}/status
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $return = ReturnItem::find($id);

        if (!$return) {
            return response()->json([
                'success' => false,
                'message' => 'Return not found.',
            ], 404);
        }

        $validated = $request->validate([
            'status'     => 'required|string|in:Pending,Inspecting,In Transit to Warehouse,Refunded,Completed,Declined',
            'resolution' => 'nullable|string|max:255',
        ]);

        $return->update([
            'status'     => $validated['status'],
            'resolution' => $validated['resolution'] ?? $return->resolution,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'status'     => $return->status,
            'resolution' => $return->resolution,
        ]);
    }
}
