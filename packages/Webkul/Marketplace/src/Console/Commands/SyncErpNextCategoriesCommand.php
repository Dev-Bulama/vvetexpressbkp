<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Models\Channel;
use Webkul\Marketplace\Concerns\ClearsResponseCache;
use Webkul\Marketplace\ERPNext\ERPNextClient;
use Webkul\Marketplace\Models\ErpNextCategory;

/**
 * Mirrors ERPNext's Item Group tree into Bagisto's own nested-set Category
 * model, so the categories shown across the storefront/admin/filters are
 * the exact same categories the connected ERPNext instance defines - no
 * separate, drifting category structure to maintain by hand.
 *
 * Run before SyncErpNextProductsCommand (which calls this automatically)
 * so every product's `item_group` already has a matching local category to
 * attach to by the time products sync.
 */
class SyncErpNextCategoriesCommand extends Command
{
    use ClearsResponseCache;

    protected $signature = 'erpnext:sync-categories';

    protected $description = 'Sync the category tree from the connected ERPNext instance (Item Groups) into the catalog';

    /**
     * ERPNext ships every site with this exact Item Group as the ultimate
     * tree root - it's a Frappe fixture, not something a real business
     * treats as a browsable category, so it's never turned into one here.
     * Its direct children become the store's top-level categories instead.
     */
    protected const ERP_ROOT_NAME = 'All Item Groups';

    public function handle(ERPNextClient $client, CategoryRepository $categoryRepository): int
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

        $itemGroups = [];
        $limitStart = 0;
        $limitPageLength = 100;

        do {
            try {
                $page = $client->fetchItemGroups($limitStart, $limitPageLength);
            } catch (\Throwable $e) {
                $this->error('ERPNext request failed: '.$e->getMessage());

                return self::FAILURE;
            }

            foreach ($page as $itemGroup) {
                if (! empty($itemGroup['name'])) {
                    $itemGroups[$itemGroup['name']] = $itemGroup;
                }
            }

            $limitStart += $limitPageLength;
        } while (count($page) === $limitPageLength);

        // Item Groups arrive as a flat list with no guaranteed parent-before
        // -child order. Resolve them in dependency order instead of relying
        // on API ordering, so a deeply nested tree still syncs correctly in
        // one pass regardless of how ERPNext returned it.
        $resolvedLocalParentId = [self::ERP_ROOT_NAME => $channel->root_category_id];
        $synced = 0;
        $failed = 0;
        $seenExternalIds = [];

        // ERPNext's own tree root is a Frappe fixture, not a real category -
        // recognize and record it without creating a category for it, so
        // its direct children become the store's top-level categories.
        if (isset($itemGroups[self::ERP_ROOT_NAME])) {
            unset($itemGroups[self::ERP_ROOT_NAME]);

            ErpNextCategory::updateOrCreate(
                ['external_id' => self::ERP_ROOT_NAME],
                [
                    'category_id' => null,
                    'external_parent_id' => null,
                    'source' => 'erpnext',
                    'sync_status' => ErpNextCategory::STATUS_SYNCED,
                    'last_synced_at' => now(),
                ]
            );

            $seenExternalIds[] = self::ERP_ROOT_NAME;
        }

        $remaining = $itemGroups;

        while ($remaining) {
            $progressed = false;

            foreach ($remaining as $externalId => $itemGroup) {
                $parentExternalId = ($itemGroup['parent_item_group'] ?? null) ?: self::ERP_ROOT_NAME;

                if (! array_key_exists($parentExternalId, $resolvedLocalParentId)) {
                    // Parent not synced yet this run - try again once it is.
                    continue;
                }

                try {
                    $category = $this->syncItemGroup($itemGroup, $resolvedLocalParentId[$parentExternalId], $categoryRepository);
                    $resolvedLocalParentId[$externalId] = $category->id;
                    $seenExternalIds[] = $externalId;
                    $synced++;
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error('Failed to sync item group '.$externalId.': '.$e->getMessage());

                    ErpNextCategory::updateOrCreate(
                        ['external_id' => $externalId],
                        ['sync_status' => ErpNextCategory::STATUS_FAILED, 'last_synced_at' => now()]
                    );
                }

                unset($remaining[$externalId]);
                $progressed = true;
            }

            if (! $progressed) {
                // Whatever is left references a parent that never resolved
                // (broken/circular parent_item_group data in ERPNext) -
                // report it and stop rather than looping forever.
                foreach (array_keys($remaining) as $externalId) {
                    $failed++;
                    $this->error("Could not resolve parent category for item group {$externalId} - its parent_item_group was never found.");
                }

                break;
            }
        }

        // A previously-synced category ERPNext no longer returned isn't
        // deleted or unpublished automatically - just flagged, so an admin
        // can decide whether to archive it (see the ERPNext Categories
        // admin page), matching the same never-silently-hide convention
        // used for ERPNext products.
        ErpNextCategory::where('source', 'erpnext')
            ->whereNotIn('external_id', $seenExternalIds)
            ->where('sync_status', '!=', ErpNextCategory::STATUS_MISSING)
            ->update(['sync_status' => ErpNextCategory::STATUS_MISSING]);

        $this->info("Synced {$synced} categor".($synced === 1 ? 'y' : 'ies').' from ERPNext'.($failed ? ", {$failed} failed" : '').'.');

        // The homepage and category listing routes are behind
        // spatie/laravel-responsecache with no built-in invalidation for
        // catalog changes - without this, a new or renamed category stays
        // invisible on the live storefront until the cache's own (long)
        // lifetime expires.
        if ($synced > 0) {
            $this->clearResponseCache();
        }

        return self::SUCCESS;
    }

    protected function syncItemGroup(array $itemGroup, int $localParentId, CategoryRepository $categoryRepository): Category
    {
        $externalId = $itemGroup['name'];
        $name = $itemGroup['item_group_name'] ?? $externalId;
        $parentExternalId = ($itemGroup['parent_item_group'] ?? null) ?: null;
        $payloadHash = md5(json_encode([$name, $parentExternalId]));

        $mapping = ErpNextCategory::where('external_id', $externalId)->first();

        if ($mapping && $mapping->category_id && $mapping->external_payload_hash === $payloadHash) {
            $mapping->update(['sync_status' => ErpNextCategory::STATUS_SYNCED, 'last_synced_at' => now()]);

            return $mapping->category->fresh();
        }

        if ($mapping && $mapping->category_id) {
            $category = Category::findOrFail($mapping->category_id);

            $locale = core()->getAllLocales()->first();

            $categoryRepository->update([
                'status' => $mapping->is_disabled_locally ? 0 : 1,
                'display_mode' => $category->display_mode ?? 'products_and_description',
                'parent_id' => $localParentId,
                $locale->code => [
                    'name' => $name,
                    'slug' => $category->slug ?: $this->uniqueSlug($name, $externalId),
                    'locale_id' => $locale->id,
                ],
            ], $category->id);

            $mapping->update([
                'external_parent_id' => $parentExternalId,
                'external_payload_hash' => $payloadHash,
                'sync_status' => ErpNextCategory::STATUS_SYNCED,
                'last_synced_at' => now(),
            ]);

            return $category->fresh();
        }

        $locale = core()->getAllLocales()->first();
        $slug = $this->uniqueSlug($name, $externalId);

        $category = $categoryRepository->create([
            'status' => 1,
            'display_mode' => 'products_and_description',
            'parent_id' => $localParentId,
            $locale->code => [
                'name' => $name,
                'slug' => $slug,
                'locale_id' => $locale->id,
            ],
        ]);

        ErpNextCategory::updateOrCreate(
            ['external_id' => $externalId],
            [
                'category_id' => $category->id,
                'external_parent_id' => $parentExternalId,
                'source' => 'erpnext',
                'sync_status' => ErpNextCategory::STATUS_SYNCED,
                'external_payload_hash' => $payloadHash,
                'last_synced_at' => now(),
            ]
        );

        return $category;
    }

    protected function uniqueSlug(string $name, string $externalId): string
    {
        $base = str($name)->slug()->value() ?: str($externalId)->slug()->value();

        $slug = $base;
        $suffix = 1;

        while (Category::whereTranslation('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
