<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\ERPNext\ERPNextClient;
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
    protected $signature = 'erpnext:sync-products';

    protected $description = 'Sync sellable products from the connected ERPNext instance into the catalog';

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

        if ($mapping) {
            $product = $productRepository->findOrFail($mapping->product_id);

            $product = $productRepository->update([
                'name' => $name,
                'price' => $price,
                'status' => 1,
                'visible_individually' => 1,
            ], $product->id);
        } else {
            $sku = 'ERPNEXT-'.$itemCode;

            $product = $productRepository->create([
                'attribute_family_id' => 1,
                'sku' => $sku,
                'type' => 'simple',
            ]);

            $product = $productRepository->update([
                'name' => $name,
                'url_key' => str($sku)->slug(),
                'price' => $price,
                'weight' => (float) ($item['weight_per_unit'] ?? 0),
                'status' => 1,
                'visible_individually' => 1,
            ], $product->id);

            $mapping = ErpNextProduct::create([
                'product_id' => $product->id,
                'item_code' => $itemCode,
            ]);
        }

        if (! empty($item['image']) && ! $product->images()->exists()) {
            $this->attachImage($product, $item['image'], $client);
        }

        SellerProduct::updateOrCreate(
            ['seller_id' => $seller->id, 'product_id' => $product->id],
            [
                'price' => $price,
                'quantity' => $quantity,
                'is_active' => $quantity > 0,
            ]
        );

        $mapping->update(['last_synced_at' => now()]);

        app(FlatIndexer::class)->refresh($product);
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
        return Seller::firstOrCreate(
            ['email' => 'erpnext-sync@vetexpress.system'],
            [
                'name' => Seller::SYSTEM_SELLER_SHOP_NAME,
                'shop_name' => Seller::SYSTEM_SELLER_SHOP_NAME,
                'password' => bcrypt(str()->random(40)),
                'status' => Seller::STATUS_APPROVED,
                'rating' => 5.0,
            ]
        );
    }
}
