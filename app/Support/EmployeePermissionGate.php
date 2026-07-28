<?php

namespace App\Support;

use App\Models\EmployeeAccessProfile;

/**
 * Applies a client system administrator's explicit employee restrictions.
 *
 * Employees who do not yet have an access-profile retain the legacy module
 * behaviour. Once a profile exists, its permissions are authoritative.
 */
class EmployeePermissionGate
{
    public static function allows(string ...$permissions): bool
    {
        $profile = EmployeeAccessProfile::query()
            ->where('company_id', (int) session('employee_client_id'))
            ->where('employee_id', (int) session('employee_id'))
            ->first();

        if (! $profile) {
            return true;
        }

        $assigned = $profile->access_permissions ?? [];
        $assigned = is_array($assigned) ? $assigned : (json_decode((string) $assigned, true) ?: []);

        return (bool) array_intersect($permissions, $assigned);
    }

    public static function abortUnlessAllowed(string $message, string ...$permissions): void
    {
        abort_unless(self::allows(...$permissions), 403, $message);
    }
}
