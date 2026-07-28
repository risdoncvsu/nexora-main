<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $connection = 'ecommerce';

    protected $fillable = ['client_id', 'user_id', 'sender_type', 'sender_id', 'message', 'is_read'];

    protected function casts(): array
    {
        return ['is_read' => 'boolean'];
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeSince(Builder $query, string $timestamp): Builder
    {
        return $query->where('created_at', '>', $timestamp);
    }

    /** Return a compact, client-scoped inbox for the ecommerce admin. */
    public static function conversationsForClient(int $clientId): array
    {
        $latest = static::query()
            ->selectRaw('DISTINCT ON (user_id) user_id, message, sender_type, created_at')
            ->where('client_id', $clientId)
            ->orderBy('user_id')
            ->orderByDesc('created_at')
            ->get();

        return $latest->map(function (self $message) use ($clientId): array {
            $user = User::query()->find($message->user_id);

            return [
                'user_id' => (int) $message->user_id,
                'customer_name' => $user?->name ?: ($user?->first_name ?: 'Customer #'.$message->user_id),
                'last_message' => $message->message,
                'last_message_at' => $message->created_at,
                'last_sender_type' => $message->sender_type,
                'unread_count' => static::forClient($clientId)
                    ->forUser((int) $message->user_id)
                    ->unread()
                    ->where('sender_type', 'customer')
                    ->count(),
            ];
        })->sortByDesc('last_message_at')->values()->all();
    }
}
