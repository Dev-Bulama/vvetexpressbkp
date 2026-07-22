<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class DeliveryAgent extends Authenticatable
{
    use Notifiable, SoftDeletes;

    public const STATUS_OFFLINE = 'offline';

    public const STATUS_AVAILABLE = 'available';

    public const STATUS_ON_DELIVERY = 'on_delivery';

    protected $table = 'delivery_agents';

    protected $fillable = [
        'logistics_provider_id',
        'vehicle_id',
        'name',
        'email',
        'password',
        'phone',
        'photo_path',
        'status',
        'current_latitude',
        'current_longitude',
        'last_location_at',
        'rating',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'current_latitude' => 'decimal:8',
        'current_longitude' => 'decimal:8',
        'last_location_at' => 'datetime',
        'rating' => 'decimal:1',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class, 'logistics_provider_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgentVehicle::class, 'vehicle_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === self::STATUS_AVAILABLE;
    }
}
