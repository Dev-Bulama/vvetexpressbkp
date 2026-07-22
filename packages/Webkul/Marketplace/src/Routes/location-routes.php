<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\LocationController;

Route::controller(LocationController::class)->prefix('customer-location')->group(function () {
    Route::get('', 'show')->name('marketplace.location.show');
    Route::post('', 'store')->name('marketplace.location.store');
});
