<?php

use Webkul\Customer\Models\Customer;
use Webkul\Marketplace\Services\MetricsService;
use Webkul\Sales\Models\Order;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('renders the admin metrics page with north stars and all 11 plots', function () {
    $response = get(route('marketplace.admin.metrics.index'));

    $response->assertOk();
    $response->assertSee('North Star');
    $response->assertSee('GMV');
    $response->assertSee('Repeat Purchase Rate');
    $response->assertSee('Vendor SLA Adherence');
    $response->assertSee('Plot 01');
    $response->assertSee('Plot 11');
});

it('never shows a fabricated number for a metric with no data source - always "Not yet tracked"', function () {
    $response = get(route('marketplace.admin.metrics.index'));

    $response->assertOk();
    $response->assertSee('Not yet tracked');
    // Trust & Compliance has zero real data sources in this system today.
    $response->assertSee('Rx verification compliance');
    $response->assertSee('NPS');
});

it('computes GMV as the sum of base_grand_total for non-cancelled orders only, ignoring order currency', function () {
    $customer = Customer::factory()->create();

    Order::factory()->completed()->create([
        'customer_id' => $customer->id,
        'base_grand_total' => 1000,
        'grand_total' => 1000,
        'order_currency_code' => 'NGN',
    ]);

    Order::factory()->completed()->create([
        'customer_id' => $customer->id,
        'base_grand_total' => 500,
        // A different order_currency_code (e.g. the customer checked out in
        // GHS) must not affect the base-currency GMV sum.
        'grand_total' => 5.05,
        'order_currency_code' => 'GHS',
    ]);

    Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => Order::STATUS_CANCELED,
        'base_grand_total' => 99999,
    ]);

    $service = MetricsService::forPeriod('all');

    expect($service->gmv())->toBe(1500.0);
});

it('computes repeat purchase rate from real per-customer order counts in the period', function () {
    $repeatCustomer = Customer::factory()->create();
    $oneTimeCustomer = Customer::factory()->create();

    Order::factory()->completed()->create(['customer_id' => $repeatCustomer->id]);
    Order::factory()->completed()->create(['customer_id' => $repeatCustomer->id]);
    Order::factory()->completed()->create(['customer_id' => $oneTimeCustomer->id]);

    $service = MetricsService::forPeriod('all');

    // 1 of 2 distinct buyers has 2+ orders = 50%.
    expect($service->repeatPurchaseRate())->toBe(50.0);
});

it('computes vendor catalog activation as the share of approved sellers with an active listing', function () {
    $withListing = $this->makeTestSeller();
    $product = $this->makeTestProduct();
    $this->makeTestOffer($withListing, $product, quantity: 5);

    $withoutListing = $this->makeTestSeller();

    $service = MetricsService::forPeriod('all');
    $rate = $service->vendorCatalogActivationRate();

    // Both test sellers are approved; only one has an active listing, but
    // the shared dev DB has other approved sellers too - assert the rate
    // is between 0 and 100 and strictly less than 100 given a seller with
    // no listing exists, rather than a brittle exact-match on total count.
    expect($rate)->toBeGreaterThan(0.0);
    expect($rate)->toBeLessThan(100.0);
});
