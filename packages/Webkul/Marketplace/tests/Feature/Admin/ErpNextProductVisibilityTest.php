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

it('searches the ERPNext products list by product name', function () {
    $matching = $this->makeTestProduct();
    app(\Webkul\Product\Repositories\ProductRepository::class)->update(['name' => 'Aquatab Strip 20 Tablets'], $matching->id);
    app(\Webkul\Product\Helpers\Indexers\Flat::class)->refresh($matching->fresh());
    ErpNextProduct::create(['product_id' => $matching->id, 'item_code' => 'SEARCH-001', 'last_synced_at' => now()]);

    $other = $this->makeTestProduct();
    app(\Webkul\Product\Repositories\ProductRepository::class)->update(['name' => 'Dog Chew Bone'], $other->id);
    app(\Webkul\Product\Helpers\Indexers\Flat::class)->refresh($other->fresh());
    ErpNextProduct::create(['product_id' => $other->id, 'item_code' => 'SEARCH-002', 'last_synced_at' => now()]);

    $response = get(route('marketplace.admin.erpnext-products.index', ['search' => 'Aquatab']));

    $response->assertOk();
    $response->assertSee($matching->sku);
    $response->assertDontSee($other->sku);
});

it('searches the ERPNext products list by SKU', function () {
    $product = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $product->id, 'item_code' => 'SEARCH-003', 'last_synced_at' => now()]);

    $other = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $other->id, 'item_code' => 'SEARCH-004', 'last_synced_at' => now()]);

    $response = get(route('marketplace.admin.erpnext-products.index', ['search' => $product->sku]));

    $response->assertOk();
    $response->assertSee($product->sku);
    $response->assertDontSee($other->sku);
});

it('searches the ERPNext products list by item code', function () {
    $product = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $product->id, 'item_code' => 'UNIQUE-ITEM-CODE-999', 'last_synced_at' => now()]);

    $other = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $other->id, 'item_code' => 'SEARCH-005', 'last_synced_at' => now()]);

    $response = get(route('marketplace.admin.erpnext-products.index', ['search' => 'UNIQUE-ITEM-CODE-999']));

    $response->assertOk();
    $response->assertSee($product->sku);
    $response->assertDontSee($other->sku);
});

it('reports no matches for a search term nothing matches, without erroring', function () {
    $product = $this->makeTestProduct();
    ErpNextProduct::create(['product_id' => $product->id, 'item_code' => 'SEARCH-006', 'last_synced_at' => now()]);

    $response = get(route('marketplace.admin.erpnext-products.index', ['search' => 'totally-nonexistent-term-xyz']));

    $response->assertOk();
    $response->assertSee('No ERPNext-synced products match your search');
});

it('bulk-hides every checked ERPNext product in one action', function () {
    $productA = $this->makeTestProduct();
    $mappingA = ErpNextProduct::create(['product_id' => $productA->id, 'item_code' => 'BULK-001', 'last_synced_at' => now()]);

    $productB = $this->makeTestProduct();
    $mappingB = ErpNextProduct::create(['product_id' => $productB->id, 'item_code' => 'BULK-002', 'last_synced_at' => now()]);

    post(route('marketplace.admin.erpnext-products.bulk-visibility'), [
        'action' => 'hide',
        'ids' => [$mappingA->id, $mappingB->id],
    ])->assertRedirect(route('marketplace.admin.erpnext-products.index'));

    $mappingA->refresh();
    $mappingB->refresh();
    $productA->refresh();
    $productB->refresh();

    expect($mappingA->isHidden())->toBeTrue();
    expect($mappingB->isHidden())->toBeTrue();
    expect((bool) $productA->status)->toBeFalse();
    expect((bool) $productB->status)->toBeFalse();
});

it('bulk-makes every checked ERPNext product public in one action', function () {
    $productA = $this->makeTestProduct();
    $mappingA = ErpNextProduct::create([
        'product_id' => $productA->id,
        'item_code' => 'BULK-003',
        'visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN,
        'last_synced_at' => now(),
    ]);

    post(route('marketplace.admin.erpnext-products.bulk-visibility'), [
        'action' => 'show',
        'ids' => [$mappingA->id],
    ])->assertRedirect(route('marketplace.admin.erpnext-products.index'));

    $mappingA->refresh();
    $productA->refresh();

    expect($mappingA->isHidden())->toBeFalse();
    expect((bool) $productA->status)->toBeTrue();
});

it('leaves unchecked ERPNext products untouched by a bulk action', function () {
    $checked = $this->makeTestProduct();
    $checkedMapping = ErpNextProduct::create(['product_id' => $checked->id, 'item_code' => 'BULK-004', 'last_synced_at' => now()]);

    $unchecked = $this->makeTestProduct();
    $uncheckedMapping = ErpNextProduct::create(['product_id' => $unchecked->id, 'item_code' => 'BULK-005', 'last_synced_at' => now()]);

    post(route('marketplace.admin.erpnext-products.bulk-visibility'), [
        'action' => 'hide',
        'ids' => [$checkedMapping->id],
    ]);

    $uncheckedMapping->refresh();
    $unchecked->refresh();

    expect($uncheckedMapping->isHidden())->toBeFalse();
    expect((bool) $unchecked->status)->toBeTrue();
});

it('refuses to make a zero-price ERPNext product public', function () {
    $product = $this->makeTestProduct(0.0);

    $mapping = ErpNextProduct::create([
        'product_id' => $product->id,
        'item_code' => 'ZEROPRICE-001',
        'visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN,
        'last_synced_at' => now(),
    ]);
    app(\Webkul\Product\Repositories\ProductRepository::class)->update(['status' => 0, 'visible_individually' => 0], $product->id);

    post(route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id))
        ->assertRedirect(route('marketplace.admin.erpnext-products.index'));

    $mapping->refresh();
    $product->refresh();

    expect($mapping->isHidden())->toBeTrue();
    expect((bool) $product->status)->toBeFalse();
    expect(session('error'))->toContain('no real price');
});

it('skips zero-price products in a bulk "make public" action but still applies to the rest', function () {
    $zeroPriced = $this->makeTestProduct(0.0);
    $zeroMapping = ErpNextProduct::create(['product_id' => $zeroPriced->id, 'item_code' => 'BULKZERO-001', 'last_synced_at' => now()]);
    app(\Webkul\Product\Repositories\ProductRepository::class)->update(['status' => 0, 'visible_individually' => 0], $zeroPriced->id);

    $realPriced = $this->makeTestProduct(500.0);
    $realMapping = ErpNextProduct::create([
        'product_id' => $realPriced->id,
        'item_code' => 'BULKREAL-001',
        'visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN,
        'last_synced_at' => now(),
    ]);
    app(\Webkul\Product\Repositories\ProductRepository::class)->update(['status' => 0, 'visible_individually' => 0], $realPriced->id);

    post(route('marketplace.admin.erpnext-products.bulk-visibility'), [
        'action' => 'show',
        'ids' => [$zeroMapping->id, $realMapping->id],
    ])->assertRedirect(route('marketplace.admin.erpnext-products.index'));

    $zeroMapping->refresh();
    $realMapping->refresh();
    $zeroPriced->refresh();
    $realPriced->refresh();

    // The refusal leaves the zero-priced item's admin-override field
    // untouched (still whatever it was before this bulk action) - what
    // actually matters is that its product stayed hidden.
    expect((bool) $zeroPriced->status)->toBeFalse();
    expect($realMapping->isHidden())->toBeFalse();
    expect((bool) $realPriced->status)->toBeTrue();
    expect(session('success'))->toContain($zeroPriced->sku);
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
