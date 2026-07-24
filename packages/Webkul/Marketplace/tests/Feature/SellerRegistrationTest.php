<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Marketplace\Models\Seller;

use function Pest\Laravel\post;

beforeEach(function () {
    Storage::fake('public');
});

function validSellerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Owner',
        'shop_name' => 'Test Shop '.str()->random(8),
        'email' => 'seller-'.str()->random(10).'@test.local',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ], $overrides);
}

it('registers a seller with an auto-detected shop location', function () {
    $payload = validSellerPayload([
        'latitude' => 6.5244,
        'longitude' => 3.3792,
        'address' => '12 Test Street',
        'city' => 'Lagos',
    ]);

    post(route('marketplace.seller.register.store'), $payload)
        ->assertRedirect(route('marketplace.seller.session.index'));

    $seller = Seller::where('email', $payload['email'])->firstOrFail();

    expect((float) $seller->latitude)->toBe(6.5244);
    expect((float) $seller->longitude)->toBe(3.3792);
    expect($seller->address)->toBe('12 Test Street');
    expect($seller->city)->toBe('Lagos');
    expect($seller->status)->toBe(Seller::STATUS_PENDING);
});

it('registers a seller without a location when detection was not used', function () {
    $payload = validSellerPayload();

    post(route('marketplace.seller.register.store'), $payload)
        ->assertRedirect(route('marketplace.seller.session.index'));

    $seller = Seller::where('email', $payload['email'])->firstOrFail();

    expect($seller->latitude)->toBeNull();
    expect($seller->longitude)->toBeNull();
});

it('stores a live-recorded shop verification video attached to the registration', function () {
    $video = UploadedFile::fake()->create('shop-verification.webm', 500, 'video/webm');

    $payload = validSellerPayload(['verification_video' => $video]);

    post(route('marketplace.seller.register.store'), $payload)
        ->assertRedirect(route('marketplace.seller.session.index'));

    $seller = Seller::where('email', $payload['email'])->firstOrFail();

    expect($seller->verification_video_path)->not->toBeNull();
    expect($seller->verification_video_recorded_at)->not->toBeNull();

    Storage::disk('public')->assertExists($seller->verification_video_path);
});

it('registers a seller successfully without a verification video', function () {
    $payload = validSellerPayload();

    post(route('marketplace.seller.register.store'), $payload)
        ->assertRedirect(route('marketplace.seller.session.index'));

    $seller = Seller::where('email', $payload['email'])->firstOrFail();

    expect($seller->verification_video_path)->toBeNull();
});

it('rejects a verification video that is not an actual video file', function () {
    $notAVideo = UploadedFile::fake()->create('shop-verification.txt', 10, 'text/plain');

    $payload = validSellerPayload(['verification_video' => $notAVideo]);

    $response = post(route('marketplace.seller.register.store'), $payload);

    $response->assertSessionHasErrors('verification_video');
    expect(Seller::where('email', $payload['email'])->exists())->toBeFalse();
});

it('rejects an oversized verification video', function () {
    $tooLarge = UploadedFile::fake()->create('shop-verification.webm', 26000, 'video/webm');

    $payload = validSellerPayload(['verification_video' => $tooLarge]);

    $response = post(route('marketplace.seller.register.store'), $payload);

    $response->assertSessionHasErrors('verification_video');
});
