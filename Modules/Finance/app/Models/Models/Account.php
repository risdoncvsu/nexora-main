<?php

namespace Modules\Finance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Modules\Finance\Models\Concerns\BelongsToClient;

class Account extends Model
{
    use BelongsToClient;

    protected $connection = 'finance';
    protected $table = 'accounts';
    protected $fillable = [
        'name',
        'account_type',
        'detail_type',
        'balance',
    ];

    public $timestamps = true;

    /**
     * Finance has been deployed with both the legacy `id` key and the newer
     * `account_id` key. Resolve the actual schema so the Accounts tab stays
     * usable during that transition instead of failing with an SQL 500.
     */
    public function getKeyName(): string
    {
        return Schema::connection('finance')->hasColumn($this->table, 'account_id')
            ? 'account_id'
            : 'id';
    }
}
