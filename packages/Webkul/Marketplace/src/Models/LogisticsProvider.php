<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LogisticsProvider extends Model
{
    use SoftDeletes;

    public const TYPE_INTERNAL = 'internal';

    public const TYPE_VENDOR_MANAGED = 'vendor_managed';

    public const TYPE_THIRD_PARTY = 'third_party';

    protected $table = 'logistics_providers';

    protected $fillable = [
        'code',
        'name',
        'type',
        'adapter_class',
        'is_active',
        'config',
        'base_fee',
        'fee_per_km',
        'max_distance_km',
        'commission_percent',
        'rating',
    ];

    protected $casts = [
        'config' => 'array',
        'is_active' => 'boolean',
        'base_fee' => 'decimal:2',
        'fee_per_km' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'rating' => 'decimal:1',
    ];

    public function serviceTypes(): HasMany
    {
        return $this->hasMany(LogisticsServiceType::class);
    }

    public function agents(): HasMany
    {
        return $this->hasMany(DeliveryAgent::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class);
    }

    /**
     * Resolve this provider's adapter instance. Never fabricate a connected
     * state - a provider with no real credentials should resolve to
     * NullProvider and report itself as unavailable everywhere it's asked.
     */
    public function adapter(): \Webkul\Marketplace\Logistics\Contracts\LogisticsProviderAdapter
    {
        $class = $this->adapter_class ?: \Webkul\Marketplace\Logistics\Adapters\NullProvider::class;

        return app($class, ['provider' => $this]);
    }
}
