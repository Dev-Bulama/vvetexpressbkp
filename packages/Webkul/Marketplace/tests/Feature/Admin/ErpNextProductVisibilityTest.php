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

it('flags a synced product with no ERPNext category as "Uncategorized"', function () {
    $product = $this->makeTestProduct();

    ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-007',
        'last_synced_at' => now(),
    ]);

    $response = get(route('marketplace.admin.erpnext-products.index'));

    $response->assertOk();
    // The shared dev DB may already have other uncategorized ERPNext
    // products from earlier runs, so assert the message text rather than
    // a brittle exact count.
    $response->assertSee('Uncategorized');
    $response->assertSee('no ERPNext category assigned yet');
});

it('shows the assigned category name for a product procurement has already categorized', function () {
    $product = $this->makeTestProduct();
    $category = $this->makeTestCategory('Dog/Puppy Food');
    $product->categories()->sync([$category->id]);

    ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-008',
        'last_synced_at' => now(),
    ]);

    $response = get(route('marketplace.admin.erpnext-products.index'));

    $response->assertOk();
    $response->assertSee('Dog/Puppy Food');
    $response->assertDontSee('Uncategorized');
});

it('filters the ERPNext products list down to only uncategorized products', function () {
    $categorized = $this->makeTestProduct();
    $category = $this->makeTestCategory('Vitamins & Supplements');
    $categorized->categories()->sync([$category->id]);

    ErpNextProduct::create(['product_id' => $categorized->id, 'item_code' => 'ITEM-009', 'last_synced_at' => now()]);

    $uncategorized = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $uncategorized->id, 'item_code' => 'ITEM-010', 'last_synced_at' => now()]);

    $response = get(route('marketplace.admin.erpnext-products.index', ['uncategorized' => 1]));

    $response->assertOk();
    $response->assertSee($uncategorized->sku);
    $response->assertDontSee($categorized->sku);
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

    expect($mapping->isHidden())->toBeTrue();
    expect($mapping->visibility_override)->toBe(ErpNextProduct::OVERRIDE_HIDDEN);
    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});

it('makes a hidden ERPNext product public again when toggled back, overriding the automatic completeness rule', function () {
    $product = $this->makeTestProduct();

    $mapping = ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ITEM-003',
        'visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN,
        'last_synced_at' => now(),
    ]);

    post(route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id));

    $mapping->refresh();
    $product->refresh();

    expect($mapping->isHidden())->toBeFalse();
    expect($mapping->visibility_override)->toBe(ErpNextProduct::OVERRIDE_VISIBLE);
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
        'visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN,
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
