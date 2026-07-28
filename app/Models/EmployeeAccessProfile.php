<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAccessProfile extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'access_role',
        'access_permissions',
    ];

    protected function casts(): array
    {
        return [
            'access_permissions' => 'array',
        ];
    }
}
