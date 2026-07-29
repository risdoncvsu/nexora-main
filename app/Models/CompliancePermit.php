<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompliancePermit extends Model
{
    protected $fillable = [
        'company_id',
        'title',
        'issuer',
        'expiry_date',
        'status',
        'file_path',
    ];

    protected function casts(): array
    {
        return ['expiry_date' => 'date'];
    }
}
