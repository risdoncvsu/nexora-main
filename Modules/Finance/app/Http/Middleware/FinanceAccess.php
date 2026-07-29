<?php

namespace Modules\Finance\Http\Middleware;

use App\Support\EmployeePermissionGate;
use App\Services\ErpIntegrationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class FinanceAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('web') ?? Auth::guard('web')->user();

        if (config('nexora.root_admin_module_testing') && $user?->role === 'root_admin') {
            return $next($request);
        }

        if (! session('employee_logged_in') || ! session('employee_client_id')) {
            return redirect()->away($request->getSchemeAndHttpHost().'/login')->withErrors([
                'username' => 'Sign in with your approved HR employee account to access Finance.',
            ]);
        }

        if ($request->routeIs('finance.maindash', 'finance.dashboard', 'finance.overview')) {
            EmployeePermissionGate::abortUnlessAllowed(
                'You do not have permission to access Finance.',
                'finance.manage_invoices',
                'finance.view_expenses',
                'finance.view_sales',
                'finance.view_cash_flow'
            );
        } else {
            $permission = match (true) {
                $request->routeIs('finance.expensesdash') => 'finance.view_expenses',
                $request->routeIs('finance.salesdash') => 'finance.view_sales',
                $request->routeIs('finance.cashflowdash') => 'finance.view_cash_flow',
                default => 'finance.manage_invoices',
            };

            EmployeePermissionGate::abortUnlessAllowed(
                'You do not have permission to access this Finance function.',
                $permission
            );
        }

        app(ErpIntegrationService::class)->reconcileFinanceInvoiceClientOwnership(
            (int) session('employee_client_id')
        );

        return $next($request);
    }
}
