<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- 1. Add this import at the top
use Illuminate\Support\Facades\Auth;
use App\Auth\EcommerceAdminUserProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Auth::provider('ecommerce-admin-employees', function ($app, array $config) {
            return new EcommerceAdminUserProvider($app['hash'], $config['model']);
        });

        // Force HTTPS everywhere except local development. Locally (APP_ENV=local)
        // no scheme is forced, so http on localhost works.
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    }
}
