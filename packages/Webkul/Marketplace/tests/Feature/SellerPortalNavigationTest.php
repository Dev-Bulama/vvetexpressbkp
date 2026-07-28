<?php

use function Pest\Laravel\get;

/**
 * Regression test for a real bug: the products list/create/edit pages were
 * standalone HTML documents with no sidebar navigation at all (unlike
 * dashboard/POS, which already used the shared seller shell), and that
 * shell had no responsive breakpoint - the sidebar was a fixed-width flex
 * column that squeezed real content into a sliver next to it on mobile.
 * These assert every seller page shares the same shell/navigation, which
 * is what carries the responsive fix to all of them at once.
 */
it('shows the shared seller navigation on the products list page', function () {
    $seller = $this->makeTestSeller();
    $this->actingAs($seller, 'seller');

    $response = get(route('marketplace.seller.products.index'));

    $response->assertOk();
    $response->assertSee('VetExpress');
    $response->assertSee('Point of Sale');
    $response->assertSee('Dashboard');
});

it('shows the shared seller navigation on the add product page', function () {
    $seller = $this->makeTestSeller();
    $this->actingAs($seller, 'seller');

    $response = get(route('marketplace.seller.products.create'));

    $response->assertOk();
    $response->assertSee('VetExpress');
    $response->assertSee('Dashboard');
});

it('shows the shared seller navigation on the edit offer page', function () {
    $seller = $this->makeTestSeller();
    $product = $this->makeTestProduct();
    $offer = $this->makeTestOffer($seller, $product, quantity: 5);

    $this->actingAs($seller, 'seller');

    $response = get(route('marketplace.seller.products.edit', $offer->id));

    $response->assertOk();
    $response->assertSee('VetExpress');
    $response->assertSee('Point of Sale');
});

it('sends no-store cache headers on seller pages so a stale login form can never be replayed', function () {
    $response = get(route('marketplace.seller.session.index'));

    $response->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});
