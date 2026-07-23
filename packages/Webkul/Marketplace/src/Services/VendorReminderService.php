<?php

namespace Webkul\Marketplace\Services;

use Illuminate\Support\Facades\Mail;
use Webkul\Marketplace\Mail\VendorCatalogueReminderMail;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\VendorReminder;

/**
 * Sends a vendor a catalogue-completeness reminder email and records it,
 * enforcing a cooldown so the same unresolved gap doesn't generate a new
 * email every time an admin clicks the button or the scheduled command
 * runs. All thresholds are configurable (config('services.marketplace_reminders'),
 * env-driven) rather than hardcoded, per the admin settings this feature
 * is meant to expose.
 */
class VendorReminderService
{
    public function __construct(protected VendorCatalogueCoverageService $coverageService) {}

    public function isOnCooldown(Seller $seller): bool
    {
        $cooldownDays = (int) config('services.marketplace_reminders.cooldown_days', 7);

        return VendorReminder::where('seller_id', $seller->id)
            ->where('created_at', '>=', now()->subDays($cooldownDays))
            ->exists();
    }

    public function exceedsWeeklyLimit(Seller $seller): bool
    {
        $maxPerWeek = (int) config('services.marketplace_reminders.max_per_week', 1);

        return VendorReminder::where('seller_id', $seller->id)
            ->where('created_at', '>=', now()->subWeek())
            ->count() >= $maxPerWeek;
    }

    /**
     * Sends the reminder unless it's on cooldown. $force bypasses both the
     * cooldown and weekly limit - reserved for an admin's explicit "Send
     * Urgent Reminder" action, never used by the automated schedule.
     */
    public function sendReminder(Seller $seller, ?int $sentByAdminId = null, string $type = 'automated', bool $force = false): ?VendorReminder
    {
        if (! $force && ($this->isOnCooldown($seller) || $this->exceedsWeeklyLimit($seller))) {
            return null;
        }

        $coverage = $this->coverageService->forSeller($seller);
        $missingProducts = $this->coverageService->missingProducts($seller);
        $outOfStockProducts = $this->coverageService->outOfStockProducts($seller);
        $lowStockProducts = $this->coverageService->lowStockProducts($seller);

        Mail::send(new VendorCatalogueReminderMail(
            $seller,
            $coverage,
            $missingProducts,
            $outOfStockProducts,
            $lowStockProducts,
            isUrgent: $type === 'urgent',
        ));

        return VendorReminder::create([
            'seller_id' => $seller->id,
            'sent_by_admin_id' => $sentByAdminId,
            'type' => $type,
            'channel' => 'email',
            'coverage_percent_at_send' => $coverage->coverage_percent,
            'missing_products_count' => $coverage->missing_products,
            'product_ids' => $missingProducts->pluck('product_id')->all(),
            'delivery_status' => 'sent',
        ]);
    }

    /**
     * Whether a seller currently meets the configured automated-reminder
     * trigger conditions (low coverage AND enough missing products to be
     * worth flagging) - used by the scheduled command.
     */
    public function shouldAutoRemind(Seller $seller): bool
    {
        if (! config('services.marketplace_reminders.enabled', true)) {
            return false;
        }

        $coverage = $this->coverageService->forSeller($seller);

        return $coverage->coverage_percent < (float) config('services.marketplace_reminders.coverage_threshold', 50)
            && $coverage->missing_products >= (int) config('services.marketplace_reminders.min_missing_products', 3);
    }
}
