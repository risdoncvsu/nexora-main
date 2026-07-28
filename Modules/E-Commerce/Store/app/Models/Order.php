<?php

namespace Modules\Ecommerce\Models;

use Modules\Ecommerce\Models\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use BelongsToClient;
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'user_id', 'status', 'total', 'shipping_fee', 
        'payment_method', 'payment_status', 'shipping_address', 'tracking_number'
    ];

    protected $casts = [
        'shipping_address' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        // Recalculate CRM customer stats and tier when an order is placed.
        static::created(function ($model) {
            try {
                \Modules\Ecommerce\CRM\Models\Customer::recalculateForUser($model->user_id);
            } catch (\Throwable $e) {
                \Log::warning('Failed to recalculate CRM stats after order '.$model->id.': '.$e->getMessage());
            }
        });

        // Recalculate when order status changes (e.g. cancelled → refund, or processing → completed).
        static::updated(function ($model) {
            if ($model->isDirty(['status', 'payment_status'])) {
                try {
                    \Modules\Ecommerce\CRM\Models\Customer::recalculateForUser($model->user_id);
                } catch (\Throwable $e) {
                    \Log::warning('Failed to recalculate CRM stats after order status change '.$model->id.': '.$e->getMessage());
                }
            }
        });
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
