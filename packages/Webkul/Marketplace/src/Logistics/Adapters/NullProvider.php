<?php

namespace Webkul\Marketplace\Logistics\Adapters;

use Webkul\Marketplace\Logistics\Contracts\LogisticsProviderAdapter;
use Webkul\Marketplace\Logistics\DTOs\DeliveryRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteRequest;
use Webkul\Marketplace\Logistics\DTOs\QuoteResult;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\LogisticsProvider;

/**
 * The graceful "disabled" state for any provider that has no real, working
 * integration yet - either because it needs official API credentials we
 * don't have, or because an admin hasn't finished configuring it. It must
 * never be mistaken for a connected provider: every method reports
 * unavailable/false/null rather than faking a result.
 */
class NullProvider implements LogisticsProviderAdapter
{
    public function __construct(protected ?LogisticsProvider $provider = null) {}

    public function isAvailable(): bool
    {
        return false;
    }

    public function quote(QuoteRequest $request): QuoteResult
    {
        return QuoteResult::unavailable(
            $this->provider
                ? "{$this->provider->name} is not configured yet - missing API credentials."
                : 'This logistics provider is not configured.'
        );
    }

    public function createDelivery(DeliveryRequest $request): bool
    {
        return false;
    }

    public function cancelDelivery(Delivery $delivery, string $reason): bool
    {
        return false;
    }

    public function fetchStatus(Delivery $delivery): ?string
    {
        return null;
    }

    public function parseWebhook(array $payload, array $headers): ?array
    {
        return null;
    }

    public function supportsLiveTracking(): bool
    {
        return false;
    }
}
