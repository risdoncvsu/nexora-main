<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Models\CustomerNotification;
use Modules\Ecommerce\Support\EcommerceClientContext;

class CustomerNotificationController extends Controller
{
    public function unread(): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        $notifications = CustomerNotification::forClient($clientId)
            ->visibleTo((int) $user->id)
            ->unread()
            ->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $notifications->count(),
        ]);
    }

    public function markRead(int $id): JsonResponse
    {
        return $this->mark($id, false);
    }

    public function markAllRead(): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        CustomerNotification::forClient($clientId)
            ->visibleTo((int) $user->id)
            ->unread()
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Notifications marked as read.']);
    }

    private function mark(int $id, bool $all): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        CustomerNotification::forClient($clientId)
            ->visibleTo((int) $user->id)
            ->whereKey($id)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read.']);
    }
}
