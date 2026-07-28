<?php

namespace Modules\HR\Http\Middleware;

use App\Models\EmployeeAccessProfile;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireHrPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $profile = EmployeeAccessProfile::query()
            ->where('company_id', (int) session('employee_client_id'))
            ->where('employee_id', (int) session('employee_id'))
            ->first();

        // A profile saved by the client system administrator is an explicit
        // restriction and must take precedence over the legacy HR-manager
        // session role.  Managers without a saved profile retain the existing
        // full-access behaviour, so existing clients are not locked out.
        if ($profile) {
            abort_unless(
                in_array($permission, $profile->access_permissions ?? [], true),
                403,
                'You do not have permission to perform this Human Resources action.'
            );

            return $next($request);
        }

        if (session('employee_role') === 'admin') {
            return $next($request);
        }

        abort_unless(
            false,
            403,
            'You do not have permission to perform this Human Resources action.'
        );

        return $next($request);
    }
}
