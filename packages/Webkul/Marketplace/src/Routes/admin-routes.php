<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\Admin\SellerController;

Route::group(['middleware' => ['admin'], 'prefix' => config('app.admin_url')], function () {
    Route::controller(SellerController::class)->prefix('marketplace/sellers')->group(function () {
        Route::get('', 'index')->name('marketplace.admin.sellers.index');
        Route::get('{id}/edit', 'edit')->name('marketplace.admin.sellers.edit');
        Route::post('{id}/status', 'updateStatus')->name('marketplace.admin.sellers.update-status');
    });
});
