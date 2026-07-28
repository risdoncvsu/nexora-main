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
        // HR managers retain their existing full administrative workflow.
        if (session('employee_role') === 'admin') {
            return $next($request);
        }

        $profile = EmployeeAccessProfile::query()
            ->where('company_id', (int) session('employee_client_id'))
            ->where('employee_id', (int) session('employee_id'))
            ->first();

        abort_unless(
            $profile && in_array($permission, $profile->access_permissions ?? [], true),
            403,
            'You do not have permission to perform this Human Resources action.'
        );

        return $next($request);
    }
}
