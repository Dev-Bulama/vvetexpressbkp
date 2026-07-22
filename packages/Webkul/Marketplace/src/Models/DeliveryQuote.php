<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryQuote extends Model
{
    protected $table = 'delivery_quotes';

    protected $fillable = [
        'quote_token',
        'cart_id',
        'seller_id',
        'logistics_provider_id',
        'logistics_service_type_id',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'distance_km',
        'duration_minutes',
        'fee_minor',
        'currency_code',
        'expires_at',
        'consumed_at',
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'dropoff_latitude' => 'decimal:8',
        'dropoff_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class, 'logistics_provider_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(LogisticsServiceType::class, 'logistics_service_type_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }
}
