<?php

namespace Webkul\Marketplace\Services;

use Illuminate\Support\Collection;
use Webkul\Checkout\Models\Cart;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Marketplace\Repositories\SellerProductRepository;

/**
 * The single source of truth for "can this vendor fulfil this entire cart".
 *
 * VetExpress's rule is one buyer -> one complete vendor -> one order: a
 * vendor is only ever recommended, selectable, or assignable to an order
 * when it holds every product, the exact variation, and the required
 * quantity for every line in the cart - after excluding reserved stock -
 * and is itself active, approved, and serving the customer's location.
 * Partial catalogue coverage never qualifies a vendor.
 *
 * Used identically at three points so eligibility can never drift between
 * them: the checkout vendor-recommendation step, the pre-payment
 * revalidation, and the pre-order-creation revalidation.
 */
class VendorCartEligibilityService
{
    public function __construct(protected SellerProductRepository $sellerProductRepository) {}

    /**
     * Resolve the cart's real purchasable lines. For a configurable
     * product, Bagisto stores the parent (type=configurable) as the
     * top-level cart item and the actual sold variant as its child - the
     * child's product_id is what a vendor's catalogue is actually checked
     * against, since that's the exact variation the customer selected.
     *
     * @return Collection<int, object{product_id: int, quantity: int, name: string, sku: ?string}>
     */
    public function cartLines(Cart $cart): Collection
    {
        return $cart->items->map(function ($item) {
            $variant = $item->child ?: $item;

            return (object) [
                'product_id' => $variant->product_id,
                'quantity' => (int) $item->quantity,
                'name' => $item->name,
                'sku' => $item->sku,
            ];
        })->values();
    }

    /**
     * Evaluate every active, approved, delivery-enabled seller against the
     * cart. Returns eligible vendors ranked best-first, almost-eligible
     * vendors (missing less than half the cart, for admin follow-up only -
     * never shown to the customer as selectable), and the full per-seller
     * diagnostic used for failed-cart-match logging.
     */
    public function evaluate(Cart $cart, ?float $latitude = null, ?float $longitude = null): object
    {
        $lines = $this->cartLines($cart);

        $sellers = Seller::where('status', Seller::STATUS_APPROVED)
            ->where('is_delivery_enabled', true)
            ->get();

        $evaluated = $sellers->map(
            fn (Seller $seller) => $this->evaluateSeller($seller, $lines, $latitude, $longitude)
        )->values();

        $eligible = $this->rank($evaluated->filter(fn ($row) => $row->eligible)->values());

        $almostEligible = $evaluated
            ->filter(fn ($row) => ! $row->eligible && $row->coverageFraction >= 0.5)
            ->sortByDesc('coverageFraction')
            ->values();

        return (object) [
            'lines' => $lines,
            'eligible' => $eligible,
            'almostEligible' => $almostEligible,
            'evaluated' => $evaluated,
        ];
    }

    /**
     * Full diagnostic eligibility check for one seller against a resolved
     * set of cart lines. This is the single rule enforcement point - the
     * same method backs the checkout listing, the "is my selection still
     * valid" revalidation, and admin catalogue-coverage reporting.
     */
    public function evaluateSeller(Seller $seller, Collection $lines, ?float $latitude = null, ?float $longitude = null): object
    {
        $missing = collect();

        $offers = SellerProduct::where('seller_id', $seller->id)
            ->whereIn('product_id', $lines->pluck('product_id'))
            ->get()
            ->keyBy('product_id');

        foreach ($lines as $line) {
            $offer = $offers->get($line->product_id);

            if (! $offer) {
                $missing->push((object) [
                    'product_id' => $line->product_id,
                    'name' => $line->name,
                    'reason' => 'missing_product',
                    'required_quantity' => $line->quantity,
                    'available_quantity' => 0,
                ]);

                continue;
            }

            $available = max(0, $offer->quantity - $offer->reserved_quantity);

            if (! $offer->is_active) {
                $missing->push((object) [
                    'product_id' => $line->product_id,
                    'name' => $line->name,
                    'reason' => 'product_inactive',
                    'required_quantity' => $line->quantity,
                    'available_quantity' => $available,
                ]);

                continue;
            }

            if ($available < $line->quantity) {
                $missing->push((object) [
                    'product_id' => $line->product_id,
                    'name' => $line->name,
                    'reason' => 'insufficient_quantity',
                    'required_quantity' => $line->quantity,
                    'available_quantity' => $available,
                ]);
            }
        }

        $distance = ($latitude !== null && $longitude !== null && $seller->latitude && $seller->longitude)
            ? $this->haversineKm($latitude, $longitude, (float) $seller->latitude, (float) $seller->longitude)
            : null;

        $locationOk = $this->sellerProductRepository->isWithinServiceArea((object) [
            'distance_km' => $distance,
            'service_radius_km' => $seller->service_radius_km,
        ]);

        $openOk = $this->sellerProductRepository->isCurrentlyOpen((object) [
            'opening_time' => $seller->opening_time,
            'closing_time' => $seller->closing_time,
        ]);

        return (object) [
            'seller' => $seller,
            'eligible' => $missing->isEmpty() && $locationOk && $openOk,
            'missing' => $missing,
            'coverageFraction' => $lines->isEmpty() ? 1.0 : (($lines->count() - $missing->count()) / $lines->count()),
            'distance_km' => $distance,
            'locationOk' => $locationOk,
            'openOk' => $openOk,
        ];
    }

    /**
     * Re-run evaluateSeller() for one specific seller - used to revalidate
     * a customer's already-made selection right before payment and again
     * right before the order is actually created, since stock can change
     * between checkout steps.
     */
    public function isStillEligible(Seller $seller, Cart $cart, ?float $latitude = null, ?float $longitude = null): object
    {
        return $this->evaluateSeller($seller, $this->cartLines($cart), $latitude, $longitude);
    }

    /**
     * Ranks eligible vendors by the same distance-first, rating-second
     * philosophy already used for per-product recommendations elsewhere in
     * this package, adapted to a whole-cart context (every candidate here
     * already fully covers the cart, so price/coverage no longer
     * differentiate them - distance and rating do).
     */
    protected function rank(Collection $eligible): Collection
    {
        $distances = $eligible->pluck('distance_km')->filter(fn ($d) => $d !== null)->map(fn ($d) => (float) $d);
        $minDistance = $distances->min();
        $maxDistance = $distances->max();
        $distanceRange = $distances->isNotEmpty() ? $maxDistance - $minDistance : null;

        $scored = $eligible->map(function ($row) use ($minDistance, $distanceRange) {
            $hasDistance = $distanceRange !== null && $row->distance_km !== null;

            $distanceScore = $hasDistance
                ? ($distanceRange > 0 ? 1 - ((((float) $row->distance_km) - $minDistance) / $distanceRange) : 1.0)
                : 0.5;

            $ratingScore = min(1.0, max(0.0, ((float) ($row->seller->rating ?? 0)) / 5));

            $row->score = ($distanceScore * 0.6) + ($ratingScore * 0.4);

            $distance = $row->distance_km;

            $row->delivery_fee = $distance === null ? 1200.0 : round(500 + ($distance * 150), -1);

            $row->eta_label = match (true) {
                $distance === null => 'Delivery time varies by location',
                $distance < 2 => '20-30 min',
                $distance < 5 => '30-45 min',
                $distance < 10 => '45-60 min',
                default => '60-90 min',
            };

            return $row;
        });

        return $scored
            ->sortBy([
                ['score', 'desc'],
                fn ($a, $b) => ($a->distance_km ?? PHP_FLOAT_MAX) <=> ($b->distance_km ?? PHP_FLOAT_MAX),
            ])
            ->values();
    }

    protected function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371;

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
