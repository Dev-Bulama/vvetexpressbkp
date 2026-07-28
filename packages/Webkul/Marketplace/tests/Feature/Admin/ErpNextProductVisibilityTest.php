<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Webkul\Marketplace\Models\ErpNextProduct;
use Webkul\Product\Models\ProductImage;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->admin = $this->loginAsAdmin();
});

it('lists every ERPNext-synced product for admin review', function () {
    $product = $this->makeTestProduct();

    $mapping = ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-001',
        'last_synced_at' => now(),
    ]);

    $response = get(route('marketplace.admin.erpnext-products.index'));

    $response->assertOk();
    $response->assertSee($product->sku);
    $response->assertSee('ITEM-001');
    $response->assertSee('Public');
});

it('shows the product image on the ERPNext products list when one was synced', function () {
    $product = $this->makeTestProduct();

    ProductImage::create([
        'product_id' => $product->id,
        'path' => 'product/'.$product->id.'/erpnext-photo.jpg',
        'position' => 1,
    ]);

    ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-005',
        'last_synced_at' => now(),
    ]);

    $response = get(route('marketplace.admin.erpnext-products.index'));

    $response->assertOk();
    $response->assertSee('erpnext-photo.jpg');
});

it('shows a "no image" placeholder when ERPNext never returned an image for the item', function () {
    $product = $this->makeTestProduct();

    ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-006',
        'last_synced_at' => now(),
    ]);

    $response = get(route('marketplace.admin.erpnext-products.index'));

    $response->assertOk();
    $response->assertSee('No image');
});

it('hides an ERPNext product from the public storefront when toggled', function () {
    $product = $this->makeTestProduct();

    $mapping = ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-002',
        'last_synced_at' => now(),
    ]);

    post(route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id))
        ->assertRedirect(route('marketplace.admin.erpnext-products.index'));

    $mapping->refresh();
    $product->refresh();

    expect($mapping->is_hidden_from_public)->toBeTrue();
    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});

it('makes a hidden ERPNext product public again when toggled back', function () {
    $product = $this->makeTestProduct();

    $mapping = ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-003',
        'is_hidden_from_public' => true,
        'last_synced_at' => now(),
    ]);

    post(route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id));

    $mapping->refresh();
    $product->refresh();

    expect($mapping->is_hidden_from_public)->toBeFalse();
    expect((bool) $product->status)->toBeTrue();
    expect((bool) $product->visible_individually)->toBeTrue();
});

it('does not silently re-publish a product an admin hid the next time ERPNext is re-synced', function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);

    $product = $this->makeTestProduct();

    ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-004',
        'is_hidden_from_public' => true,
        'last_synced_at' => now()->subDay(),
    ]);

    // Admin-hidden - even though this ran during the earlier toggle test's
    // controller call, do it directly here too so this test is self
    // sufficient regardless of execution order.
    $product->getTypeInstance()->update(['status' => 0, 'visible_individually' => 0], $product->id);

    Http::fake([
        '*/api/resource/Item*' => Http::response([
            'data' => [
                [
                    'item_code' => 'ITEM-004',
                    'item_name' => 'Re-synced Item',
                    'standard_rate' => 150,
                    'weight_per_unit' => 1,
                ],
            ],
        ]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
    ]);

    Artisan::call('erpnext:sync-products');

    $product->refresh();

    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});
