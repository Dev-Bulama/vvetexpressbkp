<?php

use Webkul\Marketplace\Models\Seller;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('shows the add vendor form to a logged-in admin', function () {
    $this->loginAsAdmin();

    get(route('marketplace.admin.sellers.create'))
        ->assertOk()
        ->assertSeeText('Add Vendor');
});

it('lets an admin create a vendor account that is approved immediately', function () {
    $this->loginAsAdmin();

    $payload = [
        'name' => 'Jane Owner',
        'shop_name' => 'Jane\'s Pet Supplies',
        'email' => 'jane-'.str()->random(8).'@test.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '08012345678',
        'address' => '10 Allen Avenue',
        'city' => 'Ikeja',
        'latitude' => 6.6059,
        'longitude' => 3.3491,
    ];

    post(route('marketplace.admin.sellers.store'), $payload)
        ->assertRedirect();

    $seller = Seller::where('email', $payload['email'])->firstOrFail();

    expect($seller->status)->toBe(Seller::STATUS_APPROVED);
    expect($seller->shop_name)->toBe("Jane's Pet Supplies");
    expect((float) $seller->latitude)->toBe(6.6059);
    expect((float) $seller->longitude)->toBe(3.3491);
});

it('rejects an admin-created vendor with no pickup location', function () {
    $this->loginAsAdmin();

    $payload = [
        'name' => 'Jane Owner',
        'shop_name' => 'Jane\'s Pet Supplies',
        'email' => 'jane-'.str()->random(8).'@test.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = post(route('marketplace.admin.sellers.store'), $payload);

    $response->assertSessionHasErrors(['latitude', 'longitude']);
    expect(Seller::where('email', $payload['email'])->exists())->toBeFalse();
});

it('rejects an admin-created vendor with a duplicate email', function () {
    $this->loginAsAdmin();

    $existing = $this->makeTestSeller(['email' => 'dupe@test.local']);

    $payload = [
        'name' => 'Jane Owner',
        'shop_name' => 'Jane\'s Pet Supplies',
        'email' => 'dupe@test.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'latitude' => 6.6059,
        'longitude' => 3.3491,
    ];

    $response = post(route('marketplace.admin.sellers.store'), $payload);

    $response->assertSessionHasErrors(['email']);
    expect(Seller::where('email', 'dupe@test.local')->count())->toBe(1);
});

it('requires an admin to be logged in to reach the add vendor form', function () {
    get(route('marketplace.admin.sellers.create'))
        ->assertRedirect();
});
