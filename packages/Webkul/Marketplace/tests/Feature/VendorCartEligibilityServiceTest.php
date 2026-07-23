<?php

use Webkul\Marketplace\Services\VendorCartEligibilityService;

beforeEach(function () {
    $this->service = app(VendorCartEligibilityService::class);
});

it('marks a vendor eligible when it has every product and enough quantity', function () {
    $productA = $this->makeTestProduct(price: 100);
    $productB = $this->makeTestProduct(price: 50);
    $seller = $this->makeTestSeller();

    $this->makeTestOffer($seller, $productA, quantity: 10);
    $this->makeTestOffer($seller, $productB, quantity: 5);

    $cart = $this->makeTestCart([[$productA, 2], [$productB, 1]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible->count())->toBe(1);
    expect($result->eligible->first()->seller->id)->toBe($seller->id);
});

it('excludes a vendor missing one required product entirely', function () {
    $productA = $this->makeTestProduct();
    $productB = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // Seller only stocks product A, not product B.
    $this->makeTestOffer($seller, $productA, quantity: 10);

    $cart = $this->makeTestCart([[$productA, 1], [$productB, 1]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible)->toBeEmpty();

    $evaluated = $result->evaluated->firstWhere('seller.id', $seller->id);
    expect($evaluated->eligible)->toBeFalse();
    expect($evaluated->missing->first()->reason)->toBe('missing_product');
});

it('excludes a vendor with insufficient quantity of a required product', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    // Seller has only 1 unit but the cart wants 2.
    $this->makeTestOffer($seller, $product, quantity: 1);

    $cart = $this->makeTestCart([[$product, 2]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible)->toBeEmpty();

    $evaluated = $result->evaluated->firstWhere('seller.id', $seller->id);
    expect($evaluated->missing->first()->reason)->toBe('insufficient_quantity');
    expect($evaluated->missing->first()->available_quantity)->toBe(1);
    expect($evaluated->missing->first()->required_quantity)->toBe(2);
});

it('excludes a vendor whose offer for the product is inactive', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    $offer = $this->makeTestOffer($seller, $product, quantity: 10);
    $offer->update(['is_active' => false]);

    $cart = $this->makeTestCart([[$product, 1]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible)->toBeEmpty();

    $evaluated = $result->evaluated->firstWhere('seller.id', $seller->id);
    expect($evaluated->missing->first()->reason)->toBe('product_inactive');
});

it('accounts for reserved quantity when determining available stock', function () {
    $product = $this->makeTestProduct();
    $seller = $this->makeTestSeller();

    $offer = $this->makeTestOffer($seller, $product, quantity: 5);
    $offer->update(['reserved_quantity' => 4]);

    // Only 1 unit truly available (5 - 4), cart wants 2.
    $cart = $this->makeTestCart([[$product, 2]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible)->toBeEmpty();

    $evaluated = $result->evaluated->firstWhere('seller.id', $seller->id);
    expect($evaluated->missing->first()->available_quantity)->toBe(1);
});

it('excludes every vendor and reports none eligible when no single vendor covers the whole cart', function () {
    // Mirrors the spec's example: three products, three vendors each
    // missing something different.
    $productA = $this->makeTestProduct();
    $productB = $this->makeTestProduct();
    $productC = $this->makeTestProduct();

    $vendorA = $this->makeTestSeller();
    $this->makeTestOffer($vendorA, $productA, quantity: 10);
    $this->makeTestOffer($vendorA, $productB, quantity: 5);
    // Vendor A is missing product C entirely.

    $vendorB = $this->makeTestSeller();
    $this->makeTestOffer($vendorB, $productA, quantity: 1); // wants 2 - insufficient
    $this->makeTestOffer($vendorB, $productB, quantity: 5);
    $this->makeTestOffer($vendorB, $productC, quantity: 9);

    $cart = $this->makeTestCart([
        [$productA, 2],
        [$productB, 1],
        [$productC, 4],
    ]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible)->toBeEmpty();

    // The shared dev database has other approved sellers too (none of
    // which stock these brand-new test products at all, so they're
    // correctly excluded as missing_product) - assert on our two test
    // vendors specifically rather than the total evaluated count.
    $evaluatedA = $result->evaluated->firstWhere('seller.id', $vendorA->id);
    $evaluatedB = $result->evaluated->firstWhere('seller.id', $vendorB->id);

    expect($evaluatedA->eligible)->toBeFalse();
    expect($evaluatedB->eligible)->toBeFalse();
});

it('ranks eligible vendors and does not include an ineligible vendor in the ranked list', function () {
    $product = $this->makeTestProduct();

    $completeVendor = $this->makeTestSeller(['rating' => 4.9]);
    $this->makeTestOffer($completeVendor, $product, quantity: 10);

    $incompleteVendor = $this->makeTestSeller(['rating' => 5.0]);
    $this->makeTestOffer($incompleteVendor, $product, quantity: 0);

    $cart = $this->makeTestCart([[$product, 1]]);

    $result = $this->service->evaluate($cart);

    expect($result->eligible->count())->toBe(1);
    expect($result->eligible->first()->seller->id)->toBe($completeVendor->id);
});
