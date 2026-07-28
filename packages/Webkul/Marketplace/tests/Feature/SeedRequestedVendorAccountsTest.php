<?php

use Illuminate\Support\Facades\Artisan;
use Webkul\Marketplace\Models\Seller;

it('creates all six requested vendor accounts, approved, with their given coordinates', function () {
    Artisan::call('vetexpress:seed-requested-vendors');

    $seller = Seller::where('email', 'elite-veterinary-hub@vetexpress-vendors.local')->first();

    expect($seller)->not->toBeNull();
    expect($seller->shop_name)->toBe('Elite Veterinary Hub');
    expect($seller->status)->toBe(Seller::STATUS_APPROVED);
    expect((float) $seller->latitude)->toBe(4.80843);
    expect((float) $seller->longitude)->toBe(7.062377);

    expect(Seller::where('email', 'like', '%@vetexpress-vendors.local')->count())->toBe(6);
});

it('does not reset an existing vendor password when re-run', function () {
    Artisan::call('vetexpress:seed-requested-vendors');

    $seller = Seller::where('email', 'vetcrest-hub@vetexpress-vendors.local')->first();
    $originalHash = $seller->password;

    Artisan::call('vetexpress:seed-requested-vendors');

    $seller->refresh();

    expect($seller->password)->toBe($originalHash);
});
