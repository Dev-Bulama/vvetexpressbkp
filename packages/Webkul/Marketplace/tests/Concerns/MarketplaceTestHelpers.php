<?php

namespace Webkul\Marketplace\Tests\Concerns;

use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

trait MarketplaceTestHelpers
{
    protected function makeTestProduct(float $price = 100.0): Product
    {
        $repository = app(ProductRepository::class);
        $sku = 'MKTTEST-'.str()->random(10);

        $product = $repository->create([
            'attribute_family_id' => 1,
            'sku' => $sku,
            'type' => 'simple',
        ]);

        $repository->update([
            'name' => 'Marketplace Test Product '.$sku,
            'url_key' => str($sku)->slug(),
            'price' => $price,
            'status' => 1,
            'visible_individually' => 1,
        ], $product->id);

        // Repository create()/update() calls don't dispatch
        // catalog.product.{create,update}.after (only the admin HTTP
        // controller does), so product_flat is never populated this way.
        // Refresh it explicitly so tests see the same product_flat state
        // a real admin-created product would have.
        app(FlatIndexer::class)->refresh($product->fresh());

        return $product->fresh();
    }

    protected function makeTestSeller(array $attributes = []): Seller
    {
        return Seller::create(array_merge([
            'name' => 'Test Seller',
            'shop_name' => 'Test Shop '.str()->random(8),
            'email' => 'seller-'.str()->random(10).'@test.local',
            'password' => bcrypt('password'),
            'status' => Seller::STATUS_APPROVED,
            'rating' => 4.5,
            'service_radius_km' => 15,
            'is_delivery_enabled' => true,
        ], $attributes));
    }

    protected function makeTestOffer(Seller $seller, Product $product, int $quantity, ?float $price = null): SellerProduct
    {
        return SellerProduct::create([
            'seller_id' => $seller->id,
            'product_id' => $product->id,
            'price' => $price ?? $product->price,
            'quantity' => $quantity,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<int, array{0: Product, 1: int}>  $items  [product, quantity] pairs
     */
    protected function makeTestCart(array $items): Cart
    {
        $channel = Channel::first();

        $cart = Cart::create([
            'channel_id' => $channel->id,
            'is_active' => true,
        ]);

        foreach ($items as [$product, $quantity]) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'sku' => $product->sku,
                'type' => 'simple',
                'name' => $product->name,
                'quantity' => $quantity,
                'price' => $product->price,
                'base_price' => $product->price,
                'total' => $product->price * $quantity,
                'base_total' => $product->price * $quantity,
            ]);
        }

        return Cart::find($cart->id);
    }
}
