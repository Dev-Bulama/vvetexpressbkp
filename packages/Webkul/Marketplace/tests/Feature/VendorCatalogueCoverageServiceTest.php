<?php

use Webkul\Marketplace\Models\FailedCartMatch;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Marketplace\Services\VendorCatalogueCoverageService;

beforeEach(function () {
    $this->service = app(VendorCatalogueCoverageService::class);
});

it('computes coverage stats for a seller relative to the real active catalogue', function () {
    $stockedProduct = $this->makeTestProduct();
    $unstockedProduct = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    $this->makeTestOffer($seller, $stockedProduct, quantity: 10);

    // Ground truth: ask the service itself for the total active-product
    // count, since the shared dev database already has real demo
    // products beyond the two created here.
    $totalActive = $this->service->activeProductIds()->count();

    $coverage = $this->service->forSeller($seller);

    expect($coverage->total_active_products)->toBe($totalActive);
    expect($coverage->stocked_products)->toBe(1);
    expect($coverage->missing_products)->toBe($totalActive - 1);
    expect($coverage->coverage_percent)->toBe(round((1 / $totalActive) * 100, 2));
    expect($coverage->is_complete)->toBeFalse();
});

it('marks a seller complete only when every active product is stocked with healthy quantity', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // Stock every currently-active product (including pre-existing demo
    // ones) so this seller is unambiguously complete.
    foreach ($this->service->activeProductIds() as $productId) {
        SellerProduct::create([
            'seller_id' => $seller->id,
            'product_id' => $productId,
            'price' => 10,
            'quantity' => 20,
            'is_active' => true,
        ]);
    }

    $coverage = $this->service->forSeller($seller);

    expect($coverage->is_complete)->toBeTrue();
    expect($coverage->status)->toBe('complete');
    expect($coverage->missing_products)->toBe(0);
    expect($coverage->coverage_percent)->toBe(100.0);
});

it('flags out-of-stock and low-stock products separately from missing ones', function () {
    $outOfStockProduct = $this->makeTestProduct();
    $lowStockProduct = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    $this->makeTestOffer($seller, $outOfStockProduct, quantity: 0);
    $this->makeTestOffer($seller, $lowStockProduct, quantity: 2); // <= LOW_STOCK_THRESHOLD (5)

    $coverage = $this->service->forSeller($seller);

    expect($coverage->out_of_stock_products)->toBe(1);
    expect($coverage->low_stock_products)->toBe(1);
    expect($coverage->insufficient_quantity_products)->toBe(2);

    $outOfStockList = $this->service->outOfStockProducts($seller);
    $lowStockList = $this->service->lowStockProducts($seller);

    expect($outOfStockList->pluck('product_id'))->toContain($outOfStockProduct->id);
    expect($lowStockList->pluck('product_id'))->toContain($lowStockProduct->id);
});

it('lists missing products annotated with a demand level derived from real failed cart matches', function () {
    $missingProduct = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // Seller stocks nothing, so $missingProduct is missing for it.
    // Record 4 failed cart matches naming this product - crosses the
    // "medium" demand threshold (>= 3) but not "high" (>= 10).
    for ($i = 0; $i < 4; $i++) {
        FailedCartMatch::create([
            'cart_snapshot' => [['product_id' => $missingProduct->id, 'quantity' => 1]],
            'cart_value' => 100,
            'vendors_evaluated' => [],
            'customer_action' => 'none',
        ]);
    }

    $missing = $this->service->missingProducts($seller);
    $row = $missing->firstWhere('product_id', $missingProduct->id);

    expect($row)->not->toBeNull();
    expect($row->failed_cart_match_count)->toBe(4);
    expect($row->demand_level)->toBe('medium');
});

it('computes an almost-eligible summary from recorded failed cart match history without exposing it as a selectable vendor', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // This vendor was 75% (>= the 50% default threshold) covered on one
    // failed match and should count toward the almost-eligible summary.
    FailedCartMatch::create([
        'cart_snapshot' => [['product_id' => $product->id, 'quantity' => 1]],
        'cart_value' => 250.0,
        'vendors_evaluated' => [
            [
                'seller_id' => $seller->id,
                'coverage_fraction' => 0.75,
                'missing' => [['product_id' => $product->id]],
            ],
        ],
        'customer_action' => 'none',
    ]);

    // A second match where this vendor barely qualifies (below threshold)
    // must NOT be counted.
    FailedCartMatch::create([
        'cart_snapshot' => [['product_id' => $product->id, 'quantity' => 1]],
        'cart_value' => 999.0,
        'vendors_evaluated' => [
            [
                'seller_id' => $seller->id,
                'coverage_fraction' => 0.1,
                'missing' => [['product_id' => $product->id]],
            ],
        ],
        'customer_action' => 'none',
    ]);

    $summary = $this->service->almostEligibleSummary($seller);

    expect($summary->missed_match_count)->toBe(1);
    expect($summary->estimated_lost_value)->toBe(250.0);
    expect($summary->most_common_missing_product_ids)->toContain($product->id);
});
