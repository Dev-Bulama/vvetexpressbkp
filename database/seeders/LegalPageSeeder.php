<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Faker\Factory;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $legalPages = [
            ['title' => 'Privacy Policy', 'slug' => 'privacy-policy'],
            ['title' => 'Terms of Service', 'slug' => 'terms-and-conditions'],
            ['title' => 'Return policy / Refund Policy', 'slug' => 'return-and-refund-policy'],
            ['title' => 'Shipping & Delivery Policy', 'slug' => 'shipping-and-delivery-policy'],
            ['title' => 'About Us', 'slug' => 'about-us'],
        ];

        $placeholder = '<p>Please update this page from the admin panel.</p>';

        foreach ($legalPages as $legalPage) {
            $legalPage['description'] = app()->environment('local')
                ? Factory::create()->randomHtml()
                : $placeholder;

            LegalPage::create($legalPage);
        }
    }
}
