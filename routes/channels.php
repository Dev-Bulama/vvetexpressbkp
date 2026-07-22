<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Webkul\Marketplace\Models\Delivery;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/**
 * One delivery's live tracking channel, reachable from four different auth
 * guards (customer/seller/delivery_agent/admin). Laravel's broadcaster only
 * resolves $request->user() from the app's single default guard unless told
 * otherwise via the 'guards' option below - without it, every guard except
 * the default one gets a silent 403 before this closure even runs. $user is
 * whichever guard resolved first; checked again against each guard
 * explicitly since the same model class can't tell us which guard it came
 * from. Only the specific parties involved in this exact delivery are
 * authorized - this is what keeps another customer's delivery position
 * private.
 */
Broadcast::channel('delivery.{deliveryId}', function ($user, int $deliveryId) {
    $delivery = Delivery::find($deliveryId);

    if (! $delivery) {
        return false;
    }

    if (Auth::guard('customer')->check()) {
        return $delivery->customer_id === Auth::guard('customer')->id();
    }

    if (Auth::guard('seller')->check()) {
        return $delivery->seller_id === Auth::guard('seller')->id();
    }

    if (Auth::guard('delivery_agent')->check()) {
        return $delivery->delivery_agent_id === Auth::guard('delivery_agent')->id();
    }

    if (Auth::guard('admin')->check()) {
        return true;
    }

    return false;
}, ['guards' => ['customer', 'seller', 'delivery_agent', 'admin']]);

/**
 * A delivery agent's own channel - job offers, assignment notifications.
 * Only that exact agent may listen.
 */
Broadcast::channel('delivery-agent.{agentId}', function ($user, int $agentId) {
    return Auth::guard('delivery_agent')->check()
        && Auth::guard('delivery_agent')->id() === $agentId;
}, ['guards' => ['delivery_agent']]);

/**
 * Admin-only channel for the live delivery map showing every active
 * delivery across the platform at once.
 */
Broadcast::channel('admin.deliveries', function ($user) {
    return Auth::guard('admin')->check();
}, ['guards' => ['admin']]);
