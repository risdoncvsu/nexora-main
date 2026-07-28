<?php

namespace Modules\Ecommerce\Http\Middleware;

use App\Models\EmployeeAccessProfile;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Ecommerce\Support\EcommerceClientContext;
use Symfony\Component\HttpFoundation\Response;

class ResolveEcommerceAdminClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('ecommerce_admin')->user();

        if (! $admin || ! $admin->isEcommerceEmployee()) {
            Auth::guard('ecommerce_admin')->logout();

            return redirect()->route('ecommerce.admin.login')
                ->withErrors(['email' => 'Your E-commerce account is not active.']);
        }

        app(EcommerceClientContext::class)->setClientId((int) $admin->client_id);

        $profile = EmployeeAccessProfile::query()
            ->where('company_id', (int) $admin->client_id)
            ->where('employee_id', (int) $admin->id)
            ->first();

        // Profiles are optional for existing clients. Once a system
        // administrator has saved one, it is an explicit restriction.
        if ($profile) {
            $permissions = $profile->access_permissions ?? [];
            $permissions = is_array($permissions) ? $permissions : (json_decode((string) $permissions, true) ?: []);
            if ($request->routeIs('ecommerce.admin.dashboard')) {
                abort_unless(
                    in_array('ecommerce.manage_product_listings', $permissions, true)
                        || in_array('ecommerce.view_orders', $permissions, true)
                        || in_array('ecommerce.manage_storefront', $permissions, true),
                    403,
                    'You do not have permission to access E-commerce Admin.'
                );
            } else {
                $permission = $request->routeIs('ecommerce.admin.orders')
                    ? 'ecommerce.view_orders'
                    : 'ecommerce.manage_product_listings';

                abort_unless(
                    in_array($permission, $permissions, true)
                        || ($permission === 'ecommerce.manage_product_listings' && in_array('ecommerce.manage_storefront', $permissions, true)),
                    403,
                    'You do not have permission to access this E-commerce admin function.'
                );
            }
        }

        return $next($request);
    }
}
