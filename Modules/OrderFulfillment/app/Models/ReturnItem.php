<?php

namespace Modules\OrderFulfillment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\OrderFulfillment\Models\Concerns\BelongsToClient;

class ReturnItem extends Model
{
    use HasFactory, BelongsToClient;

    // returns has no client_id column and isn't tenant-scoped, so this
    // model intentionally does NOT use the BelongsToClient trait — that
    // trait's global scope filters on a column this table doesn't have.
    // It still needs the same DB connection BelongsToClient would have set,
    // so that's declared directly here instead.
    protected $table = 'returns';

    protected $fillable = [
        'id',
        'client_id',
        'order_id',
        'customer_name',
        'product_name',
        'reason',
        'status',
        'resolution',
        'due_date',
        'address',
        'refund_amount'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
}
