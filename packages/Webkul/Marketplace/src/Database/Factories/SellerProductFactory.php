<?php

namespace Webkul\Marketplace\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Webkul\Marketplace\Models\SellerProduct;

class SellerProductFactory extends Factory
{
    protected $model = SellerProduct::class;

    public function definition(): array
    {
        return [
            'price'     => $this->faker->randomFloat(2, 5, 500),
            'quantity'  => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
