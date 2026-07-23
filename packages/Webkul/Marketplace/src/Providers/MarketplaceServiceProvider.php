<?php

namespace Webkul\Marketplace\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\ResponseCache\ResponseCache;
use Webkul\CMS\Models\Page as CmsPage;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Marketplace\Console\Commands\SyncErpNextProductsCommand;
use Webkul\Marketplace\Http\Middleware\DeliveryAgentGuard;
use Webkul\Marketplace\Http\Middleware\SellerGuard;
use Webkul\Marketplace\Listeners\CreateDeliveryOnOrderPlaced;
use Webkul\Marketplace\Listeners\RevalidateVendorBeforeOrderPlaced;
use Webkul\Theme\Models\ThemeCustomization;
use Webkul\Theme\ViewRenderEventManager;

class MarketplaceServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap application services.
     */
    public function boot(Router $router): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'marketplace');

        $router->aliasMiddleware('seller', SellerGuard::class);

        $router->aliasMiddleware('delivery_agent', DeliveryAgentGuard::class);

        Route::middleware(['web', 'shop'])->group(__DIR__.'/../Routes/seller-routes.php');

        Route::middleware(['web', 'shop'])->group(__DIR__.'/../Routes/checkout-routes.php');

        Route::middleware(['web', 'shop'])->group(__DIR__.'/../Routes/location-routes.php');

        Route::middleware(['web', 'shop'])->group(__DIR__.'/../Routes/tracking-routes.php');

        Route::middleware('web')->group(__DIR__.'/../Routes/admin-routes.php');

        Route::middleware('web')->group(__DIR__.'/../Routes/agent-routes.php');

        Event::listen('bagisto.shop.products.price.after', function (ViewRenderEventManager $manager) {
            $manager->addTemplate('marketplace::shop.product-availability');
        });

        Event::listen('bagisto.shop.customers.account.orders.view.before', function (ViewRenderEventManager $manager) {
            $manager->addTemplate('marketplace::shop.order-delivery-tracking');
        });

        Event::listen('checkout.order.save.before', RevalidateVendorBeforeOrderPlaced::class);

        Event::listen('checkout.order.save.after', CreateDeliveryOnOrderPlaced::class);

        $this->clearResponseCacheOnStorefrontChanges();

        if ($this->app->runningInConsole()) {
            $this->commands([SyncErpNextProductsCommand::class]);
        }

        // Shared hosting rarely has a cron entry wired up by default - this
        // only fires if the host's crontab actually calls `schedule:run`
        // (see the sync command's own error message for the manual
        // fallback: `php artisan erpnext:sync-products`).
        $this->app->afterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command(SyncErpNextProductsCommand::class)->hourly()->withoutOverlapping();
        });
    }

    /**
     * Bagisto's full-page cache (spatie/laravel-responsecache) is keyed by
     * URL/channel/locale/currency only, with no invalidation wired up for
     * admin changes - saving a new channel logo, a config value, a homepage
     * theme slide, or a CMS page would otherwise stay invisible on the
     * storefront until the cache's own lifetime expires (a week by
     * default). Clearing the whole cache on these saves is coarse, but
     * these are infrequent admin actions and a cache miss just costs one
     * normal page render.
     */
    private function clearResponseCacheOnStorefrontChanges(): void
    {
        if (! config('responsecache.enabled')) {
            return;
        }

        $clear = function (): void {
            app(ResponseCache::class)->clear();
        };

        foreach ([Channel::class, CoreConfig::class, ThemeCustomization::class, CmsPage::class] as $model) {
            $model::saved($clear);
            $model::deleted($clear);
        }
    }
}
