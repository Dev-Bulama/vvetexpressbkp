<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\DeliveryTrackingController;

Route::get('track-delivery/{id}', [DeliveryTrackingController::class, 'show'])
    ->where('id', '[0-9]+')
    ->name('marketplace.tracking.show');
