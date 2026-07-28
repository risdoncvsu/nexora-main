<?php

namespace Modules\Ecommerce\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Ecommerce\Support\EcommerceClientContext;
use Modules\Ecommerce\Console\Commands\EnsureEcommerceClientColumns;
use Modules\Ecommerce\Console\Commands\AssignEcommerceCatalogToClient;
use Modules\Ecommerce\Console\Commands\BackfillEcommerceOrderLinks;
use Modules\Ecommerce\Console\Commands\SeedStockLevelsForListing;
use Modules\Ecommerce\Console\Commands\InstallEcommerceSchema;
use Modules\Ecommerce\Services\ListingAvailabilityService;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Observers\OrderObserver;

class EcommerceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(EcommerceClientContext::class, fn (): EcommerceClientContext => new EcommerceClientContext());
        $this->app->scoped(ListingAvailabilityService::class, fn (): ListingAvailabilityService => new ListingAvailabilityService());
        $this->commands([
            EnsureEcommerceClientColumns::class,
            AssignEcommerceCatalogToClient::class,
            BackfillEcommerceOrderLinks::class,
            SeedStockLevelsForListing::class,
            InstallEcommerceSchema::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'ecommerce');

        // Routes are now loaded from bootstrap/app.php (before main app routes)
        // to ensure subdomain routes match before root-level routes.
        // See bootstrap/app.php ->withRouting() callback.

        // The standalone storefront uses <x-navbar>, <x-footer>, and related
        // anonymous components directly, so retain those component names after
        // moving its views into this module.
        Blade::anonymousComponentPath(__DIR__.'/../../resources/views/components');

        // Register OrderObserver to sync CRM customer profiles on order creation
        Order::observe(OrderObserver::class);
    }
}
