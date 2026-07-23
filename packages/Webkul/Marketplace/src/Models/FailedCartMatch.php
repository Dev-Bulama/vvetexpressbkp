<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;

class FailedCartMatch extends Model
{
    protected $table = 'marketplace_failed_cart_matches';

    protected $fillable = [
        'customer_id',
        'guest_session_id',
        'customer_latitude',
        'customer_longitude',
        'cart_snapshot',
        'cart_value',
        'vendors_evaluated',
        'nearest_vendor_id',
        'nearest_almost_eligible_vendor_id',
        'customer_action',
        'cart_saved',
        'items_removed',
        'checkout_abandoned',
    ];

    protected $casts = [
        'cart_snapshot' => 'array',
        'vendors_evaluated' => 'array',
        'cart_saved' => 'boolean',
        'items_removed' => 'boolean',
        'checkout_abandoned' => 'boolean',
    ];
}
