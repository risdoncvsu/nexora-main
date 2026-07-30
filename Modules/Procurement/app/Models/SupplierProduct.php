<?php

namespace Modules\Procurement\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Referenced by Supplier::products(). Same story as PurchaseOrderItem — the
 * relation pointed at a class that did not exist.
 *
 * Rows hang off a client-scoped supplier and cascade with it, so no separate
 * global scope here.
 */
class SupplierProduct extends Model
{
    protected $connection = 'procurement';

    protected $table = 'supplier_products';

    protected $fillable = [
        'supplier_id', 'name', 'sku', 'categories', 'unit_price',
    ];

    protected $casts = [
        'unit_price' => 'float',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}
