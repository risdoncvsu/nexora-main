<?php

namespace Modules\OrderFulfillment\Http\Middleware;

use App\Support\EmployeePermissionGate;
use App\Services\ErpIntegrationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrderFulfillmentAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('nexora.root_admin_module_testing') && $request->user()?->role === 'root_admin') {
            return $next($request);
        }

        if (! session('employee_logged_in') || ! session('employee_client_id')) {
            return redirect()->route('login')->withErrors([
                'username' => 'Sign in with your approved Order Fulfillment employee account to access Order Fulfillment.',
            ]);
        }

        $permission = match (true) {
            $request->routeIs('order-fulfillment.packing*') => 'order_fulfillment.manage_packing',
            $request->routeIs('order-fulfillment.shipping*') => 'order_fulfillment.view_shipping',
            $request->routeIs('order-fulfillment.return*') || $request->routeIs('order-fulfillment.returns*') => 'order_fulfillment.manage_returns',
            default => 'order_fulfillment.manage_orders',
        };

        EmployeePermissionGate::abortUnlessAllowed(
            'You do not have permission to access this Order Fulfillment function.',
            $permission
        );

        // Make pre-client-scope storefront orders visible only to the client
        // that owns their Ecommerce UUID. This is idempotent and leaves
        // unrelated legacy Order Fulfillment data untouched.
        app(ErpIntegrationService::class)->reconcileFulfillmentClientOwnership(
            (int) session('employee_client_id')
        );

        return $next($request);
    }
}
