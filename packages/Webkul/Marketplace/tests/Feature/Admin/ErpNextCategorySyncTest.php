<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Webkul\Category\Models\Category;
use Webkul\Marketplace\Models\ErpNextCategory;
use Webkul\Marketplace\Models\ErpNextProduct;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);
});

/**
 * Builds an Http::fake() closure that checks "resource/Item Group" (the
 * parent-category endpoint) before the plain "resource/Item" endpoint - a
 * wildcard string pattern like '*resource/Item*' would match both URLs
 * since "Item Group" contains "Item", silently feeding the wrong payload
 * to whichever request happened to run first.
 *
 * Reads from the given arrays *by reference*, so a test that needs a
 * second sync run with different data can mutate those same arrays
 * in place rather than calling Http::fake() again - Http::fake()
 * accumulates stub closures instead of replacing them, so a second
 * always-matching closure would never actually take over from the first.
 */
function fakeErpNext(array &$itemGroups, array &$items, array &$bins): void
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

it('syncs parent and child item groups into the local category tree, preserving the hierarchy', function () {
    $itemGroups = [
        ['name' => 'Veterinary Supplies', 'item_group_name' => 'Veterinary Supplies', 'parent_item_group' => 'All Item Groups', 'is_group' => 1],
        ['name' => 'Vaccines', 'item_group_name' => 'Vaccines', 'parent_item_group' => 'Veterinary Supplies', 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');

    $parentMapping = ErpNextCategory::where('external_id', 'Veterinary Supplies')->first();
    $childMapping = ErpNextCategory::where('external_id', 'Vaccines')->first();

    expect($parentMapping)->not->toBeNull();
    expect($childMapping)->not->toBeNull();
    expect($childMapping->category->parent_id)->toBe($parentMapping->category_id);
});

it('does not create a category for the ERPNext tree root itself', function () {
    $itemGroups = [
        ['name' => 'All Item Groups', 'item_group_name' => 'All Item Groups', 'parent_item_group' => null, 'is_group' => 1],
        ['name' => 'Pet Food', 'item_group_name' => 'Pet Food', 'parent_item_group' => 'All Item Groups', 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');

    $rootMapping = ErpNextCategory::where('external_id', 'All Item Groups')->first();
    $petFoodMapping = ErpNextCategory::where('external_id', 'Pet Food')->first();

    expect($rootMapping->category_id)->toBeNull();
    expect($petFoodMapping->category_id)->not->toBeNull();
});

it('does not create a duplicate category when the same item group is synced twice', function () {
    $itemGroups = [
        ['name' => 'Dewormers', 'item_group_name' => 'Dewormers', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
    $firstCategoryId = ErpNextCategory::where('external_id', 'Dewormers')->first()->category_id;

    Artisan::call('erpnext:sync-categories');
    $secondCategoryId = ErpNextCategory::where('external_id', 'Dewormers')->first()->category_id;

    expect($secondCategoryId)->toBe($firstCategoryId);
    expect(ErpNextCategory::where('external_id', 'Dewormers')->count())->toBe(1);
});

it('updates the existing category name when the item group is renamed, without creating a duplicate', function () {
    $itemGroups = [
        ['name' => 'Antibiotics', 'item_group_name' => 'Antibiotics', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
    $originalCategoryId = ErpNextCategory::where('external_id', 'Antibiotics')->first()->category_id;

    // Same external ID (ERPNext's stable primary key), renamed display name.
    $itemGroups[0]['item_group_name'] = 'Antibiotics & Antifungals';

    Artisan::call('erpnext:sync-categories');

    $mapping = ErpNextCategory::where('external_id', 'Antibiotics')->first();

    expect(ErpNextCategory::where('external_id', 'Antibiotics')->count())->toBe(1);
    expect($mapping->category_id)->toBe($originalCategoryId);
    expect($mapping->category->fresh()->name)->toBe('Antibiotics & Antifungals');
});

it('marks a previously synced category as missing when ERPNext stops returning it, without disabling or deleting it', function () {
    $itemGroups = [
        ['name' => 'Grooming', 'item_group_name' => 'Grooming', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
    $categoryId = ErpNextCategory::where('external_id', 'Grooming')->first()->category_id;

    $itemGroups = [];

    Artisan::call('erpnext:sync-categories');

    $mapping = ErpNextCategory::where('external_id', 'Grooming')->first();

    expect($mapping->sync_status)->toBe('missing');
    expect(Category::find($categoryId)->status)->toBe(1);
});

it('associates a synced product with its local category via the ERPNext item_group', function () {
    $itemGroups = [
        ['name' => 'Pet Toys', 'item_group_name' => 'Pet Toys', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [
        ['item_code' => 'TOY-001', 'item_name' => 'Chew Rope', 'standard_rate' => 500, 'item_group' => 'Pet Toys', 'weight_per_unit' => 0.2],
    ];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-products');

    $categoryId = ErpNextCategory::where('external_id', 'Pet Toys')->first()->category_id;
    $product = ErpNextProduct::where('item_code', 'TOY-001')->first()->product;

    expect($product->categories()->pluck('categories.id')->toArray())->toBe([$categoryId]);
});

it('leaves a product category assignment unchanged when its item_group has no matching synced category', function () {
    $itemGroups = [];
    $items = [
        ['item_code' => 'TOY-002', 'item_name' => 'Squeaky Ball', 'standard_rate' => 300, 'item_group' => 'Unsynced Group', 'weight_per_unit' => 0.1],
    ];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-products');

    $product = ErpNextProduct::where('item_code', 'TOY-002')->first()->product;

    // No matching category was ever synced for "Unsynced Group" - the
    // product must still sync successfully with no category, not crash.
    expect($product)->not->toBeNull();
    expect($product->categories()->count())->toBe(0);
});

it('lists synced ERPNext categories on the admin page', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Supplements', 'item_group_name' => 'Supplements', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');

    $response = get(route('marketplace.admin.erpnext-categories.index'));

    $response->assertOk();
    $response->assertSee('Supplements');
    $response->assertSee('Synced');
});

it('lets an admin manually trigger a category re-sync from the admin page', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'First Aid', 'item_group_name' => 'First Aid', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    post(route('marketplace.admin.erpnext-categories.sync'))
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));

    expect(ErpNextCategory::where('external_id', 'First Aid')->exists())->toBeTrue();
});

it('lets an admin disable a synced category locally and keeps it disabled through the next sync', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Prescription Only', 'item_group_name' => 'Prescription Only', 'parent_item_group' => null, 'is_group' => 0],
    ];
    $items = [];
    $bins = [];
    fakeErpNext($itemGroups, $items, $bins);

    Artisan::call('erpnext:sync-categories');
    $mapping = ErpNextCategory::where('external_id', 'Prescription Only')->first();

    post(route('marketplace.admin.erpnext-categories.toggle-local', $mapping->id))
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));

    $mapping->refresh();
    expect($mapping->is_disabled_locally)->toBeTrue();
    expect($mapping->category->fresh()->status)->toBe(0);

    // Re-running the sync must not silently re-enable it.
    Artisan::call('erpnext:sync-categories');
    expect($mapping->category->fresh()->status)->toBe(0);
});

it('requires admin authentication to view synced ERPNext categories', function () {
    $response = get(route('marketplace.admin.erpnext-categories.index'));

    $response->assertRedirect();
    $response->assertDontSee('ERPNext Categories');
});
