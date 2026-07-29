<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Webkul\Marketplace\Models\ErpNextProduct;
use Webkul\Product\Models\ProductImage;

/**
 * A database restored without its accompanying storage files (exactly what
 * happened moving vetxpress.shop off its old domain) leaves product_images
 * rows pointing at files that no longer exist on disk - every one renders
 * as a broken image forever, because SyncErpNextProductsCommand only
 * downloads an image when a product has *zero* image rows, so it can't
 * tell a real image apart from an orphaned reference to a missing file.
 */
beforeEach(function () {
    Storage::fake('public');

    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);
});

it('removes an image record whose file is missing from disk', function () {
    $product = \Webkul\Product\Models\Product::factory()->create();

    $orphan = ProductImage::create([
        'type' => 'product',
        'path' => 'product/'.$product->id.'/erpnext-missing.jpg',
        'product_id' => $product->id,
        'position' => 1,
    ]);

    Artisan::call('marketplace:repair-missing-images');

    expect(ProductImage::find($orphan->id))->toBeNull();
});

it('leaves an image record alone when its file genuinely exists on disk', function () {
    $product = \Webkul\Product\Models\Product::factory()->create();

    Storage::disk('public')->put('product/'.$product->id.'/real.jpg', 'bytes');

    $real = ProductImage::create([
        'type' => 'product',
        'path' => 'product/'.$product->id.'/real.jpg',
        'product_id' => $product->id,
        'position' => 1,
    ]);

    Artisan::call('marketplace:repair-missing-images');

    expect(ProductImage::find($real->id))->not->toBeNull();
});

it('does not delete anything in dry-run mode', function () {
    $product = \Webkul\Product\Models\Product::factory()->create();

    $orphan = ProductImage::create([
        'type' => 'product',
        'path' => 'product/'.$product->id.'/erpnext-missing.jpg',
        'product_id' => $product->id,
        'position' => 1,
    ]);

    Artisan::call('marketplace:repair-missing-images', ['--dry-run' => true]);

    expect(ProductImage::find($orphan->id))->not->toBeNull();
});

it('lets a subsequent sync re-download the image once the orphaned record is gone', function () {
    Http::fake([
        '*/api/resource/Item Group*' => Http::response(['data' => []]),
        '*/api/resource/Item%20Group*' => Http::response(['data' => []]),
        '*/api/resource/Item*' => Http::response(['data' => [[
            'item_code' => 'REPAIR-001',
            'item_name' => 'Aquatab Strip',
            'standard_rate' => 500,
            'weight_per_unit' => 0.1,
            'image' => '/files/aquatab.jpg',
        ]]]),
        '*/api/resource/Bin*' => Http::response(['data' => []]),
        'https://erp.test/files/aquatab.jpg' => Http::response('image-bytes', 200),
    ]);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'REPAIR-001')->first()->product;
    $originalImage = $product->images()->first();

    // Simulate the migration losing the file but keeping the DB row.
    Storage::disk('public')->delete($originalImage->path);

    Artisan::call('marketplace:repair-missing-images');
    expect($product->fresh()->images()->count())->toBe(0);

    Artisan::call('erpnext:sync-products');

    $product->refresh();
    expect($product->images()->count())->toBe(1);
    expect(Storage::disk('public')->exists($product->images()->first()->path))->toBeTrue();
    expect((bool) $product->status)->toBeTrue();
});
