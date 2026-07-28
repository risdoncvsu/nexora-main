<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'role_name',
        'description',
        'department',
        'permissions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /** Keep the role view's conventional `name` property compatible with role_name. */
    public function getNameAttribute(): ?string
    {
        return $this->role_name;
    }

    // Example relationship if roles have users
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Example if roles have permissions
    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

}
