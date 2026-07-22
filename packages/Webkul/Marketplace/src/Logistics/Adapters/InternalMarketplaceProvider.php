<?php

namespace Webkul\Marketplace\Logistics\Adapters;

use Webkul\Marketplace\Logistics\Contracts\LogisticsProviderAdapter;
use Webkul\Marketplace\Logistics\DTOs\DeliveryRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteResult;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\LogisticsProvider;
use Webkul\Marketplace\Support\GeoDistance;

/**
 * Our own in-house delivery agents. Fully real: pricing comes from the
 * provider's configured base_fee/fee_per_km, agent search/assignment is
 * handled by AgentAssignmentService against real delivery_agents rows, and
 * status is whatever is already stored on the delivery itself (there's no
 * external system to poll or receive webhooks from).
 */
class InternalMarketplaceProvider implements LogisticsProviderAdapter
{
    /** Average effective speed used only for the pre-assignment ETA estimate. */
    protected const AVERAGE_SPEED_KMH = 22;

    public function __construct(protected LogisticsProvider $provider) {}

    public function isAvailable(): bool
    {
        return $this->provider->is_active;
    }

    public function quote(QuoteRequest $request): QuoteResult
    {
        if (! $this->isAvailable()) {
            return QuoteResult::unavailable('Internal delivery is currently disabled.');
        }

        $distanceKm = GeoDistance::haversineKm(
            $request->pickupLatitude,
            $request->pickupLongitude,
            $request->dropoffLatitude,
            $request->dropoffLongitude,
        );

        if ($this->provider->max_distance_km && $distanceKm > $this->provider->max_distance_km) {
            return QuoteResult::unavailable('Route exceeds the maximum delivery distance.');
        }

        $serviceType = $request->serviceType;

        $feeNaira = (float) $serviceType->base_fee + ((float) $serviceType->fee_per_km * $distanceKm);
        $durationMinutes = $serviceType->estimated_pickup_minutes
            + max(5, (int) ceil(($distanceKm / self::AVERAGE_SPEED_KMH) * 60));

        return new QuoteResult(
            available: true,
            distanceKm: round($distanceKm, 2),
            durationMinutes: $durationMinutes,
            feeMinor: (int) round($feeNaira * 100),
            currencyCode: core()->getCurrentCurrencyCode() ?? 'NGN',
        );
    }

    public function createDelivery(DeliveryRequest $request): bool
    {
        return $this->isAvailable();
    }

    public function cancelDelivery(Delivery $delivery, string $reason): bool
    {
        return true;
    }

    public function fetchStatus(Delivery $delivery): ?string
    {
        return $delivery->status;
    }

    public function parseWebhook(array $payload, array $headers): ?array
    {
        return null;
    }

    public function supportsLiveTracking(): bool
    {
        return true;
    }
}
