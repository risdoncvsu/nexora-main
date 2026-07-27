<?php

namespace Modules\OrderFulfillment\Models;

use Modules\OrderFulfillment\Models\Concerns\BelongsToClient;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use BelongsToClient;
    protected $table = 'shipments';

    protected $fillable = [
        'shipment_id',
        'order_id',
        'customer_name',
        'qty',
        'amount',
        'courier',
        'box_used',
        'tracking_number',
        'status',
        'address',
        'due_date',
        'shipped_at',
        'out_for_delivery_at',
    ];

    protected $casts = [
        'shipped_at'           => 'datetime',
        'out_for_delivery_at'  => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    protected static function booted(): void
    {
        // Requirement #6: the 1-day SHIPPED -> READY_TO_SHIP timer starts
        // the moment a shipment's status becomes SHIPPED.
        static::saving(function (Shipment $shipment) {
            if (
                $shipment->isDirty('status') &&
                strtoupper($shipment->status) === 'SHIPPED' &&
                ! $shipment->shipped_at
            ) {
                $shipment->shipped_at = now();
            }
        });

        // Keep the parent Order's status mirrored to its Shipment's status
        // (READY_TO_SHIP, OUT_FOR_DELIVERY, DELIVERED, etc). Without this,
        // the Orders and Shipping pages drift apart the moment a shipment
        // changes status anywhere other than the Orders page itself — e.g.
        // assigning a driver moves the shipment to OUT_FOR_DELIVERY but the
        // order was left showing READY_TO_SHIP. Fires on every save, no
        // matter where the status change comes from.
        static::updated(function (Shipment $shipment) {
            if ($shipment->wasChanged('status') && $shipment->order_id) {
                Order::where('id', $shipment->order_id)->update([
                    'status'     => strtoupper($shipment->status),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}
