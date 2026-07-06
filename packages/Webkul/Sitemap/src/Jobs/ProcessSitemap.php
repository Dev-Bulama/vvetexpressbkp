<?php

namespace Webkul\Sitemap\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;
use Webkul\Sitemap\Contracts\Sitemap as SitemapContract;
use Webkul\Sitemap\Models\Category;
use Webkul\Sitemap\Models\Page;
use Webkul\Sitemap\Models\Product;

class ProcessSitemap implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Batch processed.
     */
    protected int $batchProcessed = 0;

    /**
     * Items to be processed.
     */
    protected array $itemsToBeProcessed = [];

    /**
     * Generated sitemaps.
     */
    protected array $generatedSitemaps = [];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public SitemapContract $sitemap
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /**
         * If sitemap is disabled then return.
         */
        if (! core()->getConfigData('general.sitemap.settings.enabled')) {
            return;
        }

        /**
         * If the sitemap is already generated then delete the existing sitemap.
         */
        $this->sitemap->deleteFromStorage();

        /**
         * Channels the sitemap is scoped to.
         */
        $channelIds = $this->sitemap->channels->pluck('id')->all();

        if (empty($channelIds)) {
            return;
        }

        /**
         * Process the store URL.
         */
        $this->processItems([Url::create('/')]);

        /**
         * Process the categories scoped to the sitemap's channels.
         */
        $this->processCategories($channelIds);

        /**
         * Process the products scoped to the sitemap's channels.
         */
        Product::query()
            ->whereHas('channels', fn ($query) => $query->whereIn('channel_id', $channelIds))
            ->chunk(100, fn ($items) => $this->processItems($items));

        /**
         * Process the CMS pages scoped to the sitemap's channels.
         */
        Page::query()
            ->whereHas('channels', fn ($query) => $query->whereIn('channel_id', $channelIds))
            ->chunk(100, fn ($items) => $this->processItems($items));

        /**
         * If there are any items left to be processed then generate the sitemap.
         */
        if (! empty($this->itemsToBeProcessed)) {
            $this->generateSitemap();
        }

        /**
         * After generating all the sitemaps, we will generate the index.
         */
        $this->generateSitemapIndex();

        /**
         * Update the sitemap with the generated sitemap index and sitemaps.
         */
        $this->sitemap->update([
            'generated_at' => now(),
            
            'additional' => array_merge($this->sitemap->additional ?? [], [
                'index' => $this->sitemap->index_file_name,
                'sitemaps' => $this->generatedSitemaps,
            ]),
        ]);
    }

    /**
     * Process categories under the given channels' root category subtrees.
     */
    protected function processCategories(array $channelIds): void
    {
        $rootCategoryIds = core()->getAllChannels()
            ->whereIn('id', $channelIds)
            ->pluck('root_category_id')
            ->all();

        $roots = Category::query()
            ->whereIn('id', $rootCategoryIds)
            ->get(['_lft', '_rgt']);

        if ($roots->isEmpty()) {
            return;
        }

        Category::query()
            ->where(function ($query) use ($roots) {
                foreach ($roots as $root) {
                    $query->orWhereBetween('_lft', [$root->_lft + 1, $root->_rgt - 1]);
                }
            })
            ->chunk(100, fn ($items) => $this->processItems($items));
    }

    /**
     * Process items.
     *
     * @param  mixed  $items
     */
    protected function processItems($items): void
    {
        foreach ($items as $item) {
            $this->itemsToBeProcessed[] = $item;

            if (count($this->itemsToBeProcessed) === (int) core()->getConfigData('general.sitemap.file_limits.max_url_per_file')) {
                $this->generateSitemap();
            }
        }
    }

    /**
     * Generate sitemap.
     */
    protected function generateSitemap(): void
    {
        $this->batchProcessed++;

        $sitemap = Sitemap::create();

        foreach ($this->itemsToBeProcessed as $item) {
            $sitemap->add($item);
        }

        $sitemapFilePath = clean_path($this->sitemap->path.'/'.File::name($this->sitemap->file_name).'-'.$this->sitemap->id.'-'.$this->batchProcessed.'.'.File::extension($this->sitemap->file_name));

        $sitemap->writeToDisk('public', $sitemapFilePath);

        $this->generatedSitemaps[] = $sitemapFilePath;

        $this->itemsToBeProcessed = [];
    }

    /**
     * Generate sitemap index.
     */
    protected function generateSitemapIndex(): void
    {
        $sitemap = SitemapIndex::create();

        foreach ($this->generatedSitemaps as $generatedSitemap) {
            $sitemap->add(Storage::disk('public')->url($generatedSitemap));
        }

        $sitemap->writeToDisk('public', $this->sitemap->index_file_name);
    }
}
