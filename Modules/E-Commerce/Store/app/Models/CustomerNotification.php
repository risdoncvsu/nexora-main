<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CustomerNotification extends Model
{
    protected $connection = 'ecommerce';

    protected $fillable = [
        'client_id', 'user_id', 'type', 'title', 'body', 'link', 'icon', 'icon_color', 'is_read', 'read_at',
    ];

    protected function casts(): array
    {
        return ['is_read' => 'boolean', 'read_at' => 'datetime'];
    }

    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeVisibleTo(Builder $query, int $userId): Builder
    {
        return $query->where(fn (Builder $visible) => $visible->whereNull('user_id')->orWhere('user_id', $userId));
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }
}
