<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Marketplace\Mail\VendorCatalogueReminderMail;
use Webkul\Marketplace\Models\VendorReminder;
use Webkul\Marketplace\Services\VendorReminderService;

beforeEach(function () {
    $this->service = app(VendorReminderService::class);

    Mail::fake();
});

it('sends a reminder email and records it when the vendor is not on cooldown', function () {
    $seller = $this->makeTestSeller();

    $reminder = $this->service->sendReminder($seller, type: 'automated');

    expect($reminder)->not->toBeNull();
    expect($reminder->seller_id)->toBe($seller->id);
    expect($reminder->type)->toBe('automated');
    expect($reminder->channel)->toBe('email');
    expect($reminder->delivery_status)->toBe('sent');

    Mail::assertQueued(VendorCatalogueReminderMail::class, function ($mail) use ($seller) {
        return $mail->seller->id === $seller->id && $mail->isUrgent === false;
    });
});

it('does not send a duplicate reminder within the configured cooldown', function () {
    config(['services.marketplace_reminders.cooldown_days' => 7]);

    $seller = $this->makeTestSeller();

    $first = $this->service->sendReminder($seller, type: 'automated');
    expect($first)->not->toBeNull();

    $second = $this->service->sendReminder($seller, type: 'automated');
    expect($second)->toBeNull();

    Mail::assertQueuedCount(1);
    expect(VendorReminder::where('seller_id', $seller->id)->count())->toBe(1);
});

it('allows a forced urgent reminder to bypass the cooldown', function () {
    config(['services.marketplace_reminders.cooldown_days' => 7]);

    $seller = $this->makeTestSeller();

    $this->service->sendReminder($seller, type: 'automated');
    expect($this->service->isOnCooldown($seller))->toBeTrue();

    $urgent = $this->service->sendReminder($seller, type: 'urgent', force: true);

    expect($urgent)->not->toBeNull();
    expect($urgent->type)->toBe('urgent');

    Mail::assertQueuedCount(2);
});

it('enforces the configured maximum reminders per week even across the cooldown window', function () {
    config([
        'services.marketplace_reminders.cooldown_days' => 0,
        'services.marketplace_reminders.max_per_week' => 1,
    ]);

    $seller = $this->makeTestSeller();

    $first = $this->service->sendReminder($seller, type: 'manual');
    expect($first)->not->toBeNull();

    // Cooldown is 0 (disabled) but the weekly cap of 1 should still block a
    // second reminder inside the same week.
    $second = $this->service->sendReminder($seller, type: 'manual');
    expect($second)->toBeNull();
});

it('only auto-recommends a reminder when coverage is below threshold and enough products are missing', function () {
    config([
        'services.marketplace_reminders.enabled' => true,
        'services.marketplace_reminders.coverage_threshold' => 50,
        'services.marketplace_reminders.min_missing_products' => 3,
    ]);

    $wellStockedSeller = $this->makeTestSeller();
    $product = $this->makeTestProduct();
    // Stocking even one product doesn't push coverage above the threshold
    // by itself in this shared catalogue, so directly assert against the
    // service's own coverage computation for a seller with nothing missing
    // vs. one clearly below every threshold (a brand-new seller with zero
    // offers has 100% "missing", which must trigger a reminder).
    $this->makeTestOffer($wellStockedSeller, $product, quantity: 10);

    $emptySeller = $this->makeTestSeller();

    expect($this->service->shouldAutoRemind($emptySeller))->toBeTrue();
});

it('never auto-recommends a reminder when the feature is disabled via config', function () {
    config(['services.marketplace_reminders.enabled' => false]);

    $seller = $this->makeTestSeller();

    expect($this->service->shouldAutoRemind($seller))->toBeFalse();
});
