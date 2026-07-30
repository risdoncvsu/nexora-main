<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Finance\Models\Concerns\BelongsToClient;

class CashBalance extends Model
{
    use BelongsToClient, HasFactory;

    protected $connection = 'finance';

    protected $table = 'cash_balance';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'cash_balance',
        'nexora_client_id',
    ];

    protected $casts = [
        'cash_balance' => 'decimal:2',
    ];
}