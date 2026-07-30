<?php

namespace Modules\Ecommerce\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    use BelongsToClient;
    use HasFactory;

    protected $table = 'return_requests';

    protected $fillable = [
        'order_id',
        'client_id',
        'user_id',
        'type',
        'reason',
        'condition',
        'status',
        'items_data',
        'refund_amount',
        'admin_notes',
        'resolved_at',
        'refunded_at',
    ];

    protected $casts = [
        'items_data' => 'array',
        'condition' => 'string',
        'refund_amount' => 'decimal:2',
        'resolved_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(\Modules\Ecommerce\Models\User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
