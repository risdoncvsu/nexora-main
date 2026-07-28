<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAccessProfile extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'access_role',
        'module_access',
    ];

    protected function casts(): array
    {
        return [
            'module_access' => 'array',
        ];
    }
}
