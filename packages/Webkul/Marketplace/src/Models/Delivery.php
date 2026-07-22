<?php

namespace Webkul\Marketplace\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Webkul\Sales\Models\Order;

class Delivery extends Model
{
    use SoftDeletes;

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_SEARCHING_AGENT = 'searching_agent';

    public const STATUS_AGENT_ASSIGNED = 'agent_assigned';

    public const STATUS_EN_ROUTE_TO_VENDOR = 'en_route_to_vendor';

    public const STATUS_ARRIVED_AT_VENDOR = 'arrived_at_vendor';

    public const STATUS_PICKED_UP = 'picked_up';

    public const STATUS_EN_ROUTE_TO_CUSTOMER = 'en_route_to_customer';

    public const STATUS_ARRIVED_AT_CUSTOMER = 'arrived_at_customer';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The only status a delivery may move to from each current status.
     * Anything not listed here (including same-status repeats) is rejected
     * by DeliveryStateMachine::transition() - this is what "prevent invalid
     * state transitions" means in practice for this domain.
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_REQUESTED => [self::STATUS_SEARCHING_AGENT, self::STATUS_CANCELLED],
        self::STATUS_SEARCHING_AGENT => [self::STATUS_AGENT_ASSIGNED, self::STATUS_FAILED, self::STATUS_CANCELLED],
        self::STATUS_AGENT_ASSIGNED => [self::STATUS_EN_ROUTE_TO_VENDOR, self::STATUS_CANCELLED],
        self::STATUS_EN_ROUTE_TO_VENDOR => [self::STATUS_ARRIVED_AT_VENDOR, self::STATUS_CANCELLED],
        self::STATUS_ARRIVED_AT_VENDOR => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED],
        self::STATUS_PICKED_UP => [self::STATUS_EN_ROUTE_TO_CUSTOMER],
        self::STATUS_EN_ROUTE_TO_CUSTOMER => [self::STATUS_ARRIVED_AT_CUSTOMER],
        self::STATUS_ARRIVED_AT_CUSTOMER => [self::STATUS_COMPLETED, self::STATUS_FAILED],
        self::STATUS_COMPLETED => [],
        self::STATUS_FAILED => [],
        self::STATUS_CANCELLED => [],
    ];

    /**
     * Statuses where the assigned agent's live position is meaningfully
     * "in transit" and worth broadcasting/routing to a map.
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_AGENT_ASSIGNED,
        self::STATUS_EN_ROUTE_TO_VENDOR,
        self::STATUS_ARRIVED_AT_VENDOR,
        self::STATUS_PICKED_UP,
        self::STATUS_EN_ROUTE_TO_CUSTOMER,
        self::STATUS_ARRIVED_AT_CUSTOMER,
    ];

    protected $table = 'deliveries';

    protected $fillable = [
        'order_id',
        'seller_id',
        'customer_id',
        'logistics_provider_id',
        'logistics_service_type_id',
        'delivery_agent_id',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_address',
        'dropoff_latitude',
        'dropoff_longitude',
        'status',
        'distance_km',
        'duration_minutes_estimate',
        'fee_minor',
        'currency_code',
        'pickup_verification_code',
        'dropoff_verification_code',
        'picked_up_verified_at',
        'delivered_verified_at',
        'requested_at',
        'agent_assigned_at',
        'arrived_at_vendor_at',
        'picked_up_at',
        'arrived_at_customer_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'dropoff_latitude' => 'decimal:8',
        'dropoff_longitude' => 'decimal:8',
        'distance_km' => 'decimal:2',
        'requested_at' => 'datetime',
        'agent_assigned_at' => 'datetime',
        'arrived_at_vendor_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'arrived_at_customer_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'picked_up_verified_at' => 'datetime',
        'delivered_verified_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(LogisticsProvider::class, 'logistics_provider_id');
    }

    public function serviceType(): BelongsTo
    {
        return $this->belongsTo(LogisticsServiceType::class, 'logistics_service_type_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(DeliveryAgent::class, 'delivery_agent_id');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(DeliveryStatusHistory::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DeliveryAgentLocation::class);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::ALLOWED_TRANSITIONS[$this->status] ?? [], true);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
