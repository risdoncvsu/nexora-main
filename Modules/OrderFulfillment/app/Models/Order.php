<?php

namespace Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\OrderFulfillment\Helpers\OrderCode;
use Modules\OrderFulfillment\Models\Concerns\BelongsToClient;

class Order extends Model
{
    use HasFactory, BelongsToClient;

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = ['id'];

    protected $casts = [
        'order_number' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    /**
     * Short, sequential "ORD-001" label for humans (phone/verbal reference,
     * table columns, modal headers). The UUID in `id` stays the real
     * primary/foreign key everywhere else in the code.
     */
    public function getOrderCodeAttribute()
    {
        return OrderCode::format($this->order_number);
    }

    /**
     * Sum of qty * product_amount across all items on this order.
     */
    public function getTotalAttribute()
    {
        return $this->items->sum(fn ($item) => $item->qty * $item->product_amount);
    }

    /**
     * Total unit count across all items on this order.
     */
    public function getTotalQtyAttribute()
    {
        return $this->items->sum('qty');
    }

    /**
     * First product name, plus a "+N more" suffix when there are
     * multiple distinct products on the order. Used anywhere the UI
     * only has room for a single product string (table rows, etc).
     */
    public function getProductSummaryAttribute()
    {
        $items = $this->items;

        if ($items->isEmpty()) {
            return '—';
        }

        $first = $items->first()->product_name;

        return $items->count() > 1
            ? $first . ' +' . ($items->count() - 1) . ' more'
            : $first;
    }
}