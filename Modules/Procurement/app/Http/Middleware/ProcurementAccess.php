<?php

namespace Modules\Procurement\Http\Middleware;

use App\Support\EmployeePermissionGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProcurementAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin') {
            return $next($request);
        }

        $department = strtolower((string) session('employee_department', ''));

        if (! session('employee_logged_in') || ! session('employee_client_id') || ! (str_contains($department, 'procurement') || str_contains($department, 'purchasing'))) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sign in with an approved Procurement employee account to access Procurement.',
            ]);
        }

        if ($request->routeIs('procurement.dashboard', 'procurement.live-stats')) {
            EmployeePermissionGate::abortUnlessAllowed(
                'You do not have permission to access Procurement.',
                'procurement.approve_purchase_orders',
                'procurement.manage_suppliers',
                'procurement.manage_requisitions',
                'procurement.log_deliveries'
            );

            return $next($request);
        }

        $permission = match (true) {
            $request->routeIs('procurement.suppliers.*') => 'procurement.manage_suppliers',
            $request->routeIs('procurement.requisitions.*') => 'procurement.manage_requisitions',
            $request->routeIs('procurement.deliveries.*') => 'procurement.log_deliveries',
            default => 'procurement.approve_purchase_orders',
        };

        EmployeePermissionGate::abortUnlessAllowed(
            'You do not have permission to access this Procurement function.',
            $permission
        );

        return $next($request);
    }
}
