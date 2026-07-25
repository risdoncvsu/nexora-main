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

        $department = strtolower((string) session('employee_department', ''));
        $position = strtolower((string) session('employee_position', ''));
        $assignment = $department.' '.$position;

        $orderFulfillmentTerms = [
            'fulfillment',
            'operations',
            'order',
            'shipping',
            'logistics',
            'dispatch',
            'delivery',
            'courier',
            'packing',
            'returns',
        ];

        abort_unless(
            collect($orderFulfillmentTerms)->contains(
                fn (string $term): bool => str_contains($assignment, $term)
            ),
            403
        );

        return $next($request);
    }
}
