<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\SellerDashboardController;
use Webkul\Marketplace\Http\Controllers\SellerRegistrationController;
use Webkul\Marketplace\Http\Controllers\SellerSessionController;

Route::prefix('seller')->group(function () {
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
    });
});
