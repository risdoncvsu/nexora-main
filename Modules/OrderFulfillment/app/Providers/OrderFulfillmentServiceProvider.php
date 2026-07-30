<?php

namespace Modules\OrderFulfillment\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class OrderFulfillmentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'order-fulfillment');
        $this->commands([
            \Modules\OrderFulfillment\Console\Commands\InstallOrderFulfillmentSchema::class,
            \Modules\OrderFulfillment\Console\Commands\EnsureOrderFulfillmentClientColumns::class,
            \Modules\OrderFulfillment\Console\Commands\CompleteDeliveredOrders::class,
            \Modules\OrderFulfillment\Console\Commands\ProgressReturnLifecycle::class,
        ]);
        Route::middleware('web')->group(__DIR__.'/../../routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->app->booted(function (): void {
                $this->app->make(Schedule::class)
                    ->command('orders:complete-delivered')
                    ->everyFiveMinutes();

                // Returns move through their post-approval lifecycle on
                // much shorter timers (down to 10 minutes for the last
                // step), so this runs every minute — checked here rather
                // than only on Returns-tab page load, so Orders/Dashboard
                // pick up each promotion (and the RETURNED/REFUNDED sync
                // it triggers) without anyone needing to visit the Returns
                // tab first.
                $this->app->make(Schedule::class)
                    ->command('returns:progress-lifecycle')
                    ->everyMinute();
            });
        }
    }
}
