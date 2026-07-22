<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogisticsServiceType extends Model
{
    protected $table = 'logistics_service_types';

    protected $fillable = [
        'logistics_provider_id',
        'code',
        'name',
        'vehicle_type',
        'description',
        'tracking_available',
        'estimated_pickup_minutes',
        'base_fee',
        'fee_per_km',
        'is_active',
    ];

    protected $casts = [
        'tracking_available' => 'boolean',
        'is_active' => 'boolean',
        'base_fee' => 'decimal:2',
        'fee_per_km' => 'decimal:2',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class, 'logistics_provider_id');
    }
}
