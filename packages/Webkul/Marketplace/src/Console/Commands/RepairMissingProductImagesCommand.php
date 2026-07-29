<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Webkul\Product\Models\ProductImage;

/**
 * A database restored from a backup/export that didn't carry the actual
 * uploaded files with it (e.g. moving to a new domain's document root)
 * ends up with product_images rows pointing at files that don't exist on
 * this server's disk - every one of those renders as a broken image on
 * the storefront forever, because nothing ever notices the file is gone.
 *
 * SyncErpNextProductsCommand only downloads an image when a product has
 * *zero* image rows (`! $product->images()->exists()`) - it has no way to
 * tell "has a row" apart from "has a row AND a real file", so it silently
 * skips every product already in this broken state. Deleting the orphaned
 * rows here is what lets that check correctly see them as imageless again,
 * so the next `erpnext:sync-products` run re-downloads them from ERPNext.
 * Admin-uploaded images with no ERPNext source can't be recovered this way
 * - removing their orphaned row just stops them rendering as broken.
 */
class RepairMissingProductImagesCommand extends Command
{
    protected $signature = 'marketplace:repair-missing-images {--dry-run : List what would be removed without deleting anything}';

    protected $description = 'Remove product image records whose file is missing from disk, so re-syncing can re-download them';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $missing = ProductImage::all()->filter(
            fn (ProductImage $image) => ! Storage::disk('public')->exists($image->path)
        );

        if ($missing->isEmpty()) {
            $this->info('No orphaned product images found - every image record has a matching file on disk.');

            return self::SUCCESS;
        }

        $affectedProductIds = $missing->pluck('product_id')->unique()->sort()->values();

        foreach ($missing as $image) {
            $this->line(($dryRun ? '[dry-run] would remove' : 'Removing').": product #{$image->product_id} - {$image->path}");
        }

        if (! $dryRun) {
            ProductImage::whereIn('id', $missing->pluck('id'))->delete();
        }

        $this->info(($dryRun ? 'Would remove' : 'Removed')." {$missing->count()} orphaned image record(s) across {$affectedProductIds->count()} product(s).");

        if (! $dryRun) {
            $this->info('Run `php artisan erpnext:sync-products` now to re-download images for ERPNext-sourced products. Admin-uploaded images with no ERPNext source will need to be re-uploaded manually.');
        }

        return self::SUCCESS;
    }
}
