<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\CheckoutVendorController;

Route::controller(CheckoutVendorController::class)->prefix('checkout/vendor')->group(function () {
    Route::get('', 'index')->name('marketplace.checkout.vendor.index');
    Route::post('', 'store')->name('marketplace.checkout.vendor.store');
});
