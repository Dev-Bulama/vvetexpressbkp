<?php

namespace Webkul\Marketplace\Logistics\Services;

use Webkul\Marketplace\Events\AgentLocationUpdated;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\DeliveryAgent;
use Webkul\Marketplace\Models\DeliveryAgentLocation;

/**
 * The single place an agent's position is ever written. Location history is
 * only recorded while the agent has an active delivery - a bounded,
 * operationally-justified log, not a standing location tracker - and every
 * update refreshes the agent's own current_latitude/longitude regardless of
 * whether a delivery is active, since that's also what agent search
 * (AgentAssignmentService) reads from.
 */
class LocationUpdateService
{
    private const MAX_ACCURACY_METERS = 500;

    public function record(
        DeliveryAgent $agent,
        float $latitude,
        float $longitude,
        ?float $accuracyMeters = null,
        ?float $headingDegrees = null,
        ?float $speedKph = null,
    ): void {
        if (! $this->isValidCoordinate($latitude, $longitude)) {
            throw new \InvalidArgumentException('Location payload out of range.');
        }

        // A very low-accuracy fix (large GPS error radius) is worse than no
        // update at all for a live map - drop it rather than jump the
        // marker to a wildly wrong spot.
        if ($accuracyMeters !== null && $accuracyMeters > self::MAX_ACCURACY_METERS) {
            return;
        }

        $agent->update([
            'current_latitude' => $latitude,
            'current_longitude' => $longitude,
            'last_location_at' => now(),
        ]);

        $delivery = Delivery::where('delivery_agent_id', $agent->id)
            ->whereIn('status', Delivery::ACTIVE_STATUSES)
            ->latest('id')
            ->first();

        if (! $delivery) {
            return;
        }

        $location = DeliveryAgentLocation::create([
            'delivery_id' => $delivery->id,
            'delivery_agent_id' => $agent->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_meters' => $accuracyMeters,
            'heading_degrees' => $headingDegrees,
            'speed_kph' => $speedKph,
            'recorded_at' => now(),
        ]);

        event(new AgentLocationUpdated($delivery, $location));
    }

    private function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90 && $latitude <= 90
            && $longitude >= -180 && $longitude <= 180;
    }
}
