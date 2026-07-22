<?php

namespace Webkul\Marketplace\Logistics\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Webkul\Marketplace\Logistics\DTOs\QuoteRequest;
use Webkul\Marketplace\Models\DeliveryQuote;
use Webkul\Marketplace\Models\LogisticsServiceType;

/**
 * Builds the real, priced "Choose a Delivery Service" list at checkout: one
 * row per active service type whose provider adapter can actually serve the
 * requested route right now. Each returned quote is persisted with a short
 * expiry so DeliveryService can re-validate the exact same price
 * server-side instead of trusting whatever the client submits.
 */
class DeliveryQuoteService
{
    private const QUOTE_TTL_MINUTES = 20;

    /**
     * @return Collection<int, array{service_type: LogisticsServiceType, quote: \Webkul\Marketplace\Logistics\DTOs\QuoteResult, record: DeliveryQuote}>
     */
    public function eligibleQuotes(
        ?int $cartId,
        ?int $sellerId,
        float $pickupLatitude,
        float $pickupLongitude,
        float $dropoffLatitude,
        float $dropoffLongitude,
    ): Collection {
        return LogisticsServiceType::with('provider')
            ->where('is_active', true)
            ->whereHas('provider', fn ($query) => $query->where('is_active', true))
            ->get()
            ->map(function (LogisticsServiceType $serviceType) use ($pickupLatitude, $pickupLongitude, $dropoffLatitude, $dropoffLongitude, $cartId, $sellerId) {
                $request = new QuoteRequest($serviceType, $pickupLatitude, $pickupLongitude, $dropoffLatitude, $dropoffLongitude);

                $quote = $serviceType->provider->adapter()->quote($request);

                if (! $quote->available) {
                    return null;
                }

                $record = DeliveryQuote::create([
                    'quote_token' => (string) Str::uuid(),
                    'cart_id' => $cartId,
                    'seller_id' => $sellerId,
                    'logistics_provider_id' => $serviceType->logistics_provider_id,
                    'logistics_service_type_id' => $serviceType->id,
                    'pickup_latitude' => $request->pickupLatitude,
                    'pickup_longitude' => $request->pickupLongitude,
                    'dropoff_latitude' => $request->dropoffLatitude,
                    'dropoff_longitude' => $request->dropoffLongitude,
                    'distance_km' => $quote->distanceKm,
                    'duration_minutes' => $quote->durationMinutes,
                    'fee_minor' => $quote->feeMinor,
                    'currency_code' => $quote->currencyCode,
                    'expires_at' => now()->addMinutes(self::QUOTE_TTL_MINUTES),
                ]);

                return [
                    'service_type' => $serviceType,
                    'quote' => $quote,
                    'record' => $record,
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * Re-checks a previously issued quote token is still valid (not
     * expired, not already used) before checkout is allowed to proceed with
     * it. This is the server-side re-validation the checkout total must
     * always go through instead of trusting a client-submitted price.
     */
    public function validate(string $quoteToken): ?DeliveryQuote
    {
        $quote = DeliveryQuote::where('quote_token', $quoteToken)->first();

        if (! $quote || $quote->isExpired() || $quote->isConsumed()) {
            return null;
        }

        return $quote;
    }
}
