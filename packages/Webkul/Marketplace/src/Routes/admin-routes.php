<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\Admin\SellerController;
use Webkul\Marketplace\Http\Controllers\Admin\VendorCatalogueCoverageController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(SellerController::class)->prefix('marketplace/sellers')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.sellers.index');
        Route::get('{id}/edit', 'edit')->name('marketplace.admin.sellers.edit');
        Route::post('{id}/status', 'updateStatus')->name('marketplace.admin.sellers.update-status');
    });

    Route::controller(VendorCatalogueCoverageController::class)->prefix('marketplace/catalogue-coverage')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.catalogue-coverage.index');
        Route::get('{id}', 'show')->name('marketplace.admin.catalogue-coverage.show');
        Route::post('{id}/remind', 'remind')->name('marketplace.admin.catalogue-coverage.remind');
    });
});
