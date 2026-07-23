<?php

namespace Webkul\Marketplace\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Marketplace\Models\FailedCartMatch;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;

/**
 * Computes how completely a vendor's catalogue covers the platform's active
 * product range - separate from (and much looser than) cart-complete-order
 * eligibility. A vendor can be genuinely useful, "eligible", and profitable
 * while stocking only a fraction of the catalogue; this is about giving the
 * admin visibility into gaps and prioritising restock reminders, not about
 * penalising a vendor for a naturally partial catalogue.
 */
class VendorCatalogueCoverageService
{
    /**
     * A SellerProduct at or below this quantity (but still > 0) is "low
     * stock". Not yet admin-configurable - see the completion report for
     * why, and CoreConfig would be the natural home for that later.
     */
    public const LOW_STOCK_THRESHOLD = 5;

    /**
     * Every product currently active and visible on the storefront - the
     * denominator for every vendor's coverage percentage. Read from
     * product_flat (not the EAV products table) since status/visibility
     * are channel+locale-specific attributes stored there, matching how
     * the rest of this package already queries the catalogue.
     */
    public function activeProductIds(): Collection
    {
        return DB::table('product_flat')
            ->where('channel', core()->getCurrentChannel()->code)
            ->where('locale', app()->getLocale())
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->pluck('product_id');
    }

    public function forSeller(Seller $seller): object
    {
        $activeIds = $this->activeProductIds();
        $totalActive = $activeIds->count();

        $offers = SellerProduct::where('seller_id', $seller->id)
            ->whereIn('product_id', $activeIds)
            ->get();

        $stockedCount = $offers->count();
        $missingCount = max(0, $totalActive - $stockedCount);
        $outOfStockCount = $offers->where('quantity', '<=', 0)->count();
        $lowStockCount = $offers->filter(fn ($o) => $o->quantity > 0 && $o->quantity <= self::LOW_STOCK_THRESHOLD)->count();
        $coveragePercent = $totalActive > 0 ? round(($stockedCount / $totalActive) * 100, 2) : 0.0;
        $lastInventoryUpdate = $offers->max('updated_at');

        $isComplete = $missingCount === 0 && $outOfStockCount === 0 && $lowStockCount === 0;

        $status = match (true) {
            ! $seller->isApproved() => 'inactive',
            $isComplete => 'complete',
            $totalActive > 0 && $coveragePercent < 50 => 'incomplete',
            $outOfStockCount > 0 && $lowStockCount === 0 && $missingCount === 0 => 'out_of_stock',
            $lowStockCount > 0 && $missingCount === 0 && $outOfStockCount === 0 => 'low_stock',
            default => 'requires_attention',
        };

        return (object) [
            'seller' => $seller,
            'total_active_products' => $totalActive,
            'stocked_products' => $stockedCount,
            'missing_products' => $missingCount,
            'out_of_stock_products' => $outOfStockCount,
            'low_stock_products' => $lowStockCount,
            'insufficient_quantity_products' => $outOfStockCount + $lowStockCount,
            'coverage_percent' => $coveragePercent,
            'category_coverage_percent' => $this->categoryCoveragePercent($activeIds, $offers->pluck('product_id')),
            'last_inventory_update' => $lastInventoryUpdate,
            'is_complete' => $isComplete,
            'status' => $status,
            'eligible_for_recommendations' => $seller->isApproved() && $seller->is_delivery_enabled,
        ];
    }

    public function allVendorsCoverage(): Collection
    {
        return Seller::orderBy('shop_name')->get()->map(fn (Seller $seller) => $this->forSeller($seller));
    }

    /**
     * Active platform products this vendor doesn't stock at all, annotated
     * with a demand signal derived from real failed-cart-match history
     * (how often this product blocked a complete-cart match) rather than a
     * fabricated popularity score.
     */
    public function missingProducts(Seller $seller): Collection
    {
        $activeIds = $this->activeProductIds();
        $stockedIds = SellerProduct::where('seller_id', $seller->id)->pluck('product_id');
        $missingIds = $activeIds->diff($stockedIds)->values();

        if ($missingIds->isEmpty()) {
            return collect();
        }

        $demandCounts = $this->demandCounts($missingIds);

        return DB::table('product_flat')
            ->whereIn('product_id', $missingIds)
            ->where('channel', core()->getCurrentChannel()->code)
            ->where('locale', app()->getLocale())
            ->select('product_id', 'sku', 'name')
            ->get()
            ->map(function ($row) use ($demandCounts) {
                $row->failed_cart_match_count = $demandCounts[$row->product_id] ?? 0;
                $row->demand_level = $this->demandLevel($row->failed_cart_match_count);

                return $row;
            })
            ->sortByDesc('failed_cart_match_count')
            ->values();
    }

    public function outOfStockProducts(Seller $seller): Collection
    {
        return SellerProduct::where('seller_id', $seller->id)
            ->where('quantity', '<=', 0)
            ->with('product')
            ->get();
    }

    public function lowStockProducts(Seller $seller): Collection
    {
        return SellerProduct::where('seller_id', $seller->id)
            ->where('quantity', '>', 0)
            ->where('quantity', '<=', self::LOW_STOCK_THRESHOLD)
            ->with('product')
            ->get();
    }

    /**
     * How many recorded failed-cart-matches (in the last 90 days) named
     * each of the given products as a reason a vendor didn't qualify -
     * the real, observed-demand signal behind "high-demand missing
     * product" rather than a guessed one.
     *
     * @param  Collection<int, int>  $productIds
     * @return array<int, int> product_id => count
     */
    public function demandCounts(Collection $productIds): array
    {
        $counts = array_fill_keys($productIds->all(), 0);

        FailedCartMatch::where('created_at', '>=', now()->subDays(90))
            ->get(['cart_snapshot'])
            ->each(function ($match) use (&$counts) {
                foreach ($match->cart_snapshot ?? [] as $line) {
                    $productId = $line['product_id'] ?? null;

                    if ($productId !== null && array_key_exists($productId, $counts)) {
                        $counts[$productId]++;
                    }
                }
            });

        return $counts;
    }

    public function demandLevel(int $failedCartMatchCount): string
    {
        return match (true) {
            $failedCartMatchCount >= 10 => 'high',
            $failedCartMatchCount >= 3 => 'medium',
            default => 'low',
        };
    }

    /**
     * How often this vendor was "almost eligible" (missed a complete-cart
     * match by a small gap) in recorded failed-cart-matches, what
     * consistently blocked it, and the cart value that would have gone to
     * this vendor had it been complete - real numbers derived from
     * FailedCartMatch history, used for the admin's restock-follow-up view
     * (never shown to customers as a selectable vendor).
     */
    public function almostEligibleSummary(Seller $seller, float $minCoverageFraction = 0.5): object
    {
        $matches = FailedCartMatch::where('created_at', '>=', now()->subDays(90))->get();

        $missingProductCounts = [];
        $missedMatchCount = 0;
        $estimatedLostValue = 0.0;

        foreach ($matches as $match) {
            $vendorRow = collect($match->vendors_evaluated ?? [])->firstWhere('seller_id', $seller->id);

            if (! $vendorRow || $vendorRow['coverage_fraction'] < $minCoverageFraction) {
                continue;
            }

            $missedMatchCount++;
            $estimatedLostValue += (float) $match->cart_value;

            foreach ($vendorRow['missing'] ?? [] as $missing) {
                $key = $missing['product_id'];
                $missingProductCounts[$key] = ($missingProductCounts[$key] ?? 0) + 1;
            }
        }

        arsort($missingProductCounts);

        return (object) [
            'seller' => $seller,
            'missed_match_count' => $missedMatchCount,
            'estimated_lost_value' => round($estimatedLostValue, 2),
            'most_common_missing_product_ids' => array_slice(array_keys($missingProductCounts), 0, 5),
        ];
    }

    protected function categoryCoveragePercent(Collection $activeProductIds, Collection $vendorProductIds): float
    {
        if ($activeProductIds->isEmpty()) {
            return 0.0;
        }

        $allCategoryIds = DB::table('product_categories')->whereIn('product_id', $activeProductIds)->distinct()->pluck('category_id');

        if ($allCategoryIds->isEmpty()) {
            return 0.0;
        }

        $vendorCategoryIds = DB::table('product_categories')->whereIn('product_id', $vendorProductIds)->distinct()->pluck('category_id');

        return round(($vendorCategoryIds->intersect($allCategoryIds)->count() / $allCategoryIds->count()) * 100, 2);
    }
}
