<?php

namespace Webkul\Marketplace\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Marketplace\Http\Middleware\DeliveryAgentGuard;
use Webkul\Marketplace\Http\Middleware\SellerGuard;
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

        Route::middleware('web')->group(__DIR__.'/../Routes/admin-routes.php');

        Route::middleware('web')->group(__DIR__.'/../Routes/agent-routes.php');

        Event::listen('bagisto.shop.products.price.after', function (ViewRenderEventManager $manager) {
            $manager->addTemplate('marketplace::shop.product-availability');
        });
    }
}
