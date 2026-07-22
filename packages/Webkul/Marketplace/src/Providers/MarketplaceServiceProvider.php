<?php

namespace Webkul\Marketplace\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\Marketplace\Http\Middleware\SellerGuard;

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

        Route::middleware('web')->group(__DIR__.'/../Routes/seller-routes.php');
    }
}
