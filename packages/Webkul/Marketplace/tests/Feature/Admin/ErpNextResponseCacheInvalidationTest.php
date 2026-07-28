<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Spatie\ResponseCache\ResponseCache;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\Models\ErpNextCategory;
use Webkul\Marketplace\Models\ErpNextProduct;

use function Pest\Laravel\post;

/**
 * The homepage and category/product listing routes carry the
 * `cache.response` middleware (spatie/laravel-responsecache), but only
 * Channel/CoreConfig/Theme/CmsPage saves were ever wired to invalidate it
 * (see MarketplaceServiceProvider::clearResponseCacheOnStorefrontChanges()).
 * A category or product synced/changed via ERPNext never cleared it, so a
 * new category, a newly attached image, or a visibility toggle stayed
 * invisible on the live storefront until the cache's own (long) lifetime
 * expired. These tests prove every ERPNext-driven catalog change now
 * clears it.
 */
beforeEach(function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
        'responsecache.enabled' => true,
    ]);
});

function fakeErpNextForCache(array &$itemGroups, array &$items, array &$bins): void
{
    Http::fake(function ($request) use (&$itemGroups, &$items, &$bins) {
        $url = $request->url();

        if (str_contains($url, 'resource/Item%20Group') || str_contains($url, 'resource/Item Group')) {
            return Http::response(['data' => $itemGroups]);
        }

        if (str_contains($url, 'resource/Item')) {
            return Http::response(['data' => $items]);
        }

        if (str_contains($url, 'resource/Bin')) {
            return Http::response(['data' => $bins]);
        }

        return Http::response([], 404);
    });
}

it('clears the storefront response cache after erpnext:sync-categories syncs at least one category', function () {
    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    $itemGroups = [
        ['name' => 'Cache Test Category', 'item_group_name' => 'Cache Test Category', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNextForCache($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
});

it('clears the storefront response cache after erpnext:sync-products syncs at least one product', function () {
    // sync-categories runs first internally (no categories here) and
    // clears nothing since it syncs zero - only the product sync clears.
    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    $itemGroups = [];
    $items = [
        ['item_code' => 'CACHE-001', 'item_name' => 'Cache Test Product', 'standard_rate' => 100, 'weight_per_unit' => 0.1],
    ];
    $bins = [];
    fakeErpNextForCache($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-products');
});

it('clears the storefront response cache when an admin toggles an ERPNext product\'s visibility', function () {
    $this->loginAsAdmin();

    $itemGroups = [];
    $items = [
        ['item_code' => 'CACHE-002', 'item_name' => 'Visibility Toggle Product', 'standard_rate' => 100, 'weight_per_unit' => 0.1],
    ];
    $bins = [];
    fakeErpNextForCache($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-products');

    $mapping = ErpNextProduct::where('item_code', 'CACHE-002')->first();

    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    post(route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id))
        ->assertRedirect(route('marketplace.admin.erpnext-products.index'));
});

it('clears the storefront response cache when an admin toggles an ERPNext category locally', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Toggle Test Category', 'item_group_name' => 'Toggle Test Category', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNextForCache($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');

    $mapping = ErpNextCategory::where('external_id', 'Toggle Test Category')->first();

    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    post(route('marketplace.admin.erpnext-categories.toggle-local', $mapping->id))
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));
});

it('clears the storefront response cache when an admin disables non-ERPNext categories in bulk', function () {
    $this->loginAsAdmin();

    $channel = Channel::first();

    app(CategoryRepository::class)->create([
        'status' => 1,
        'display_mode' => 'products_and_description',
        'parent_id' => $channel->root_category_id,
        'en' => [
            'name' => 'Manual Category For Cache Test',
            'slug' => 'manual-category-cache-test-'.uniqid(),
            'locale_id' => core()->getAllLocales()->first()->id,
        ],
    ]);

    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    post(route('marketplace.admin.erpnext-categories.disable-non-api'))
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));
});

it('does not attempt to clear the response cache when it is disabled in config', function () {
    config(['responsecache.enabled' => false]);

    $this->mock(ResponseCache::class)->shouldNotReceive('clear');

    $itemGroups = [
        ['name' => 'No Cache Config Category', 'item_group_name' => 'No Cache Config Category', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNextForCache($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
});
