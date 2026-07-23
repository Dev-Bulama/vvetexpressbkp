<?php

namespace Webkul\Marketplace\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Services\VendorReminderService;

/**
 * Automated catalogue-completeness reminders - only sends to vendors that
 * meet the configured coverage-threshold + missing-products trigger (see
 * config('services.marketplace_reminders')), and never bypasses the
 * cooldown/weekly-limit VendorReminderService enforces for every send.
 */
class SendVendorCatalogueRemindersCommand extends Command
{
    protected $signature = 'marketplace:send-vendor-reminders';

    protected $description = 'Send catalogue-completeness reminder emails to vendors below the configured coverage threshold';

    public function handle(VendorReminderService $reminderService): int
    {
        if (! config('services.marketplace_reminders.enabled', true)) {
            $this->info('Vendor reminders are disabled (services.marketplace_reminders.enabled).');

            return self::SUCCESS;
        }

        $sent = 0;
        $skipped = 0;

        Seller::where('status', Seller::STATUS_APPROVED)->each(function (Seller $seller) use ($reminderService, &$sent, &$skipped) {
            if (! $reminderService->shouldAutoRemind($seller)) {
                return;
            }

            $reminder = $reminderService->sendReminder($seller, type: 'automated');

            $reminder ? $sent++ : $skipped++;
        });

        $this->info("Sent {$sent} vendor catalogue reminder(s), skipped {$skipped} (cooldown or weekly limit).");

        return self::SUCCESS;
    }
}
