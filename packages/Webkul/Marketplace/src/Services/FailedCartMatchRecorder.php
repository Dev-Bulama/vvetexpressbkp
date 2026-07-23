<?php

namespace Webkul\Marketplace\Services;

use Webkul\Checkout\Models\Cart;
use Webkul\Marketplace\Models\FailedCartMatch;

/**
 * Logs the moment no single vendor can fulfil a customer's complete cart -
 * the data source for admin's failed-cart-match reporting and for deciding
 * which vendors are "almost eligible" and worth a restock reminder.
 *
 * Deduplicated per cart per cooldown window so refreshing the checkout
 * vendor page repeatedly doesn't flood the table with identical rows.
 */
class FailedCartMatchRecorder
{
    protected const COOLDOWN_MINUTES = 30;

    public function record(Cart $cart, object $evaluation, ?float $latitude, ?float $longitude): ?FailedCartMatch
    {
        $customerId = auth()->guard('customer')->id();
        $guestSessionId = $customerId ? null : session()->getId();

        if ($this->recentlyRecorded($cart, $customerId, $guestSessionId)) {
            return null;
        }

        $cartSnapshot = $evaluation->lines->map(fn ($line) => [
            'product_id' => $line->product_id,
            'name' => $line->name,
            'sku' => $line->sku,
            'quantity' => $line->quantity,
        ])->values()->all();

        $vendorsEvaluated = $evaluation->evaluated->map(fn ($row) => [
            'seller_id' => $row->seller->id,
            'shop_name' => $row->seller->shop_name,
            'coverage_fraction' => round($row->coverageFraction, 4),
            'missing' => $row->missing->map(fn ($m) => [
                'product_id' => $m->product_id,
                'name' => $m->name,
                'reason' => $m->reason,
                'required_quantity' => $m->required_quantity,
                'available_quantity' => $m->available_quantity,
            ])->values()->all(),
            'distance_km' => $row->distance_km,
            'location_ok' => $row->locationOk,
            'open_ok' => $row->openOk,
        ])->values()->all();

        $nearestVendorId = $evaluation->evaluated
            ->filter(fn ($row) => $row->distance_km !== null)
            ->sortBy('distance_km')
            ->first()?->seller->id;

        $nearestAlmostEligibleId = $evaluation->almostEligible->first()?->seller->id;

        $cartValue = $cart->items->sum(fn ($item) => (float) $item->total);

        return FailedCartMatch::create([
            'customer_id' => $customerId,
            'guest_session_id' => $guestSessionId,
            'customer_latitude' => $latitude,
            'customer_longitude' => $longitude,
            'cart_snapshot' => $cartSnapshot,
            'cart_value' => $cartValue,
            'vendors_evaluated' => $vendorsEvaluated,
            'nearest_vendor_id' => $nearestVendorId,
            'nearest_almost_eligible_vendor_id' => $nearestAlmostEligibleId,
        ]);
    }

    protected function recentlyRecorded(Cart $cart, ?int $customerId, ?string $guestSessionId): bool
    {
        $query = FailedCartMatch::where('created_at', '>=', now()->subMinutes(self::COOLDOWN_MINUTES));

        if ($customerId) {
            $query->where('customer_id', $customerId);
        } else {
            $query->where('guest_session_id', $guestSessionId);
        }

        return $query->exists();
    }
}
