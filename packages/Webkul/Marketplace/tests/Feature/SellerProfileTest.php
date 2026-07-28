<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

beforeEach(function () {
    Storage::fake('public');
});

it('shows the seller their current profile details', function () {
    $seller = $this->makeTestSeller(['shop_name' => 'Original Shop Name']);
    $this->actingAs($seller, 'seller');

    $response = get(route('marketplace.seller.profile.edit'));

    $response->assertOk();
    $response->assertSee('Original Shop Name');
    $response->assertSee($seller->email);
});

it('lets a seller update their shop name, email, and address', function () {
    $seller = $this->makeTestSeller(['shop_name' => 'Old Name']);
    $this->actingAs($seller, 'seller');

    put(route('marketplace.seller.profile.update'), [
        'name' => $seller->name,
        'shop_name' => 'New Shop Name',
        'email' => 'updated-'.str()->random(6).'@test.local',
        'phone' => '08011112222',
        'address' => '99 New Street',
        'city' => 'Abuja',
    ])->assertRedirect(route('marketplace.seller.profile.edit'));

    $seller->refresh();

    expect($seller->shop_name)->toBe('New Shop Name');
    expect($seller->address)->toBe('99 New Street');
    expect($seller->city)->toBe('Abuja');
});

it('lets a seller upload a shop logo', function () {
    $seller = $this->makeTestSeller();
    $this->actingAs($seller, 'seller');

    $logo = UploadedFile::fake()->image('logo.png');

    put(route('marketplace.seller.profile.update'), [
        'name' => $seller->name,
        'shop_name' => $seller->shop_name,
        'email' => $seller->email,
        'logo' => $logo,
    ])->assertRedirect(route('marketplace.seller.profile.edit'));

    $seller->refresh();

    expect($seller->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($seller->logo_path);
});

it('lets a seller change their password after confirming the current one', function () {
    $seller = $this->makeTestSeller(['password' => bcrypt('old-password')]);
    $this->actingAs($seller, 'seller');

    put(route('marketplace.seller.profile.update'), [
        'name' => $seller->name,
        'shop_name' => $seller->shop_name,
        'email' => $seller->email,
        'current_password' => 'old-password',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ])->assertRedirect(route('marketplace.seller.profile.edit'));

    $seller->refresh();

    expect(Hash::check('brand-new-password', $seller->password))->toBeTrue();
});

it('rejects a password change when the current password is wrong', function () {
    $seller = $this->makeTestSeller(['password' => bcrypt('old-password')]);
    $this->actingAs($seller, 'seller');

    $response = put(route('marketplace.seller.profile.update'), [
        'name' => $seller->name,
        'shop_name' => $seller->shop_name,
        'email' => $seller->email,
        'current_password' => 'totally-wrong',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $response->assertSessionHasErrors('current_password');

    $seller->refresh();
    expect(Hash::check('old-password', $seller->password))->toBeTrue();
});
