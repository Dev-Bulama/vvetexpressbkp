<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Webkul\Marketplace\Models\ErpNextProduct;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Only a "complete" listing (a real price and at least one photo) should
 * show to customers automatically - the surgical/medical consumable items
 * this vendor's ERPNext sends with no price and no photo were showing on
 * the storefront as blank placeholder cards with ₦0.00, which is what
 * these tests guard against. An admin's explicit override (either
 * direction) still always wins over this automatic rule.
 */
beforeEach(function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);
});

function fakeErpNextItem(array $item): void
{
    Http::fake([
        '*/api/resource/Item Group*' => Http::response(['data' => []]),
        '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
        '*/api/resource/Item*' => Http::response(['data' => [$item]]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
    ]);
}

it('auto-hides a newly synced item that has no real price', function () {
    fakeErpNextItem([
        'item_code' => 'NOPRICE-001',
        'item_name' => 'Vicryl 3/0',
        'standard_rate' => 0,
        'weight_per_unit' => 0.1,
    ]);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'NOPRICE-001')->first()->product;

    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});

it('auto-hides a newly synced item that has a price but no image', function () {
    fakeErpNextItem([
        'item_code' => 'NOIMAGE-001',
        'item_name' => 'Nylon 2',
        'standard_rate' => 500,
        'weight_per_unit' => 0.1,
    ]);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'NOIMAGE-001')->first()->product;

    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});

it('auto-shows a newly synced item that has both a real price and an image', function () {
    Http::fake([
        '*/api/resource/Item Group*' => Http::response(['data' => []]),
        '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
        '*/api/resource/Item*' => Http::response(['data' => [[
            'item_code' => 'COMPLETE-001',
            'item_name' => 'Sigma Dog Food 15kg',
            'standard_rate' => 15000,
            'weight_per_unit' => 15,
            'image' => '/files/dog-food.jpg',
        ]]]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
        'https://erp.test/files/dog-food.jpg' => Http::response('image-bytes', 200),
    ]);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'COMPLETE-001')->first()->product;

    expect((bool) $product->status)->toBeTrue();
    expect((bool) $product->visible_individually)->toBeTrue();
    expect($product->images()->exists())->toBeTrue();
});

it('keeps a product image intact across repeated re-syncs, instead of wiping it every run', function () {
    // Bagisto's ProductImageRepository::upload() deletes every existing
    // image whenever an update() call omits the 'images' key - every
    // update() call in the sync command must re-supply it or a
    // previously-attached image disappears the moment ANY other field
    // (price, category, visibility) gets synced again.
    Http::fake([
        '*/api/resource/Item Group*' => Http::response(['data' => []]),
        '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
        '*/api/resource/Item*' => Http::response(['data' => [[
            'item_code' => 'PERSIST-001',
            'item_name' => 'Sigma Cat Food 15kg',
            'standard_rate' => 12000,
            'weight_per_unit' => 15,
            'image' => '/files/cat-food.jpg',
        ]]]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
        'https://erp.test/files/cat-food.jpg' => Http::response('image-bytes', 200),
    ]);

    Artisan::call('erpnext:sync-products');

    $mapping = ErpNextProduct::where('item_code', 'PERSIST-001')->first();
    $imageIdAfterFirstSync = $mapping->product->images()->first()->id;

    // Re-sync several more times with the same data - a fresh HTTP fake
    // each time with NO stub for the image URL, so if the sync ever tried
    // to re-download it, the request would fail loudly rather than
    // silently succeeding again.
    for ($i = 0; $i < 3; $i++) {
        Http::fake([
            '*/api/resource/Item Group*' => Http::response(['data' => []]),
            '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
            '*/api/resource/Item*' => Http::response(['data' => [[
                'item_code' => 'PERSIST-001',
                'item_name' => 'Sigma Cat Food 15kg',
                'standard_rate' => 12000,
                'weight_per_unit' => 15,
                'image' => '/files/cat-food.jpg',
            ]]]),
            '*/api/resource/Bin*' => Http::response(['data' => []]),
        ]);

        Artisan::call('erpnext:sync-products');
    }

    $product = $mapping->product()->first();

    expect($product->images()->count())->toBe(1);
    expect($product->images()->first()->id)->toBe($imageIdAfterFirstSync);
    expect((bool) $product->status)->toBeTrue();
});

it('lets an admin force an incomplete item public, and that survives a future sync', function () {
    fakeErpNextItem([
        'item_code' => 'FORCED-001',
        'item_name' => 'Cotton Wool',
        'standard_rate' => 0,
        'weight_per_unit' => 0.1,
    ]);

    Artisan::call('erpnext:sync-products');

    $mapping = ErpNextProduct::where('item_code', 'FORCED-001')->first();
    $mapping->update(['visibility_override' => ErpNextProduct::OVERRIDE_VISIBLE]);
    app(ProductRepository::class)->update(['status' => 1, 'visible_individually' => 1], $mapping->product_id);

    // Re-sync with the exact same (still incomplete) data - the override
    // must win, not the automatic rule.
    Artisan::call('erpnext:sync-products');

    $product = $mapping->product()->first();

    expect((bool) $product->status)->toBeTrue();
    expect((bool) $product->visible_individually)->toBeTrue();
});

it('lets an admin hide a complete item, and that survives a future sync', function () {
    Http::fake([
        '*/api/resource/Item Group*' => Http::response(['data' => []]),
        '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
        '*/api/resource/Item*' => Http::response(['data' => [[
            'item_code' => 'HIDE-001',
            'item_name' => 'Confidential Item',
            'standard_rate' => 5000,
            'weight_per_unit' => 1,
            'image' => '/files/confidential.jpg',
        ]]]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
        'https://erp.test/files/confidential.jpg' => Http::response('image-bytes', 200),
    ]);

    Artisan::call('erpnext:sync-products');

    $mapping = ErpNextProduct::where('item_code', 'HIDE-001')->first();
    $mapping->update(['visibility_override' => ErpNextProduct::OVERRIDE_HIDDEN]);
    app(ProductRepository::class)->update(['status' => 0, 'visible_individually' => 0], $mapping->product_id);

    Artisan::call('erpnext:sync-products');

    $product = $mapping->product()->first();

    expect((bool) $product->status)->toBeFalse();
    expect((bool) $product->visible_individually)->toBeFalse();
});

it('syncs the ERPNext description into the product without blanking it on a later sync that omits it', function () {
    fakeErpNextItem([
        'item_code' => 'DESC-001',
        'item_name' => 'Described Item',
        'standard_rate' => 100,
        'weight_per_unit' => 0.1,
        'description' => 'A genuinely useful description from ERPNext.',
    ]);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'DESC-001')->first()->product->fresh();

    expect($product->description)->toBe('A genuinely useful description from ERPNext.');

    // Re-sync without a description field this time - must not wipe it.
    fakeErpNextItem([
        'item_code' => 'DESC-001',
        'item_name' => 'Described Item',
        'standard_rate' => 100,
        'weight_per_unit' => 0.1,
    ]);

    Artisan::call('erpnext:sync-products');

    $product = $product->fresh();

    expect($product->description)->toBe('A genuinely useful description from ERPNext.');
});
