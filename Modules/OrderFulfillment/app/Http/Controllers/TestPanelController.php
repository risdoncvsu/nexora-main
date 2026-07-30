<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\OrderFulfillment\Models\Order;
use Modules\OrderFulfillment\Models\ReturnItem;
use Modules\OrderFulfillment\Models\Shipment;

/**
 * DEMO / PRESENTATION TOOL ONLY.
 *
 * Lets you force any Order, Shipment, or Return straight to any status
 * with one click, so you can show every badge/tier/flow during a demo
 * without having to walk each record through its real lifecycle
 * (packing -> shipping -> delivery, etc).
 *
 * This intentionally BYPASSES the normal business rules that the real
 * controllers enforce (e.g. OrderController::cancel blocking cancellation
 * after delivery, ShippingController's non-cancellable statuses, or
 * ReturnController's Pending-only guard on accept/decline). That's the point
 * for a test panel, but it also means this must never be reachable in
 * production — see the route registration notes in routes/web.php.
 */
class TestPanelController extends Controller
{
    public const ORDER_STATUSES = [
        'NEW', 'PACKING', 'READY_TO_SHIP', 'SHIPPED',
        'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETE', 'DELAYED', 'CANCELLED', 'RETURNED', 'REFUNDED',
    ];

    public const SHIPMENT_STATUSES = [
        'SHIPPED', 'READY_TO_SHIP', 'OUT_FOR_DELIVERY', 'DELAYED', 'DELIVERED', 'COMPLETE', 'CANCELLED',
    ];

    public const RETURN_STATUSES = [
        'Pending', 'Inspecting', 'In Transit to Warehouse', 'Refunded', 'Completed', 'Declined',
    ];

    public function index()
    {
        $orders = Order::orderByDesc('updated_at')->get();
        $shipments = Shipment::orderByDesc('updated_at')->get();
        $returns = ReturnItem::orderByDesc('updated_at')->get();

        return view('order-fulfillment::test-panel', [
            'orders'             => $orders,
            'shipments'          => $shipments,
            'returns'            => $returns,
            'orderStatuses'      => self::ORDER_STATUSES,
            'shipmentStatuses'   => self::SHIPMENT_STATUSES,
            'returnStatuses'     => self::RETURN_STATUSES,
            'adminCancelReasons' => self::ADMIN_CANCEL_REASONS,
        ]);
    }

    /**
     * POST /test-panel/orders/{id}/status
     * Force an Order straight to any status, no lifecycle checks.
     */
    public function updateOrder(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:' . implode(',', self::ORDER_STATUSES),
        ]);

        $order = Order::find($id);

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Order not found.'], 404);
        }

        $update = ['status' => $data['status'], 'updated_at' => now()];
        if ($data['status'] === 'DELIVERED' && ! $order->delivered_at) {
            $update['delivered_at'] = now();
        } elseif (! in_array($data['status'], ['DELIVERED', 'COMPLETE'], true)) {
            $update['delivered_at'] = null;
        }

        $order->update($update);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    /**
     * POST /test-panel/shipments/{shipmentId}/status
     * Force a Shipment straight to any status. Note: Shipment::booted()'s
     * `updated` hook still fires here, so the parent Order will keep
     * mirroring this status automatically, same as in real usage.
     */
    public function updateShipment(Request $request, string $shipmentId): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|string|in:' . implode(',', self::SHIPMENT_STATUSES),
        ]);

        $shipment = Shipment::where('shipment_id', $shipmentId)->first();

        if (! $shipment) {
            return response()->json(['success' => false, 'message' => 'Shipment not found.'], 404);
        }

        $shipment->update(['status' => $data['status']]);

        return response()->json(['success' => true, 'status' => $shipment->status]);
    }

    /**
     * POST /test-panel/returns/{id}/status
     * Force a Return straight to any status/resolution pair, skipping the
     * Pending-only guard that ReturnController::accept()/decline() enforce.
     */
    /**
     * Reasons that ReturnController/return.blade.php treat as an admin
     * cancellation rather than a genuine customer return request — kept
     * in sync with ReturnController::ADMIN_CANCEL_REASONS. A return with
     * one of these reasons will NEVER show Accept/Decline, no matter what
     * status it's in, so the test panel needs to be able to clear it.
     */
    public const ADMIN_CANCEL_REASONS = [
        'Cancelled while shipping',
        'Cancelled before shipping',
    ];

    public function updateReturn(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'status'     => 'required|string|in:' . implode(',', self::RETURN_STATUSES),
            'resolution' => 'nullable|string|max:255',
            'reason'     => 'nullable|string|max:255',
        ]);

        $return = ReturnItem::find($id);

        if (! $return) {
            return response()->json(['success' => false, 'message' => 'Return not found.'], 404);
        }

        $return->update([
            'status'     => $data['status'],
            'resolution' => $data['resolution'] ?? $return->resolution,
            'reason'     => $data['reason'] ?? $return->reason,
            'updated_at' => now(),
        ]);

        return response()->json([
            'success'    => true,
            'status'     => $return->status,
            'resolution' => $return->resolution,
            'reason'     => $return->reason,
        ]);
    }
}
