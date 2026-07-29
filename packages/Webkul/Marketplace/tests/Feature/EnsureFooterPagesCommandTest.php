<?php

use Illuminate\Support\Facades\Artisan;
use Webkul\CMS\Models\Page;

/**
 * The storefront footer links to CMS pages by url_key (about-us,
 * terms-conditions, etc.) - a database that was migrated in rather than
 * created via `bagisto:install` can be missing all of them, making every
 * footer link 404. This command backfills only what's missing.
 */
beforeEach(function () {
    // Simulates a database that was migrated in rather than created via
    // `bagisto:install`, so none of the footer-linked default CMS pages exist.
    foreach (['about-us', 'terms-conditions', 'privacy-policy', 'customer-service', 'return-policy', 'shipping-policy'] as $urlKey) {
        Page::whereTranslation('url_key', $urlKey)->get()->each(fn ($page) => $page->delete());
    }
});

it('creates every missing footer-linked CMS page', function () {
    expect(Page::whereTranslation('url_key', 'about-us')->exists())->toBeFalse();

    Artisan::call('marketplace:ensure-footer-pages');

    foreach (['about-us', 'terms-conditions', 'privacy-policy', 'customer-service', 'return-policy', 'shipping-policy'] as $urlKey) {
        expect(Page::whereTranslation('url_key', $urlKey)->exists())->toBeTrue();
    }
});

it('attaches created pages to every channel so they resolve on the storefront', function () {
    Artisan::call('marketplace:ensure-footer-pages');

    $page = Page::whereTranslation('url_key', 'about-us')->first();

    expect($page->channels()->count())->toBe(\Webkul\Core\Models\Channel::count());
});

it('does not touch or duplicate a page that already exists', function () {
    Artisan::call('marketplace:ensure-footer-pages');

    $page = Page::whereTranslation('url_key', 'about-us')->first();

    $page->translations()->first()->update(['html_content' => '<p>Admin-edited content</p>']);

    Artisan::call('marketplace:ensure-footer-pages');

    expect(Page::whereTranslation('url_key', 'about-us')->count())->toBe(1);
    expect($page->fresh()->translate()->html_content)->toBe('<p>Admin-edited content</p>');
});

it('lets the about-us page actually render instead of 404ing', function () {
    Artisan::call('marketplace:ensure-footer-pages');

    $this->get(route('shop.cms.page', ['slug' => 'about-us']))->assertOk();
});

it('lets the terms-conditions page actually render instead of 404ing', function () {
    Artisan::call('marketplace:ensure-footer-pages');

    $this->get(route('shop.cms.page', ['slug' => 'terms-conditions']))->assertOk();
});

it('lets the privacy-policy page actually render instead of 404ing', function () {
    Artisan::call('marketplace:ensure-footer-pages');

    $this->get(route('shop.cms.page', ['slug' => 'privacy-policy']))->assertOk();
});
