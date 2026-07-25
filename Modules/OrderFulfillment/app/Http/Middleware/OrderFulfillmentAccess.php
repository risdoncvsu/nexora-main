<?php

namespace Modules\OrderFulfillment\Http\Middleware;

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

        // HR department and position values are free-form onboarding data,
        // not an authoritative module-permission source. Applying a keyword
        // gate here made every Order Fulfillment route fail for otherwise
        // active, client-scoped employees. Tenant isolation is enforced by
        // the Order Fulfillment models' client_id scope.

        return $next($request);
    }
}
