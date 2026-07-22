<?php

namespace Webkul\Marketplace\Logistics\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\DeliveryAgent;
use Webkul\Marketplace\Support\GeoDistance;

/**
 * Finds and assigns one of our own available delivery_agents to an internal
 * delivery. Third-party providers assign their own agents and report them
 * back via webhook/status polling instead - this service only applies to
 * providers whose adapter is InternalMarketplaceProvider (or another
 * provider type backed by our own agent pool).
 */
class AgentAssignmentService
{
    public function __construct(protected DeliveryStateMachine $stateMachine) {}

    /**
     * Locks and assigns the nearest available agent for this delivery's
     * provider, then advances the delivery to agent_assigned. Returns null
     * (leaving the delivery in searching_agent) if no agent is free right
     * now - this is a real "no capacity" outcome, not an error.
     */
    public function assignNearestAvailable(Delivery $delivery): ?DeliveryAgent
    {
        return DB::transaction(function () use ($delivery) {
            $candidates = DeliveryAgent::where('logistics_provider_id', $delivery->logistics_provider_id)
                ->where('status', DeliveryAgent::STATUS_AVAILABLE)
                ->where('is_active', true)
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude')
                ->lockForUpdate()
                ->get();

            if ($candidates->isEmpty()) {
                return null;
            }

            $nearest = $candidates
                ->sortBy(fn (DeliveryAgent $agent) => GeoDistance::haversineKm(
                    (float) $agent->current_latitude,
                    (float) $agent->current_longitude,
                    (float) $delivery->pickup_latitude,
                    (float) $delivery->pickup_longitude,
                ))
                ->first();

            $nearest->update(['status' => DeliveryAgent::STATUS_ON_DELIVERY]);

            $delivery->delivery_agent_id = $nearest->id;
            $delivery->save();

            $this->stateMachine->transition(
                $delivery,
                Delivery::STATUS_AGENT_ASSIGNED,
                actorType: 'system',
                note: "Assigned agent #{$nearest->id} ({$nearest->name})"
            );

            return $nearest;
        });
    }
}
