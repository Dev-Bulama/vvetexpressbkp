<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Core\Models\Channel;

/**
 * The storefront footer links About Us, Contact Us, Terms & Conditions,
 * Privacy Policy, Help Centre, Returns and Refunds, and Delivery Information
 * to Bagisto CMS pages by url_key - the same admin-editable pages a fresh
 * Bagisto install seeds automatically. A site whose database was migrated
 * in (rather than created via `bagisto:install`) can end up without them,
 * which makes every one of those footer links 404. This command creates
 * only the ones that are missing, with placeholder content an admin can
 * immediately edit from Admin > Content > Pages - it never touches a page
 * that already exists, so it's safe to run as many times as needed.
 */
class EnsureFooterPagesCommand extends Command
{
    protected $signature = 'marketplace:ensure-footer-pages';

    protected $description = 'Create any footer-linked CMS pages (About Us, Terms & Conditions, etc.) that are missing, so footer links stop 404ing';

    public function handle(PageRepository $pageRepository): int
    {
        $channelIds = Channel::pluck('id')->all();

        if (empty($channelIds)) {
            $this->error('No channels found - cannot attach CMS pages to a channel.');

            return self::FAILURE;
        }

        $created = 0;

        foreach ($this->pages() as $page) {
            if ($pageRepository->findByUrlKey($page['url_key'])) {
                $this->line("Skipping \"{$page['url_key']}\" - already exists.");

                continue;
            }

            $pageRepository->create([
                'url_key' => $page['url_key'],
                'page_title' => $page['page_title'],
                'html_content' => $page['html_content'],
                'meta_title' => $page['page_title'],
                'meta_description' => '',
                'meta_keywords' => '',
                'channels' => $channelIds,
            ]);

            $this->info("Created \"{$page['url_key']}\".");

            $created++;
        }

        $this->info($created > 0
            ? "Created {$created} CMS page(s). Edit their content from Admin > Content > Pages."
            : 'All footer-linked CMS pages already exist - nothing to do.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{url_key: string, page_title: string, html_content: string}>
     */
    protected function pages(): array
    {
        $placeholder = fn (string $title) => '<div class="static-container"><div class="mb-5"><p>'
            .'This is a placeholder for the "'.$title.'" page. Edit this content from Admin > Content > Pages.'
            .'</p></div></div>';

        return [
            ['url_key' => 'about-us', 'page_title' => 'About Us', 'html_content' => $placeholder('About Us')],
            ['url_key' => 'terms-conditions', 'page_title' => 'Terms & Conditions', 'html_content' => $placeholder('Terms & Conditions')],
            ['url_key' => 'privacy-policy', 'page_title' => 'Privacy Policy', 'html_content' => $placeholder('Privacy Policy')],
            ['url_key' => 'customer-service', 'page_title' => 'Help Centre', 'html_content' => $placeholder('Help Centre')],
            ['url_key' => 'return-policy', 'page_title' => 'Returns and Refunds', 'html_content' => $placeholder('Returns and Refunds')],
            ['url_key' => 'shipping-policy', 'page_title' => 'Delivery Information', 'html_content' => $placeholder('Delivery Information')],
        ];
    }
}
