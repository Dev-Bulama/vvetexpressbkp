<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\Admin\ErpNextCategoryController;
use Webkul\Marketplace\Http\Controllers\Admin\ErpNextProductController;
use Webkul\Marketplace\Http\Controllers\Admin\MetricsController;
use Webkul\Marketplace\Http\Controllers\Admin\SellerController;
use Webkul\Marketplace\Http\Controllers\Admin\VendorCatalogueCoverageController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(SellerController::class)->prefix('marketplace/sellers')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.sellers.index');
        Route::get('create', 'create')->name('marketplace.admin.sellers.create');
        Route::post('', 'store')->name('marketplace.admin.sellers.store');
        Route::get('{id}/edit', 'edit')->name('marketplace.admin.sellers.edit');
        Route::post('{id}/status', 'updateStatus')->name('marketplace.admin.sellers.update-status');
    });

    Route::controller(VendorCatalogueCoverageController::class)->prefix('marketplace/catalogue-coverage')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.catalogue-coverage.index');
        Route::get('{id}', 'show')->name('marketplace.admin.catalogue-coverage.show');
        Route::post('{id}/remind', 'remind')->name('marketplace.admin.catalogue-coverage.remind');
    });

    Route::controller(ErpNextProductController::class)->prefix('marketplace/erpnext-products')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.erpnext-products.index');
        Route::post('bulk-visibility', 'bulkUpdateVisibility')->name('marketplace.admin.erpnext-products.bulk-visibility');
        Route::post('{id}/toggle-visibility', 'toggleVisibility')->name('marketplace.admin.erpnext-products.toggle-visibility');
    });

    Route::controller(ErpNextCategoryController::class)->prefix('marketplace/erpnext-categories')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.erpnext-categories.index');
        Route::post('sync', 'sync')->name('marketplace.admin.erpnext-categories.sync');
        Route::post('disable-non-api', 'disableNonApiCategories')->name('marketplace.admin.erpnext-categories.disable-non-api');
        Route::post('keep-only-selected', 'keepOnlySelected')->name('marketplace.admin.erpnext-categories.keep-only-selected');
        Route::post('import-visibility', 'importVisibility')->name('marketplace.admin.erpnext-categories.import-visibility');
        Route::post('{id}/toggle-local', 'toggleLocal')->name('marketplace.admin.erpnext-categories.toggle-local');
    });

    Route::get('metrics', [MetricsController::class, 'index'])->name('marketplace.admin.metrics.index');
});
