<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Procurement\Models\Concerns\BelongsToClient;

class Supplier extends Model
{
    use BelongsToClient;

    protected $connection = 'procurement';

    protected $fillable = [
        'client_id',
        'name', 'contact_person', 'email', 'phone', 'address',
        'badge_color', 'status', 'brand', 'product_items', 'warehouse_id',
    ];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function deliveries()
    {
        return $this->hasMany(Delivery::class);
    }

    public function products()
    {
        return $this->hasMany(SupplierProduct::class);
    }
}