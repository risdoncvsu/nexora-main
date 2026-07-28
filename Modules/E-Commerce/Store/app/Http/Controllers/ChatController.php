<?php

namespace Modules\Ecommerce\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Models\ChatMessage;
use Modules\Ecommerce\Models\CustomerNotification;
use Modules\Ecommerce\Support\EcommerceClientContext;

class ChatController extends Controller
{
    public function adminConversations(): JsonResponse
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        abort_unless($clientId > 0, 422, 'Storefront client could not be resolved.');

        return response()->json(['data' => ChatMessage::conversationsForClient($clientId)]);
    }

    public function adminMessages(int $userId): JsonResponse
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        abort_unless($clientId > 0, 422, 'Storefront client could not be resolved.');

        $messages = ChatMessage::forClient($clientId)->forUser($userId)->latest()->paginate(50);
        ChatMessage::forClient($clientId)->forUser($userId)->unread()
            ->where('sender_type', 'customer')->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function adminSend(Request $request, int $userId): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        abort_unless($clientId > 0, 422, 'Storefront client could not be resolved.');

        $message = ChatMessage::create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'sender_type' => 'admin',
            'sender_id' => auth('ecommerce_admin')->id(),
            'message' => $data['message'],
            'is_read' => false,
        ]);

        CustomerNotification::create([
            'client_id' => $clientId,
            'user_id' => $userId,
            'type' => 'chat',
            'title' => 'New message from store support',
            'body' => str($data['message'])->limit(120),
            'link' => route('ecommerce.account.profile').'#support',
            'icon' => 'ph-chats',
            'icon_color' => 'primary',
            'is_read' => false,
        ]);

        return response()->json(['data' => $message], 201);
    }

    public function adminPoll(Request $request, int $userId): JsonResponse
    {
        return $this->poll($request, $userId, 'customer');
    }

    public function customerMessages(): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        $messages = ChatMessage::forClient($clientId)->forUser((int) $user->id)->oldest()->get();
        ChatMessage::forClient($clientId)->forUser((int) $user->id)->where('sender_type', 'admin')->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['data' => $messages]);
    }

    public function customerSend(Request $request): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        $message = ChatMessage::create([
            'client_id' => $clientId, 'user_id' => $user->id, 'sender_type' => 'customer',
            'sender_id' => $user->id, 'message' => $data['message'], 'is_read' => false,
        ]);

        return response()->json(['data' => $message], 201);
    }

    public function customerPoll(Request $request): JsonResponse
    {
        $user = Auth::guard('ecommerce')->user();
        $clientId = (int) app(EcommerceClientContext::class)->clientId();

        if (! $user || $clientId < 1) {
            return response()->json(['message' => 'Authentication is required.'], 401);
        }

        return $this->poll($request, (int) $user->id, 'admin');
    }

    private function poll(Request $request, int $userId, string $readSender): JsonResponse
    {
        $clientId = (int) app(EcommerceClientContext::class)->clientId();
        $after = (string) $request->query('after', now()->subHour()->toIso8601String());

        $messages = ChatMessage::forClient($clientId)->forUser($userId)
            ->since($after)->oldest()->get();
        ChatMessage::forClient($clientId)->forUser($userId)->unread()
            ->where('sender_type', $readSender)->update(['is_read' => true]);

        return response()->json(['data' => $messages]);
    }
}
