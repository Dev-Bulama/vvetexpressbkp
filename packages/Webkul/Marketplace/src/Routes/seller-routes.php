<?php

use Illuminate\Support\Facades\Route;
use Webkul\Core\Http\Middleware\NoCacheMiddleware;
use Webkul\Marketplace\Http\Controllers\SellerDashboardController;
use Webkul\Marketplace\Http\Controllers\SellerPosController;
use Webkul\Marketplace\Http\Controllers\SellerProductController;
use Webkul\Marketplace\Http\Controllers\SellerProfileController;
use Webkul\Marketplace\Http\Controllers\SellerRegistrationController;
use Webkul\Marketplace\Http\Controllers\SellerSessionController;

/**
 * Every seller route (guest register/login pages included) gets
 * NoCacheMiddleware, the same as admin and customer-authenticated routes
 * already do - without it, a browser/proxy/CDN can serve a stale cached
 * copy of the login page carrying an old CSRF token, causing a 419 on
 * the very first submit right after registering (fixed by a hard
 * reload only because that bypasses the stale cache, not because
 * anything was actually wrong with the session).
 */
Route::prefix('seller')->middleware(NoCacheMiddleware::class)->group(function () {
    Route::controller(SellerRegistrationController::class)->prefix('register')->group(function () {
        Route::get('', 'index')->name('marketplace.seller.register.index');
        Route::post('', 'store')->name('marketplace.seller.register.store');
    });

    Route::controller(SellerSessionController::class)->group(function () {
        Route::get('login', 'index')->name('marketplace.seller.session.index');
        Route::post('login', 'store')->name('marketplace.seller.session.create');
        Route::post('logout', 'destroy')->name('marketplace.seller.session.destroy');
    });

    Route::middleware('seller')->group(function () {
        Route::get('dashboard', [SellerDashboardController::class, 'index'])->name('marketplace.seller.dashboard.index');

        Route::controller(SellerPosController::class)->prefix('pos')->group(function () {
            Route::get('', 'index')->name('marketplace.seller.pos.index');
            Route::post('charge', 'charge')->name('marketplace.seller.pos.charge');
        });

        Route::controller(SellerProductController::class)->prefix('products')->group(function () {
            Route::get('', 'index')->name('marketplace.seller.products.index');
            Route::get('create', 'create')->name('marketplace.seller.products.create');
            Route::post('', 'store')->name('marketplace.seller.products.store');
            Route::get('{id}/edit', 'edit')->name('marketplace.seller.products.edit');
            Route::put('{id}', 'update')->name('marketplace.seller.products.update');
            Route::delete('{id}', 'destroy')->name('marketplace.seller.products.destroy');
        });

        Route::controller(SellerProfileController::class)->prefix('profile')->group(function () {
            Route::get('', 'edit')->name('marketplace.seller.profile.edit');
            Route::put('', 'update')->name('marketplace.seller.profile.update');
        });
    });
});
