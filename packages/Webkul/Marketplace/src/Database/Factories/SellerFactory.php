<?php

namespace Webkul\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Webkul\Marketplace\Models\Seller;

class SellerFactory extends Factory
{
    protected $model = Seller::class;

    public function definition(): array
    {
        return [
            'name'              => $this->faker->name(),
            'shop_name'         => $this->faker->company(),
            'email'             => $this->faker->unique()->safeEmail(),
            'password'          => Hash::make('password'),
            'phone'             => $this->faker->phoneNumber(),
            'address'           => $this->faker->streetAddress(),
            'city'              => $this->faker->city(),
            'latitude'          => $this->faker->latitude(),
            'longitude'         => $this->faker->longitude(),
            'status'            => Seller::STATUS_APPROVED,
            'email_verified_at' => now(),
        ];
    }
}
