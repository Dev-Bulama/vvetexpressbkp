<?php

namespace Webkul\Marketplace\Concerns;

use Spatie\ResponseCache\ResponseCache;

/**
 * The storefront's homepage and category/product listing routes carry the
 * `cache.response` middleware (spatie/laravel-responsecache), which has no
 * built-in invalidation for catalog changes - only Channel/CoreConfig/Theme/
 * CmsPage saves clear it (see MarketplaceServiceProvider). Without this, a
 * category re-synced from ERPNext, a newly-attached product image, or an
 * admin visibility toggle stays invisible on the live storefront until the
 * cache's own week-long lifetime expires. Anything that changes what a
 * customer sees in the catalog needs to clear it explicitly.
 */
trait ClearsResponseCache
{
    protected function clearResponseCache(): void
    {
        if (! config('responsecache.enabled')) {
            return;
        }

        app(ResponseCache::class)->clear();
    }
}
