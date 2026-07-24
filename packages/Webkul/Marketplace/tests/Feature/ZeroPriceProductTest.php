<?php

use Webkul\Checkout\Facades\Cart;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Regression test for a real bug found via a production error report: an
 * ERPNext-synced item with no configured standard_rate (0) got its price
 * attribute silently nulled out on save, because
 * ProductAttributeValueRepository::saveValues() used empty($value) to
 * detect "clear this field" - and empty(0.0) is true in PHP, so a
 * genuinely zero price was wiped to NULL along with a blank input. That
 * NULL then hit cart_items.base_price (NOT NULL) and broke add-to-cart
 * with a SQL integrity error for every zero-priced product.
 */
it('persists an explicit zero price instead of nulling it out', function () {
    $repository = app(ProductRepository::class);
    $sku = 'MKTTEST-ZEROPRICE-'.str()->random(8);

    $product = $repository->create([
        'attribute_family_id' => 1,
        'sku' => $sku,
        'type' => 'simple',
    ]);

    $product = $repository->update([
        'name' => 'Zero Price Regression Test',
        'url_key' => str($sku)->slug(),
        'price' => 0.0,
        'status' => 1,
        'visible_individually' => 1,
    ], $product->id);

    $product->refresh();

    expect($product->price)->not->toBeNull();
    expect((float) $product->price)->toBe(0.0);

    // prepareForCart() checks for an existing matching cart item, which
    // needs an active cart in context even for a brand new item.
    Cart::setCart($this->makeTestCart([]));

    $cartRow = $product->getTypeInstance()->prepareForCart(['quantity' => 1, 'product_id' => $product->id])[0];

    expect($cartRow['base_price'])->not->toBeNull();
    expect((float) $cartRow['base_price'])->toBe(0.0);
});
