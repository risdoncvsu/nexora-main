<?php

namespace Modules\OrderFulfillment\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\OrderFulfillment\Helpers\OrderCode;
use Modules\OrderFulfillment\Models\Order;

class ActivityController extends Controller
{
    /**
     * GET /activity/recent
     *
     * Polled every 8s by dashboard.blade.php, order.blade.php, and
     * shipping.blade.php to prepend anything new since their last poll.
     *
     * Uses Modules\OrderFulfillment\Models\Order (not App\Models\Order),
     * since that's the one with the BelongsToClient global scope — without
     * it this endpoint returned every client's activity to every employee.
     *
     * Only polls on `updated_at`, since there's no separate
     * order_status_logs table yet — good enough for "did something
     * change recently", but it can't say what the status changed
     * *from*. If richer messages are needed later ("moved from
     * PACKING to SHIPPED"), that table should be written to in
     * OrderController::prepare()/cancel() and
     * ShippingController::assignDriver().
     */
    public function recent(Request $request): JsonResponse
    {
        $since = $request->query('since')
            ? Carbon::parse($request->query('since'))
            : now()->subMinutes(5);

        $changed = Order::where('updated_at', '>', $since)
            ->orderBy('updated_at')
            ->get();

        $items = $changed->map(function ($order) {
            [$icon, $message] = $this->describe($order);

            return [
                'id'      => $order->id . '-' . $order->status,
                'type'    => strtoupper($order->status) === 'NEW' ? 'alert' : 'activity',
                'icon'    => $icon,
                'message' => $message,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'now'   => now()->toISOString(),
        ]);
    }

    /**
     * Status -> [icon, message]. Mirrors the mapping already used by
     * OrderController::index()'s $recentActivity and
     * DashboardController::index()'s $activity — keep all three in
     * sync if a status is added or renamed.
     */
    private function describe(Order $order): array
    {
        $code = OrderCode::format($order->order_number);

        switch (strtoupper($order->status)) {
            case 'NEW':
                return ['📦', "Order {$code} was received"];
            case 'PACKING':
                return ['📦', "Order {$code} moved to packing"];
            case 'READY_TO_SHIP':
                return ['📬', "Order {$code} is ready for delivery"];
            case 'SHIPPED':
                return ['🚚', "Order {$code} has been shipped"];
            case 'OUT_FOR_DELIVERY':
                return ['🚛', "Order {$code} is out for delivery"];
            case 'DELIVERED':
                return ['✅', "Order {$code} has been delivered"];
            case 'CANCELLED':
                return ['❌', "Order {$code} has been cancelled"];
            default:
                return ['📈', "Order {$code} is now " . strtolower($order->status)];
        }
    }
}
