<?php

use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

beforeEach(function () {
    Storage::fake('public');
    $this->loginAsAdmin();
});

it('shows the shop verification video on the admin seller review page when one was recorded', function () {
    $seller = $this->makeTestSeller();

    Storage::disk('public')->put('sellers/verification/test-clip.webm', 'fake-video-bytes');

    $seller->update([
        'verification_video_path' => 'sellers/verification/test-clip.webm',
        'verification_video_recorded_at' => now(),
    ]);

    $response = get(route('marketplace.admin.sellers.edit', $seller->id));

    $response->assertOk();
    $response->assertSee('Shop Verification Video');
    $response->assertSee(Storage::disk('public')->url('sellers/verification/test-clip.webm'), false);
});

it('does not show a verification video section when the seller never recorded one', function () {
    $seller = $this->makeTestSeller();

    $response = get(route('marketplace.admin.sellers.edit', $seller->id));

    $response->assertOk();
    $response->assertDontSee('Shop Verification Video');
});
