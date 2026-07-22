<?php

use Illuminate\Support\Facades\Route;
use Webkul\Marketplace\Http\Controllers\AgentDashboardController;
use Webkul\Marketplace\Http\Controllers\AgentLocationController;
use Webkul\Marketplace\Http\Controllers\AgentSessionController;

Route::prefix('delivery-agent')->group(function () {
    Route::controller(AgentSessionController::class)->group(function () {
        Route::get('login', 'index')->name('marketplace.agent.session.index');
        Route::post('login', 'store')->name('marketplace.agent.session.create');
        Route::delete('logout', 'destroy')->name('marketplace.agent.session.destroy');
    });

    Route::middleware('delivery_agent')->group(function () {
        Route::get('dashboard', [AgentDashboardController::class, 'index'])->name('marketplace.agent.dashboard.index');

        Route::post('location', [AgentLocationController::class, 'update'])->name('marketplace.agent.location.update');
    });
});
