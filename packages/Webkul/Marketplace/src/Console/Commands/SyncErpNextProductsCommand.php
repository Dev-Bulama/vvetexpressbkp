<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\Concerns\ClearsResponseCache;
use Webkul\Marketplace\ERPNext\ERPNextClient;
use Webkul\Marketplace\Models\ErpNextCategory;
use Webkul\Marketplace\Models\ErpNextProduct;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Models\ProductImage;
use Webkul\Product\Repositories\ProductRepository;

/**
 * Pulls sellable Items from an external ERPNext instance and mirrors each
 * one into a real Bagisto product, so it shows up on the storefront through
 * the exact same catalog/search/cart/checkout code path as anything an
 * admin adds by hand - no separate display system to maintain.
 *
 * Products are attributed to one dedicated "system" seller (see
 * systemSeller()) so the existing per-vendor checkout/logistics flow keeps
 * working unmodified. Re-running this command updates price, stock, and
 * image for items already synced (matched via marketplace_erpnext_products
 * . item_code) instead of duplicating them.
 */
class SyncErpNextProductsCommand extends Command
{
    use ClearsResponseCache;

    protected $signature = 'erpnext:sync-products';

    protected $description = 'Sync sellable products from the connected ERPNext instance into the catalog';

    /**
     * Items ERPNext returned with no `image` field at all - reported at the
     * end so it's obvious whether missing storefront images are an ERPNext
     * catalog data issue rather than a download/storage problem.
     */
    protected int $itemsWithoutImage = 0;

    public function handle(ERPNextClient $client, ProductRepository $productRepository): int
    {
        if (! $client->isConfigured()) {
            $this->error('ERPNext integration is not configured. Set ERPNEXT_BASE_URL, ERPNEXT_API_KEY, and ERPNEXT_API_SECRET in .env to enable it.');

            return self::FAILURE;
        }

        $channel = Channel::first();

        if (! $channel) {
            $this->error('No channel found - run the essential seeder first.');

            return self::FAILURE;
        }

        // Categories must exist locally before products can be linked to
        // them via their ERPNext item_group - see SyncErpNextCategoriesCommand.
        $this->call('erpnext:sync-categories');

        try {
            $stockLevels = $client->fetchStockLevels();
        } catch (\Throwable $e) {
            $this->warn('Could not fetch stock levels from ERPNext, defaulting synced items to 0 stock: '.$e->getMessage());
            $stockLevels = [];
        }

        $seller = $this->systemSeller();

        $limitStart = 0;
        $limitPageLength = 50;
        $synced = 0;
        $failed = 0;

        do {
            try {
                $items = $client->fetchItems($limitStart, $limitPageLength);
            } catch (\Throwable $e) {
                $this->error('ERPNext request failed: '.$e->getMessage());

                if ($synced > 0) {
                    $this->clearResponseCache();
                }

                return $synced > 0 ? self::SUCCESS : self::FAILURE;
            }

            foreach ($items as $item) {
                try {
                    $this->syncItem($item, $channel, $seller, $stockLevels, $productRepository, $client);
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error('Failed to sync item '.($item['item_code'] ?? '(unknown)').': '.$e->getMessage());
                }
            }

            $limitStart += $limitPageLength;
        } while (count($items) === $limitPageLength);

        $this->info("Synced {$synced} product(s) from ERPNext".($failed ? ", {$failed} failed" : '').'.');

        if ($this->itemsWithoutImage > 0) {
            $this->warn("{$this->itemsWithoutImage} synced item(s) had no image field in ERPNext at all - check the Item's Image field there, this isn't a download failure.");
        }

        if ($synced > 0) {
            // Bagisto's storefront price-range filter and price-based
            // sorting read from a separate denormalized product_price_
            // indices table, not the live product price - it's normally
            // kept in sync by the full admin product-save flow, which
            // ProductRepository::update() called directly (as this sync
            // does) never triggers. Without this, the filter's min/max
            // range stays stuck at whatever old/seed data it last saw,
            // silently excluding every ERPNext-synced product from price
            // filtering and sorting.
            $this->call('indexer:index', ['--type' => ['price', 'inventory'], '--mode' => ['full']]);

            // The homepage and category/product listing routes are behind
            // spatie/laravel-responsecache with no built-in invalidation
            // for catalog changes - without this, a newly synced price,
            // image, or category assignment stays invisible on the live
            // storefront until the cache's own (long) lifetime expires.
            $this->clearResponseCache();
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, float>  $stockLevels
     */
    protected function syncItem(
        array $item,
        Channel $channel,
        Seller $seller,
        array $stockLevels,
        ProductRepository $productRepository,
        ERPNextClient $client
    ): void {
        $itemCode = $item['item_code'] ?? null;

        if (! $itemCode) {
            return;
        }

        $mapping = ErpNextProduct::where('item_code', $itemCode)->first();

        $price = (float) ($item['standard_rate'] ?? 0);
        $quantity = (int) ($stockLevels[$itemCode] ?? 0);
        $name = $item['item_name'] ?? $itemCode;
        $description = trim((string) ($item['description'] ?? ''));

        $baseData = [
            'name' => $name,
            'price' => $price,
        ];

        // Never overwrite an existing description with a blank one just
        // because this particular ERPNext response happened to omit it -
        // only apply it when ERPNext actually provided text.
        if ($description !== '') {
            $baseData['description'] = $description;
            $baseData['short_description'] = $description;
        }

        if ($mapping) {
            $product = $productRepository->findOrFail($mapping->product_id);

            $product = $productRepository->update($baseData + [
                // update() syncs the category pivot to exactly this array -
                // an empty/missing category resolution must never wipe a
                // product's existing category, only a real re-assignment
                // should change it (see resolveCategoryIds()).
                'categories' => $this->resolveCategoryIds($item['item_group'] ?? null, $itemCode, $product),
                // Bagisto's ProductImageRepository::upload() deletes every
                // existing image whenever the 'images' key is missing from
                // update() data (it's built for a full admin-form submit,
                // not a partial patch) - every update() call here must
                // re-supply the product's current images or they vanish.
                'images' => $this->preserveImagesData($product),
            ], $product->id);
        } else {
            $sku = 'ERPNEXT-'.$itemCode;

            $product = $productRepository->create([
                'attribute_family_id' => 1,
                'sku' => $sku,
                'type' => 'simple',
            ]);

            $product = $productRepository->update($baseData + [
                'url_key' => str($sku)->slug(),
                'weight' => (float) ($item['weight_per_unit'] ?? 0),
                'status' => 0,
                'visible_individually' => 0,
                'categories' => $this->resolveCategoryIds($item['item_group'] ?? null, $itemCode, $product),
            ], $product->id);

            $mapping = ErpNextProduct::create([
                'product_id' => $product->id,
                'item_code' => $itemCode,
            ]);
        }

        if (empty($item['image'])) {
            $this->itemsWithoutImage++;
        } elseif (! $product->images()->exists()) {
            $this->attachImage($product, $item['image'], $client);
        }

        // Only complete listings (a real price and at least one photo) are
        // fit for customers to see - an admin can still override either
        // way via Admin\ErpNextProductController, and that decision always
        // wins over this automatic rule on every future sync.
        $isComplete = $price > 0 && $product->images()->exists();

        $visible = match ($mapping->visibility_override) {
            ErpNextProduct::OVERRIDE_HIDDEN => false,
            ErpNextProduct::OVERRIDE_VISIBLE => true,
            default => $isComplete,
        };

        $productRepository->update([
            'status' => $visible ? 1 : 0,
            'visible_individually' => $visible ? 1 : 0,
            // Re-supplied again here too - this update() call happens
            // after attachImage() may have just added a brand new image,
            // which would otherwise be wiped immediately by this very
            // call, and update() wipes the category pivot exactly the
            // same way when 'categories' is missing.
            'images' => $this->preserveImagesData($product),
            'categories' => $product->categories()->pluck('categories.id')->toArray(),
        ], $product->id);

        SellerProduct::updateOrCreate(
            ['seller_id' => $seller->id, 'product_id' => $product->id],
            [
                'price' => $price,
                'quantity' => $quantity,
                'is_active' => $quantity > 0,
            ]
        );

        $mapping->update(['last_synced_at' => now()]);

        app(FlatIndexer::class)->refresh($product->fresh());
    }

    /**
     * Resolves an ERPNext item's `item_group` to the local category synced
     * for it by SyncErpNextCategoriesCommand (matched by external ID, never
     * by name). A missing or not-yet-synced item_group is not treated as
     * "no category" - the product's current category assignment is left
     * untouched rather than silently cleared.
     *
     * @return array<int, int>
     */
    protected function resolveCategoryIds(?string $itemGroup, string $itemCode, $product): array
    {
        if ($itemGroup) {
            $categoryMapping = ErpNextCategory::where('external_id', $itemGroup)->first();

            if ($categoryMapping && $categoryMapping->category_id) {
                return [$categoryMapping->category_id];
            }

            $this->warn("Item {$itemCode}'s item_group '{$itemGroup}' has no matching local category yet - run erpnext:sync-categories, or check it exists in ERPNext. Leaving its current category assignment unchanged.");
        }

        return $product->exists
            ? $product->categories()->pluck('categories.id')->toArray()
            : [];
    }

    /**
     * Builds the 'images' => ['files' => [id => keep, ...]] structure
     * ProductImageRepository::upload() expects to recognize "this existing
     * image should be kept" (any non-UploadedFile value keyed by the
     * image's own ID) - without this, update() treats a missing/empty
     * 'images' key as "the product now has zero images" and deletes every
     * one of them, exactly like the same repository does for 'categories'.
     *
     * @return array<string, mixed>
     */
    protected function preserveImagesData($product): array
    {
        if (! $product->exists) {
            return [];
        }

        $existingImageIds = $product->images()->pluck('id');

        if ($existingImageIds->isEmpty()) {
            return [];
        }

        return [
            'files' => $existingImageIds->mapWithKeys(fn ($id) => [$id => true])->all(),
        ];
    }

    protected function attachImage($product, string $imagePath, ERPNextClient $client): void
    {
        $contents = $client->downloadImage($imagePath);

        if (! $contents) {
            return;
        }

        $filename = 'product/'.$product->id.'/erpnext-'.basename(parse_url($imagePath, PHP_URL_PATH) ?? 'image.jpg');

        Storage::disk('public')->put($filename, $contents);

        ProductImage::create([
            'type' => 'product',
            'path' => $filename,
            'product_id' => $product->id,
            'position' => 1,
        ]);
    }

    /**
     * One dedicated seller account owns every ERPNext-synced product, kept
     * separate from real vendor accounts. Its name is deliberately generic
     * ("External Catalog") since it isn't a real vendor an admin is meant
     * to manage by hand - only rename it if the business wants a different
     * storefront-facing label for these items.
     */
    protected function systemSeller(): Seller
    {
        // VendorCartEligibilityService requires every seller to have a
        // pickup location - without one, this seller (and every ERPNext
        // product it owns) would never be eligible for any order. Default
        // to the business's own registered address (see the storefront
        // footer) since there's no per-item pickup point for these items;
        // an admin can correct this to the exact warehouse coordinates via
        // seller management if it differs.
        $seller = Seller::firstOrCreate(
            ['email' => 'erpnext-sync@vetexpress.system'],
            [
                'name' => Seller::SYSTEM_SELLER_SHOP_NAME,
                'shop_name' => Seller::SYSTEM_SELLER_SHOP_NAME,
                'password' => bcrypt(str()->random(40)),
                'status' => Seller::STATUS_APPROVED,
                'rating' => 5.0,
                'latitude' => 6.6059,
                'longitude' => 3.3491,
            ]
        );

        if ($seller->latitude === null || $seller->longitude === null) {
            $seller->update(['latitude' => 6.6059, 'longitude' => 3.3491]);
        }

        return $seller;
    }
}
