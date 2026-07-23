<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Marketplace\Mail\VendorCatalogueReminderMail;
use Webkul\Marketplace\Models\VendorReminder;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Mail::fake();
    $this->admin = $this->loginAsAdmin();
});

it('lists vendor catalogue coverage with summary stats on the admin dashboard', function () {
    $seller = $this->makeTestSeller(['shop_name' => 'Coverage Dashboard Test Shop']);
    $product = $this->makeTestProduct();
    $this->makeTestOffer($seller, $product, quantity: 10);

    $response = get(route('marketplace.admin.catalogue-coverage.index'));

    $response->assertOk();
    $response->assertSee('Coverage Dashboard Test Shop');
});

it('shows a single vendor catalogue detail page with missing products and reminder history', function () {
    $seller = $this->makeTestSeller(['shop_name' => 'Vendor Detail Test Shop']);

    $reminder = VendorReminder::create([
        'seller_id' => $seller->id,
        'type' => 'manual',
        'channel' => 'email',
        'coverage_percent_at_send' => 10.0,
        'missing_products_count' => 5,
        'product_ids' => [],
        'delivery_status' => 'sent',
    ]);

    $response = get(route('marketplace.admin.catalogue-coverage.show', $seller->id));

    $response->assertOk();
    $response->assertSee('Vendor Detail Test Shop');
    $response->assertSee('manual');
});

it('lets an admin send a manual reminder to a vendor from the detail page', function () {
    $seller = $this->makeTestSeller();

    $response = post(route('marketplace.admin.catalogue-coverage.remind', $seller->id));

    $response->assertRedirect(route('marketplace.admin.catalogue-coverage.show', $seller->id));

    $this->assertDatabaseHas('marketplace_vendor_reminders', [
        'seller_id' => $seller->id,
        'type' => 'manual',
        'sent_by_admin_id' => $this->admin->id,
    ]);

    Mail::assertQueued(VendorCatalogueReminderMail::class);
});

it('blocks a second manual reminder while the vendor is on cooldown and records nothing new', function () {
    $seller = $this->makeTestSeller();

    post(route('marketplace.admin.catalogue-coverage.remind', $seller->id));

    expect(VendorReminder::where('seller_id', $seller->id)->count())->toBe(1);

    post(route('marketplace.admin.catalogue-coverage.remind', $seller->id));

    expect(VendorReminder::where('seller_id', $seller->id)->count())->toBe(1);
});

it('allows an urgent reminder to override the cooldown from the admin dashboard', function () {
    $seller = $this->makeTestSeller();

    post(route('marketplace.admin.catalogue-coverage.remind', $seller->id));
    expect(VendorReminder::where('seller_id', $seller->id)->count())->toBe(1);

    post(route('marketplace.admin.catalogue-coverage.remind', $seller->id), ['urgent' => true]);

    expect(VendorReminder::where('seller_id', $seller->id)->where('type', 'urgent')->count())->toBe(1);
});

it('requires admin authentication to view the catalogue coverage dashboard', function () {
    auth()->guard('admin')->logout();

    $response = get(route('marketplace.admin.catalogue-coverage.index'));

    $response->assertRedirect();
});
