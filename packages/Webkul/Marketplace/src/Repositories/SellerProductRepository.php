<?php

namespace Webkul\Marketplace\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Eloquent\Repository;

class SellerProductRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Marketplace\Contracts\SellerProduct';
    }

    /**
     * Every active, in-stock vendor offer for a given catalog product, each
     * annotated with distance from the given coordinates (when provided) and
     * a composite recommendation score. Sorted best-first.
     */
    public function findOffersForProduct(int $productId, ?float $latitude = null, ?float $longitude = null): Collection
    {
        $query = DB::table('marketplace_seller_products as offers')
            ->join('marketplace_sellers as sellers', 'sellers.id', '=', 'offers.seller_id')
            ->where('offers.product_id', $productId)
            ->where('offers.is_active', true)
            ->where('offers.quantity', '>', 0)
            ->where('sellers.status', 'approved')
            ->select([
                'offers.id as offer_id',
                'offers.seller_id',
                'offers.price',
                'offers.quantity',
                'sellers.name as seller_name',
                'sellers.shop_name',
                'sellers.city',
                'sellers.latitude',
                'sellers.longitude',
            ]);

        if ($latitude !== null && $longitude !== null) {
            $query->selectRaw(
                '(6371 * acos(least(1, greatest(-1,
                    cos(radians(?)) * cos(radians(sellers.latitude)) *
                    cos(radians(sellers.longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(sellers.latitude))
                )))) as distance_km',
                [$latitude, $longitude, $latitude]
            );
        }

        $offers = collect($query->get());

        if ($offers->isEmpty()) {
            return $offers;
        }

        return $this->rankByRecommendation($offers);
    }

    /**
     * Annotate each offer with a 0-1 recommendation score (higher is better)
     * combining price and distance, and sort best-first. Cheapest and
     * nearest each contribute equally; an offer with no distance available
     * is scored on price alone.
     */
    private function rankByRecommendation(Collection $offers): Collection
    {
        $prices = $offers->pluck('price')->map(fn ($p) => (float) $p);
        $minPrice = $prices->min();
        $maxPrice = $prices->max();
        $priceRange = $maxPrice - $minPrice;

        $distances = $offers->pluck('distance_km')->filter(fn ($d) => $d !== null)->map(fn ($d) => (float) $d);
        $minDistance = $distances->min();
        $maxDistance = $distances->max();
        $distanceRange = $distances->isNotEmpty() ? $maxDistance - $minDistance : null;

        $ranked = $offers->map(function ($offer) use ($minPrice, $priceRange, $minDistance, $distanceRange) {
            $priceScore = $priceRange > 0
                ? 1 - ((((float) $offer->price) - $minPrice) / $priceRange)
                : 1.0;

            $hasDistance = $distanceRange !== null && isset($offer->distance_km) && $offer->distance_km !== null;

            $distanceScore = $hasDistance
                ? ($distanceRange > 0 ? 1 - ((((float) $offer->distance_km) - $minDistance) / $distanceRange) : 1.0)
                : null;

            $offer->score = $distanceScore === null
                ? $priceScore
                : ($priceScore * 0.5) + ($distanceScore * 0.5);

            return $offer;
        });

        return $ranked
            ->sortBy([
                ['score', 'desc'],
                fn ($a, $b) => ($a->distance_km ?? PHP_FLOAT_MAX) <=> ($b->distance_km ?? PHP_FLOAT_MAX),
            ])
            ->values();
    }
}
