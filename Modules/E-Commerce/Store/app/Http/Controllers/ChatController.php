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
}
