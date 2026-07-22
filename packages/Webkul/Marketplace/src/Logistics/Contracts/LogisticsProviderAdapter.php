<?php

namespace Webkul\Marketplace\Logistics\Contracts;

use Webkul\Marketplace\Logistics\DTOs\DeliveryRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteResult;
use Webkul\Marketplace\Models\Delivery;

/**
 * A logistics provider adapter wraps everything specific to one delivery
 * source - our own internal agent pool, a vendor's own delivery staff, or a
 * real third-party courier API - behind one interface, so the checkout,
 * admin, and delivery-tracking code never need to know which kind of
 * provider they're talking to.
 *
 * A provider with no real API/credentials configured MUST implement this by
 * reporting itself unavailable everywhere (see NullProvider), never by
 * fabricating a connected state.
 */
interface LogisticsProviderAdapter
{
    /**
     * Whether this adapter can actually be used right now (credentials
     * present, service reachable, etc) - independent of whether it can
     * serve a specific route.
     */
    public function isAvailable(): bool;

    /**
     * Price and time estimate for one pickup -> dropoff route. Must return
     * QuoteResult::unavailable() rather than guessing when the provider
     * can't serve the route (out of range, no coverage, API error).
     */
    public function quote(QuoteRequest $request): QuoteResult;

    /**
     * Create the delivery with the provider (assign one of our own agents
     * for the internal adapter, call a real courier API for a third-party
     * one). Returns true once the provider has acknowledged the request;
     * the actual agent assignment/ETA arrives asynchronously via
     * assignAgent()/webhook for third-party providers.
     */
    public function createDelivery(DeliveryRequest $request): bool;

    public function cancelDelivery(Delivery $delivery, string $reason): bool;

    /**
     * Pull the provider's current view of delivery status. Third-party
     * adapters without a real polling endpoint should rely on
     * receiveWebhook() instead and can return null here.
     */
    public function fetchStatus(Delivery $delivery): ?string;

    /**
     * Validate and normalize an inbound provider webhook payload into a
     * (external_event_id, event_type, data) tuple for the caller to persist
     * idempotently. Return null to reject an unverifiable payload.
     */
    public function parseWebhook(array $payload, array $headers): ?array;

    public function supportsLiveTracking(): bool;
}
