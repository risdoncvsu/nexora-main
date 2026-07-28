<?php

namespace Modules\Manufacturing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Manufacturing\Models\Concerns\BelongsToClient;

class ProductBom extends Model
{
    use BelongsToClient;

    protected $table = 'product_boms';

    // Packaging BOMs are operational packing material lists, not products that
    // can be sold or sent through the computer QC benchmark.
    protected $fillable = ['sku', 'name', 'description', 'status', 'bom_type'];

    public function items(): HasMany
    {
        return $this->hasMany(ProductBomItem::class, 'bom_id');
    }
}
