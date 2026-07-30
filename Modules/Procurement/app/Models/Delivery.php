<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Models\Concerns\BelongsToClient;

class Delivery extends Model
{
    use BelongsToClient;

    protected $connection = 'procurement';

    protected $fillable = [
        'client_id', 'shipment_number', 'purchase_order_id', 'supplier_id', 'status',
        'qty', 'qty_expected', 'items', 'remarks', 'delivery_date',
        'estimated_arrival', 'actual_arrival', 'tracking_number', 'carrier',
        'deliver_to_warehouse',
    ];
    protected $casts = [
        'delivery_date' => 'date',
        'estimated_arrival' => 'date',
        'actual_arrival' => 'date',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}