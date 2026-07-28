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
            $permissions = array_values(array_unique(array_map('strval', $permissions)));

            // `manage_storefront` was used by an earlier implementation but
            // was never exposed in the client ITSM access-control editor.
            // Preserve existing access while moving every profile to the two
            // supported E-commerce permissions.
            if (in_array('ecommerce.manage_storefront', $permissions, true)) {
                $permissions[] = 'ecommerce.manage_product_listings';
                $permissions = array_values(array_unique($permissions));
            }

            $request->attributes->set('ecommerce_permissions', $permissions);
            $request->attributes->set('ecommerce_has_access_profile', true);

            if ($request->routeIs('ecommerce.admin.dashboard')) {
                abort_unless(
                    in_array('ecommerce.manage_product_listings', $permissions, true)
                        || in_array('ecommerce.view_orders', $permissions, true),
                    403,
                    'You do not have permission to access E-commerce Admin.'
                );
            } else {
                $permission = $request->routeIs('ecommerce.admin.orders')
                    ? 'ecommerce.view_orders'
                    : 'ecommerce.manage_product_listings';

                abort_unless(
                    in_array($permission, $permissions, true),
                    403,
                    'You do not have permission to access this E-commerce admin function.'
                );
            }
        } else {
            // Existing employees without a saved access profile retain their
            // legacy access until a client administrator creates one.
            $request->attributes->set('ecommerce_permissions', []);
            $request->attributes->set('ecommerce_has_access_profile', false);
        }

        return $next($request);
    }
}
