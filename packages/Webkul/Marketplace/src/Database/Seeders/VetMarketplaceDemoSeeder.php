<?php

namespace Webkul\Marketplace\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\Category;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\CoreConfig;
use Webkul\Marketplace\Logistics\Adapters\InternalMarketplaceProvider;
use Webkul\Marketplace\Logistics\Adapters\NullProvider;
use Webkul\Marketplace\Models\DeliveryAgent;
use Webkul\Marketplace\Models\DeliveryAgentVehicle;
use Webkul\Marketplace\Models\LogisticsProvider;
use Webkul\Marketplace\Models\LogisticsServiceType;
use Webkul\Marketplace\Models\Seller;
use Webkul\Marketplace\Models\SellerProduct;
use Webkul\Product\Helpers\Indexers\Flat as FlatIndexer;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Theme\Models\ThemeCustomization;

class VetMarketplaceDemoSeeder extends Seeder
{
    /**
     * Vet-store category names, in display order.
     */
    protected array $categories = [
        'Dog Food',
        'Cat Food',
        'Puppy & Kitten Food',
        'Farm Animal Feed',
        'Poultry Feed & Supplies',
        'Vaccines & Medications',
        'Dewormers & Flea Control',
        'Vitamins & Supplements',
        'Grooming & Shampoo',
        'Leashes, Collars & Harnesses',
        'Pet Carriers & Crates',
        'Bedding & Housing',
        'Pet Toys',
        'Aquarium & Fish Supplies',
        'Bird Cages & Supplies',
        'Reptile Supplies',
        'Veterinary Equipment',
        'First Aid & Wound Care',
        'Litter & Waste Care',
        'Training & Behavior Aids',
    ];

    /**
     * Demo products: name, SKU suffix, base price, weight (kg). Indexed to
     * line up 1:1 with $categories above.
     */
    protected array $products = [
        ['Premium Adult Dog Food 15kg', 'DOG-FOOD-ADULT-15KG', 28000, 15],
        ['Premium Cat Food 3kg', 'CAT-FOOD-PREMIUM-3KG', 9500, 3],
        ['Puppy Starter Formula 5kg', 'PUPPY-STARTER-5KG', 14000, 5],
        ['Cattle Feed Concentrate 50kg', 'CATTLE-FEED-50KG', 32000, 50],
        ['Layers Mash 25kg', 'LAYERS-MASH-25KG', 18500, 25],
        ['Canine Multivalent Vaccine', 'VACCINE-CANINE-MULTI', 6500, 1],
        ['Broad-Spectrum Dewormer Tablets', 'DEWORMER-BROAD-TABS', 3200, 1],
        ['Pet Multivitamin Syrup 100ml', 'MULTIVIT-SYRUP-100ML', 4500, 1],
        ['Medicated Pet Shampoo 500ml', 'SHAMPOO-MEDICATED-500ML', 5200, 1],
        ['Nylon Leash & Collar Set', 'LEASH-COLLAR-SET', 6800, 1],
        ['Heavy-Duty Pet Carrier', 'CARRIER-HEAVY-DUTY', 22000, 3],
        ['Cozy Pet Bed (Medium)', 'PET-BED-MEDIUM', 15500, 2],
        ['Interactive Chew Toy', 'CHEW-TOY-INTERACTIVE', 3800, 1],
        ['Aquarium Filter Pump', 'AQUARIUM-FILTER-PUMP', 17500, 2],
        ['Bird Cage (Medium)', 'BIRD-CAGE-MEDIUM', 21000, 4],
        ['Reptile Heat Lamp', 'REPTILE-HEAT-LAMP', 9800, 1],
        ['Digital Vet Thermometer', 'VET-THERMOMETER-DIGITAL', 8200, 1],
        ['Wound Care First Aid Kit', 'FIRST-AID-KIT-PET', 11500, 1],
        ['Clumping Cat Litter 10kg', 'CAT-LITTER-CLUMPING-10KG', 7200, 10],
        ['Training Clicker Set', 'TRAINING-CLICKER-SET', 2600, 1],
    ];

    /**
     * Demo vendors: shop name, contact name, Lagos-area label, latitude, longitude.
     */
    protected array $vendors = [
        ['PetCare Lagos', 'Ada Okoye', 'Ikeja, Lagos', 6.6018, 3.3515],
        ['Lekki Vet Mart', 'Chidi Nwosu', 'Lekki, Lagos', 6.4698, 3.5852],
        ['Surulere Pet Supplies', 'Bola Adeyemi', 'Surulere, Lagos', 6.5027, 3.3541],
        ['Yaba Animal Health', 'Femi Balogun', 'Yaba, Lagos', 6.5158, 3.3707],
        ['VictoriaVet Store', 'Ngozi Eze', 'Victoria Island, Lagos', 6.4281, 3.4219],
        ['Ajah Pet Depot', 'Tunde Bakare', 'Ajah, Lagos', 6.4675, 3.5661],
        ['Ikorodu Farm & Pet', 'Kemi Salami', 'Ikorodu, Lagos', 6.6194, 3.5106],
        ['Apapa Vet Supplies', 'Chuka Obi', 'Apapa, Lagos', 6.4432, 3.3592],
        ['Gbagada Pet Corner', 'Yetunde Fashola', 'Gbagada, Lagos', 6.5533, 3.3889],
        ['Ogba Animal Care', 'Emeka Chukwu', 'Ogba, Lagos', 6.6280, 3.3390],
        ['Agege Vet Mart', 'Halima Bello', 'Agege, Lagos', 6.6155, 3.3216],
        ['Egbeda Pet Shop', 'Segun Aina', 'Egbeda, Lagos', 6.5940, 3.2870],
        ['Isolo Farm Supplies', 'Ifeoma Nnamdi', 'Isolo, Lagos', 6.5316, 3.3264],
        ['Festac Pet Store', 'Rasheed Lawal', 'Festac Town, Lagos', 6.4649, 3.2807],
        ['Maryland Vet Supplies', 'Chioma Udo', 'Maryland, Lagos', 6.5703, 3.3660],
        ['Magodo Pet Essentials', 'Wale Ogundipe', 'Magodo, Lagos', 6.6127, 3.3789],
        ['Oshodi Animal Health', 'Amaka Eze', 'Oshodi, Lagos', 6.5540, 3.3466],
        ['Ketu Pet & Farm', 'Biodun Fagbenle', 'Ketu, Lagos', 6.5928, 3.3928],
        ['Mile 2 Vet Supplies', 'Grace Okafor', 'Mile 2, Lagos', 6.4599, 3.3103],
        ['Ojota Pet Depot', 'Ibrahim Musa', 'Ojota, Lagos', 6.5804, 3.3765],
    ];

    public function run(): void
    {
        $channel = Channel::first();

        if (! $channel) {
            $this->command?->error('No channel found - run the essential seeder first.');

            return;
        }

        $this->seedCurrency($channel);

        $this->seedStoreSettings($channel);

        $categoryRepository = app(CategoryRepository::class);
        $productRepository = app(ProductRepository::class);

        $categoryIds = $this->seedCategories($categoryRepository, $channel->root_category_id);

        $productIds = $this->seedProducts($productRepository, $categoryIds);

        $sellerIds = $this->seedSellers();

        $this->seedOffers($sellerIds, $productIds);

        $this->seedFlashDeals($productRepository, $productIds);

        $this->seedLogistics();

        $this->seedCarrierConfig($channel);

        $this->seedHeroCarousel($channel);

        $this->command?->info('Vet marketplace demo data seeded: '.count($categoryIds).' categories, '.count($productIds).' products, '.count($sellerIds).' vendors.');
    }

    /**
     * The homepage hero always renders one fixed, code-defined branded slide
     * (packages/../resources/views/vendor/shop/home/index.blade.php) plus
     * whatever slides are in this record, editable through Bagisto's
     * existing Theme Customization screen (Admin > Content > Themes). Seeds
     * three illustrated placeholder slides (public/vetexpress/hero/*.svg) so
     * the carousel actually has something to auto-advance between out of
     * the box - an admin can replace or remove them any time from that
     * screen. Never overwrites images an admin has already customized here.
     */
    protected function seedHeroCarousel(Channel $channel): void
    {
        $carousel = ThemeCustomization::firstOrCreate(
            [
                'theme_code' => 'default',
                'channel_id' => $channel->id,
                'name' => 'VetExpress Hero Carousel',
            ],
            [
                'type' => ThemeCustomization::IMAGE_CAROUSEL,
                'sort_order' => 0,
                'status' => 1,
                'options' => ['images' => []],
            ]
        );

        if (! empty($carousel->options['images'] ?? [])) {
            return;
        }

        $carousel->update([
            'options' => [
                'images' => [
                    [
                        'image' => 'vetexpress/hero/dog-cat-food.svg',
                        'title' => 'Dog & Cat Food - Up to 30% Off',
                        'link' => '#top-categories',
                    ],
                    [
                        'image' => 'vetexpress/hero/vaccines.svg',
                        'title' => 'Vaccines & Medications',
                        'link' => '#top-categories',
                    ],
                    [
                        'image' => 'vetexpress/hero/farm-poultry.svg',
                        'title' => 'Farm & Poultry Supplies',
                        'link' => '#top-categories',
                    ],
                ],
            ],
        ]);
    }

    /**
     * The checkout's "Choose a Delivery Service" step is meant to be the
     * only way delivery pricing is decided - Bagisto's stock flat-rate/free
     * carriers would otherwise show up as competing options alongside our
     * real, distance-priced marketplace_logistics carrier at the onepage
     * shipping-method step. Switching them off here is a normal admin
     * setting (Settings > Sales > Shipping Methods), not something hidden.
     */
    protected function seedCarrierConfig(Channel $channel): void
    {
        foreach (['flatrate', 'free'] as $code) {
            CoreConfig::updateOrCreate(
                ['code' => "sales.carriers.{$code}.active", 'channel_code' => $channel->code, 'locale_code' => null],
                ['value' => '0']
            );
        }

        CoreConfig::updateOrCreate(
            ['code' => 'sales.carriers.marketplace_logistics.active', 'channel_code' => $channel->code, 'locale_code' => null],
            ['value' => '1']
        );
    }

    /**
     * VetExpress is a Nigerian marketplace - every price on the storefront
     * must render in Naira. Bagisto's installer only seeds USD by default, so
     * make NGN the channel's base currency (no exchange-rate conversion,
     * since demo prices are already Naira amounts) if it isn't already.
     */
    protected function seedCurrency(Channel $channel): void
    {
        $ngn = DB::table('currencies')->where('code', 'NGN')->first();

        if (! $ngn) {
            $ngnId = DB::table('currencies')->insertGetId([
                'code' => 'NGN',
                'name' => 'Nigerian Naira',
                'symbol' => '₦',
                'decimal' => 2,
                'group_separator' => ',',
                'decimal_separator' => '.',
                'currency_position' => 'left',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $ngnId = $ngn->id;
        }

        if ((int) $channel->base_currency_id !== $ngnId) {
            $channel->update(['base_currency_id' => $ngnId]);
        }

        if (! DB::table('channel_currencies')->where('channel_id', $channel->id)->where('currency_id', $ngnId)->exists()) {
            DB::table('channel_currencies')->insert(['channel_id' => $channel->id, 'currency_id' => $ngnId]);
        }

        if (! DB::table('currency_exchange_rates')->where('target_currency', $ngnId)->exists()) {
            DB::table('currency_exchange_rates')->insert(['target_currency' => $ngnId, 'rate' => 1]);
        }
    }

    /**
     * Real business-contact settings for the footer's Contact column, payment
     * area, and support links. These are ordinary admin-configurable
     * `core_config` values (Settings > General > Content > Footer) - seeded
     * here only so the demo storefront isn't blank; a real operator would
     * fill these in through the admin panel instead.
     */
    protected function seedStoreSettings(Channel $channel): void
    {
        $values = [
            'general.content.footer.support_email' => 'support@vetexpress.ng',
            'general.content.footer.support_phone' => '+234 800 123 4567',
            'general.content.footer.address' => '12 Allen Avenue, Ikeja, Lagos, Nigeria',
            'general.content.footer.facebook_url' => 'https://facebook.com/vetexpressng',
            'general.content.footer.instagram_url' => 'https://instagram.com/vetexpressng',
            'general.content.footer.x_url' => 'https://x.com/vetexpressng',
        ];

        foreach ($values as $code => $value) {
            CoreConfig::updateOrCreate(
                ['code' => $code, 'channel_code' => $channel->code, 'locale_code' => null],
                ['value' => $value]
            );
        }
    }

    /**
     * @return array<int, int> category name => category id
     */
    protected function seedCategories(CategoryRepository $categoryRepository, int $rootCategoryId): array
    {
        $ids = [];

        // Build the per-locale translation payload ourselves rather than
        // passing the repository's 'locale' => 'all' convenience key -
        // that path leaves a stray top-level 'locale' attribute on the
        // model that isn't a real `categories` column, and fails the insert.
        $locale = core()->getAllLocales()->first();

        foreach ($this->categories as $name) {
            $slug = 'vetdemo-'.str($name)->slug();

            $existing = Category::whereTranslation('slug', $slug)->first();

            if ($existing) {
                $ids[$name] = $existing->id;

                continue;
            }

            $category = $categoryRepository->create([
                'status' => 1,
                'display_mode' => 'products_and_description',
                'parent_id' => $rootCategoryId,
                $locale->code => [
                    'name' => $name,
                    'slug' => $slug,
                    'locale_id' => $locale->id,
                ],
            ]);

            $ids[$name] = $category->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $categoryIds
     * @return array<int, int> product SKU => product id
     */
    protected function seedProducts(ProductRepository $productRepository, array $categoryIds): array
    {
        $ids = [];

        $categoryNames = array_keys($categoryIds);

        foreach ($this->products as $index => [$name, $skuSuffix, $price, $weight]) {
            $sku = 'VETDEMO-'.$skuSuffix;

            $existing = $productRepository->findByField('sku', $sku)->first();

            if ($existing) {
                $ids[$sku] = $existing->id;

                continue;
            }

            $product = $productRepository->create([
                'attribute_family_id' => 1,
                'sku' => $sku,
                'type' => 'simple',
            ]);

            $product = $productRepository->update([
                'name' => $name,
                'url_key' => str($skuSuffix)->slug(),
                'price' => $price,
                'weight' => $weight,
                'status' => 1,
                'visible_individually' => 1,
            ], $product->id);

            $categoryName = $categoryNames[$index % count($categoryNames)];

            $product->categories()->sync([$categoryIds[$categoryName]]);

            // Bagisto's storefront reads product name/price/url_key from the
            // denormalized `product_flat` table, not the EAV rows directly.
            // The repository's create/update events are expected to refresh
            // it automatically, but that doesn't fire reliably outside a
            // real HTTP request, so do it explicitly here.
            app(FlatIndexer::class)->refresh($product);

            $ids[$sku] = $product->id;
        }

        return $ids;
    }

    /**
     * @return array<int, int> shop name => seller id
     */
    protected function seedSellers(): array
    {
        $ids = [];

        foreach ($this->vendors as $index => [$shopName, $contactName, $city, $lat, $lng]) {
            $email = 'vendor'.($index + 1).'@vetexpress.demo';

            // Deterministic 4.3-4.9 spread rather than a flat default, so
            // vendor comparison rows aren't all identical.
            $rating = round(4.3 + (($index * 7) % 13) / 20, 1);

            $seller = Seller::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $contactName,
                    'shop_name' => $shopName,
                    'password' => 'password123',
                    'phone' => '0801'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                    'city' => $city,
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'status' => Seller::STATUS_APPROVED,
                    'rating' => $rating,
                ]
            );

            $ids[$shopName] = $seller->id;
        }

        return $ids;
    }

    /**
     * @param  array<string, int>  $sellerIds
     * @param  array<string, int>  $productIds
     */
    protected function seedOffers(array $sellerIds, array $productIds): void
    {
        $sellers = array_values($sellerIds);
        $products = array_values($productIds);

        $sellerCount = count($sellers);

        if (! $sellerCount) {
            return;
        }

        foreach ($products as $productIndex => $productId) {
            $product = app(ProductRepository::class)->find($productId);

            $basePrice = (float) $product->price;

            // Spread each product across 3 vendors, evenly rotated so
            // coverage and price/stock variety look natural.
            foreach ([0, 7, 14] as $offset) {
                $sellerIndex = ($productIndex + $offset) % $sellerCount;
                $sellerId = $sellers[$sellerIndex];

                $priceVariance = 1 + ((($productIndex + $offset) % 5) - 2) * 0.03;
                $quantity = 8 + (($productIndex + $offset) % 6) * 4;

                SellerProduct::firstOrCreate(
                    [
                        'seller_id' => $sellerId,
                        'product_id' => $productId,
                    ],
                    [
                        'price' => round($basePrice * $priceVariance, 2),
                        'quantity' => $quantity,
                        'is_active' => true,
                    ]
                );
            }
        }
    }

    /**
     * Give a handful of products a genuine, time-boxed Bagisto special
     * price (special_price + special_price_to) so the storefront's Flash
     * Deals section has real deal data and a real countdown target -
     * rather than a fabricated timer. Also drops the cheapest seller
     * offer for each below the special price so the discount shown is real.
     *
     * @param  array<string, int>  $productIds  SKU => product id
     */
    protected function seedFlashDeals(ProductRepository $productRepository, array $productIds): void
    {
        $skus = array_keys($productIds);

        // A handful of distinct products, staggered end dates so the
        // countdown values aren't identical across cards. Bagisto's
        // special_price_to attribute is date-only (no time-of-day), so the
        // real countdown target is midnight at the end of that date.
        $deals = [
            ['sku' => $skus[0], 'discount' => 0.20, 'daysFromNow' => 0],
            ['sku' => $skus[4], 'discount' => 0.15, 'daysFromNow' => 1],
            ['sku' => $skus[9], 'discount' => 0.25, 'daysFromNow' => 2],
            ['sku' => $skus[14], 'discount' => 0.12, 'daysFromNow' => 3],
        ];

        foreach ($deals as $deal) {
            $productId = $productIds[$deal['sku']];
            $product = $productRepository->find($productId);
            $catalogPrice = (float) $product->price;
            $specialPrice = round($catalogPrice * (1 - $deal['discount']), 2);
            $specialPriceTo = now()->addDays($deal['daysFromNow'])->endOfDay();

            // Bagisto's update() treats every boolean attribute absent from
            // $data as false, and syncs categories to [] when the key is
            // missing - it's built for a full admin-form submission, not a
            // partial patch. Re-supply everything a second update() call
            // would otherwise silently wipe.
            $existingCategoryIds = $product->categories()->pluck('categories.id')->toArray();

            $product = $productRepository->update([
                'status' => 1,
                'visible_individually' => 1,
                'categories' => $existingCategoryIds,
                'special_price' => $specialPrice,
                'special_price_from' => now()->subDay()->toDateString(),
                'special_price_to' => $specialPriceTo->toDateString(),
            ], $productId);

            app(FlatIndexer::class)->refresh($product);

            // Make sure the cheapest active offer for this product is at
            // or below the special price, so the flash-deal discount shown
            // on the storefront reflects a real, purchasable price.
            $cheapestOffer = SellerProduct::where('product_id', $productId)
                ->where('is_active', true)
                ->orderBy('price')
                ->first();

            if ($cheapestOffer && (float) $cheapestOffer->price > $specialPrice) {
                $cheapestOffer->update(['price' => $specialPrice]);
            }
        }
    }

    /**
     * One real, working internal delivery provider (our own riders) plus a
     * disabled third-party example, so the "Choose a Delivery Service"
     * checkout step and the logistics admin have something genuine to show
     * without fabricating a connected courier integration that doesn't
     * exist. base_fee/fee_per_km mirror the heuristic already used for the
     * per-vendor delivery-fee estimate shown on the product page.
     */
    protected function seedLogistics(): void
    {
        $internalProvider = LogisticsProvider::firstOrCreate(
            ['code' => 'internal-marketplace'],
            [
                'name' => 'VetExpress Riders',
                'type' => LogisticsProvider::TYPE_INTERNAL,
                'adapter_class' => InternalMarketplaceProvider::class,
                'is_active' => true,
                'base_fee' => 500,
                'fee_per_km' => 150,
                'max_distance_km' => 40,
                'commission_percent' => 10,
                'rating' => 4.6,
            ]
        );

        LogisticsProvider::firstOrCreate(
            ['code' => 'third-party-courier'],
            [
                'name' => 'Third-Party Courier (not yet connected)',
                'type' => LogisticsProvider::TYPE_THIRD_PARTY,
                'adapter_class' => NullProvider::class,
                'is_active' => false,
                'base_fee' => 0,
                'fee_per_km' => 0,
                'commission_percent' => 0,
                'rating' => 0,
            ]
        );

        $serviceTypes = [
            ['code' => 'standard', 'name' => 'Standard Delivery', 'vehicle_type' => 'motorcycle', 'pickup_minutes' => 20, 'base_fee' => 500, 'fee_per_km' => 150],
            ['code' => 'express', 'name' => 'Express Delivery', 'vehicle_type' => 'motorcycle', 'pickup_minutes' => 10, 'base_fee' => 900, 'fee_per_km' => 220],
            ['code' => 'van', 'name' => 'Van Delivery (bulk orders)', 'vehicle_type' => 'van', 'pickup_minutes' => 30, 'base_fee' => 1500, 'fee_per_km' => 300],
        ];

        foreach ($serviceTypes as $serviceType) {
            LogisticsServiceType::firstOrCreate(
                ['logistics_provider_id' => $internalProvider->id, 'code' => $serviceType['code']],
                [
                    'name' => $serviceType['name'],
                    'vehicle_type' => $serviceType['vehicle_type'],
                    'description' => $serviceType['name'].' by a VetExpress rider.',
                    'tracking_available' => true,
                    'estimated_pickup_minutes' => $serviceType['pickup_minutes'],
                    'base_fee' => $serviceType['base_fee'],
                    'fee_per_km' => $serviceType['fee_per_km'],
                    'is_active' => true,
                ]
            );
        }

        $agents = [
            ['Tunde Bakare', 'tunde.rider@vetexpress.ng', 6.6018, 3.3515, 'motorcycle', 'ABJ-421-KJ'],
            ['Halima Bello', 'halima.rider@vetexpress.ng', 6.5703, 3.3660, 'motorcycle', 'LND-118-XA'],
            ['Chuka Obi', 'chuka.rider@vetexpress.ng', 6.4432, 3.3592, 'van', 'VAN-207-EE'],
        ];

        foreach ($agents as [$name, $email, $lat, $lng, $vehicleType, $plate]) {
            if (DeliveryAgent::where('email', $email)->exists()) {
                continue;
            }

            $vehicle = DeliveryAgentVehicle::create([
                'type' => $vehicleType,
                'plate_number' => $plate,
                'model' => $vehicleType === 'van' ? 'Toyota Hiace' : 'Bajaj Boxer',
                'color' => 'White',
            ]);

            DeliveryAgent::create([
                'logistics_provider_id' => $internalProvider->id,
                'vehicle_id' => $vehicle->id,
                'name' => $name,
                'email' => $email,
                'password' => 'TestRider123!',
                'phone' => '+234 800 000 0000',
                'status' => DeliveryAgent::STATUS_AVAILABLE,
                'current_latitude' => $lat,
                'current_longitude' => $lng,
                'last_location_at' => now(),
                'rating' => 4.7,
                'is_active' => true,
            ]);
        }
    }
}
