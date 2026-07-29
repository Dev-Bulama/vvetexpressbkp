<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\ResponseCache\ResponseCache;
use Webkul\Category\Models\Category;
use Webkul\Marketplace\Models\ErpNextCategory;

use function Pest\Laravel\post;

/**
 * Procurement maintains the approved-category decision in a spreadsheet
 * (matching ERPNext's own Item Group report export: an "Item Group Name"
 * column and an Enable/Disable column) rather than clicking through dozens
 * of categories by hand every time the list changes.
 */
beforeEach(function () {
    config([
        'services.erpnext.base_url' => 'https://erp.test',
        'services.erpnext.api_key' => 'key',
        'services.erpnext.api_secret' => 'secret',
    ]);
});

function fakeErpNextForImport(array &$itemGroups): void
{
    $items = [];
    $bins = [];

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

/**
 * @param  array<int, array{0: int, 1: string, 2: string}>  $rows  [id, item group name, Enable/Disable]
 */
function makeVisibilitySpreadsheet(array $rows, string $format = 'xlsx'): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([[null, 'Item Group Name', 'Title'], [null, 'All Item Groups', null]], $rows));

    $extension = $format === 'csv' ? 'csv' : 'xlsx';
    $path = tempnam(sys_get_temp_dir(), 'visibility').'.'.$extension;

    $writer = $format === 'csv' ? new Csv($spreadsheet) : new Xlsx($spreadsheet);
    $writer->save($path);

    return new UploadedFile(
        $path,
        'categories.'.$extension,
        $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true
    );
}

it('applies category visibility from an uploaded xlsx, matched by ERPNext external ID not display name', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'DOG/PUPPY FOOD', 'item_group_name' => 'DOG/PUPPY FOOD', 'parent_item_group' => null, 'is_group' => 0],
        ['name' => 'ASSETS', 'item_group_name' => 'ASSETS', 'parent_item_group' => null, 'is_group' => 0],
    ];
    fakeErpNextForImport($itemGroups);

    Artisan::call('erpnext:sync-categories');

    $dogCategoryId = ErpNextCategory::where('external_id', 'DOG/PUPPY FOOD')->first()->category_id;
    $assetsCategoryId = ErpNextCategory::where('external_id', 'ASSETS')->first()->category_id;

    // Start both enabled, so the "disable ASSETS" direction is a genuine
    // change the import must apply, not a no-op.
    Category::whereIn('id', [$dogCategoryId, $assetsCategoryId])->update(['status' => 1]);

    $file = makeVisibilitySpreadsheet([
        [1, 'DOG/PUPPY FOOD', 'Enabled'],
        [2, 'ASSETS', 'Disable'],
    ]);

    post(route('marketplace.admin.erpnext-categories.import-visibility'), ['file' => $file])
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));

    expect(Category::find($dogCategoryId)->status)->toBe(1);
    expect(Category::find($assetsCategoryId)->status)->toBe(0);
    expect(ErpNextCategory::where('external_id', 'ASSETS')->first()->is_disabled_locally)->toBeTrue();
    expect(ErpNextCategory::where('external_id', 'DOG/PUPPY FOOD')->first()->is_disabled_locally)->toBeFalse();
});

it('applies category visibility from an uploaded csv the same way', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Pet Toys', 'item_group_name' => 'Pet Toys', 'parent_item_group' => null, 'is_group' => 0],
    ];
    fakeErpNextForImport($itemGroups);

    Artisan::call('erpnext:sync-categories');

    $categoryId = ErpNextCategory::where('external_id', 'Pet Toys')->first()->category_id;
    Category::where('id', $categoryId)->update(['status' => 0]);

    $file = makeVisibilitySpreadsheet([[1, 'Pet Toys', 'Enabled']], format: 'csv');

    post(route('marketplace.admin.erpnext-categories.import-visibility'), ['file' => $file])
        ->assertRedirect(route('marketplace.admin.erpnext-categories.index'));

    expect(Category::find($categoryId)->status)->toBe(1);
});

it('reports spreadsheet rows that do not match any synced category, without failing the whole import', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Accessories', 'item_group_name' => 'Accessories', 'parent_item_group' => null, 'is_group' => 0],
    ];
    fakeErpNextForImport($itemGroups);

    Artisan::call('erpnext:sync-categories');

    $categoryId = ErpNextCategory::where('external_id', 'Accessories')->first()->category_id;

    $file = makeVisibilitySpreadsheet([
        [1, 'Accessories', 'Enabled'],
        [2, 'Some Typo Category', 'Disable'],
    ]);

    $response = post(route('marketplace.admin.erpnext-categories.import-visibility'), ['file' => $file]);

    $response->assertRedirect(route('marketplace.admin.erpnext-categories.index'));
    $response->assertSessionHas('success');
    expect(session('success'))->toContain('Some Typo Category');
    expect(Category::find($categoryId)->status)->toBe(1);
});

it('clears the response cache after applying an import that actually changed something', function () {
    $this->loginAsAdmin();

    $itemGroups = [
        ['name' => 'Beverages', 'item_group_name' => 'Beverages', 'parent_item_group' => null, 'is_group' => 0],
    ];
    fakeErpNextForImport($itemGroups);

    Artisan::call('erpnext:sync-categories');

    $file = makeVisibilitySpreadsheet([[1, 'Beverages', 'Disable']]);

    $this->mock(ResponseCache::class)->shouldReceive('clear')->once();

    post(route('marketplace.admin.erpnext-categories.import-visibility'), ['file' => $file]);
});
