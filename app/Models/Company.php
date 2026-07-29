<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'company_name',
    'ecommerce_slug',
    'industry',
    'company_email',
    'phone_no',
    'country_code',
    'timezone',
    'admin_name',
    'status',
    'admin_user_id',
    'employee_table_name',
    'logo_path',
    'hr_employee_id',
    'setup_completed_at',
])]
class Company extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'setup_completed_at' => 'datetime',
        ];
    }

    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    public function logoUrl(): ?string
    {
        $path = trim((string) $this->logo_path);

        if ($path === '') {
            return null;
        }

        // Support legacy records that retained an externally hosted logo.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Earlier deployments stored uploads directly under public/. Keep
        // those client logos working while newer uploads use the public disk.
        if (is_file(public_path($path))) {
            return asset($path);
        }

        if (is_file(public_path('storage/'.$path))) {
            return asset('storage/'.$path);
        }

        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        // Serve the ITSM-owned upload through the application instead of
        // assuming that every deployment has a public/storage symlink. This
        // keeps the exact uploaded logo available to every integrated module
        // and to wildcard storefront domains.
        return route('company.logo', ['company' => $this]);
    }
}
