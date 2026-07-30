<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referenced by PurchaseOrder::items(). The class was missing, so touching that
 * relation raised "Class not found" — the controllers only ever queried the
 * table through the query builder, which is why it went unnoticed.
 *
 * Rows are reached through their parent purchase order (which is client
 * scoped), and the table has an ON DELETE CASCADE to purchase_orders, so this
 * model deliberately does not carry its own global scope.
 */
class PurchaseOrderItem extends Model
{
    protected $connection = 'procurement';

    protected $table = 'purchase_order_items';

    protected $fillable = [
        'client_id', 'purchase_order_id', 'supplier_product_id',
        'name', 'category', 'qty', 'unit_price', 'amount',
    ];

    protected $casts = [
        'qty' => 'integer',
        'unit_price' => 'float',
        'amount' => 'float',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
