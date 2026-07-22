<?php

namespace Webkul\Marketplace\Logistics\Services;

use Illuminate\Support\Facades\DB;
use Webkul\Marketplace\Logistics\DTOs\DeliveryRequest;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\DeliveryQuote;
use Webkul\Marketplace\Models\Seller;
use Webkul\Sales\Models\Order;

/**
 * Turns a validated DeliveryQuote into a real Delivery once an order has
 * actually been placed, and drives it through the provider's
 * create-delivery + agent-search steps. This is deliberately the only
 * entry point that creates delivery rows - `deliveries.order_id` is unique,
 * so calling this twice for the same order fails loudly instead of quietly
 * creating a duplicate delivery.
 */
class DeliveryService
{
    public function __construct(
        protected DeliveryStateMachine $stateMachine,
        protected AgentAssignmentService $agentAssignment,
    ) {}

    public function createFromQuote(Order $order, Seller $seller, DeliveryQuote $quote, string $pickupAddress, string $dropoffAddress): Delivery
    {
        return DB::transaction(function () use ($order, $seller, $quote, $pickupAddress, $dropoffAddress) {
            $delivery = Delivery::create([
                'order_id' => $order->id,
                'seller_id' => $seller->id,
                'customer_id' => $order->customer_id,
                'logistics_provider_id' => $quote->logistics_provider_id,
                'logistics_service_type_id' => $quote->logistics_service_type_id,
                'pickup_address' => $pickupAddress,
                'pickup_latitude' => $quote->pickup_latitude,
                'pickup_longitude' => $quote->pickup_longitude,
                'dropoff_address' => $dropoffAddress,
                'dropoff_latitude' => $quote->dropoff_latitude,
                'dropoff_longitude' => $quote->dropoff_longitude,
                'status' => Delivery::STATUS_REQUESTED,
                'distance_km' => $quote->distance_km,
                'duration_minutes_estimate' => $quote->duration_minutes,
                'fee_minor' => $quote->fee_minor,
                'currency_code' => $quote->currency_code,
                'pickup_verification_code' => $this->generateVerificationCode(),
                'dropoff_verification_code' => $this->generateVerificationCode(),
                'requested_at' => now(),
            ]);

            $quote->update(['consumed_at' => now()]);

            $provider = $delivery->provider;

            $accepted = $provider->adapter()->createDelivery(new DeliveryRequest(
                deliveryId: $delivery->id,
                pickupAddress: $pickupAddress,
                pickupLatitude: (float) $delivery->pickup_latitude,
                pickupLongitude: (float) $delivery->pickup_longitude,
                dropoffAddress: $dropoffAddress,
                dropoffLatitude: (float) $delivery->dropoff_latitude,
                dropoffLongitude: (float) $delivery->dropoff_longitude,
            ));

            if (! $accepted) {
                return $this->stateMachine->transition($delivery, Delivery::STATUS_FAILED, note: 'Provider rejected the delivery request.');
            }

            $delivery = $this->stateMachine->transition($delivery, Delivery::STATUS_SEARCHING_AGENT);

            // Internal/vendor-managed providers assign one of our own
            // agents synchronously; third-party providers report their
            // agent asynchronously via webhook instead.
            if ($provider->adapter()->supportsLiveTracking() && in_array($provider->type, ['internal', 'vendor_managed'], true)) {
                $this->agentAssignment->assignNearestAvailable($delivery);
            }

            return $delivery->fresh();
        });
    }

    private function generateVerificationCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
