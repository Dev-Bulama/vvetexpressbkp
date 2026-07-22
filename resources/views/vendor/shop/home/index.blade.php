@php
    use Webkul\Marketplace\Helpers\CategoryIcon;
    use Webkul\Marketplace\Repositories\SellerProductRepository;
    use Webkul\Product\Helpers\Review;
    use Webkul\Product\Models\Product;

    $channel = core()->getCurrentChannel();

    $sellerProductRepository = app(SellerProductRepository::class);
    $reviewHelper = app(Review::class);

    $customerLat = session('marketplace.customer_location.lat');
    $customerLng = session('marketplace.customer_location.lng');

    $recommendedOffers = $sellerProductRepository->bestOfferPerProduct(
        $customerLat ? (float) $customerLat : null,
        $customerLng ? (float) $customerLng : null,
        12
    );

    $flashOffers = $sellerProductRepository->activeFlashOffers(8);

    $rootCategory = app(\Webkul\Category\Repositories\CategoryRepository::class)->find($channel->root_category_id);

    // Hydrate the small set of offers we're about to render with their real
    // Product models (name/reviews/image are EAV-backed, not on the raw
    // offer row) - cheap here since both lists are capped at a dozen items.
    $productCache = [];

    $hydrate = function ($offer) use (&$productCache) {
        if (! isset($productCache[$offer->product_id])) {
            $productCache[$offer->product_id] = Product::find($offer->product_id);
        }

        $offer->productModel = $productCache[$offer->product_id];

        return $offer;
    };

    $recommendedOffers = $recommendedOffers->map($hydrate);
    $flashOffers = $flashOffers->map($hydrate);
@endphp

@push('meta')
    <meta name="title" content="{{ $channel->home_seo['meta_title'] ?? config('app.name') }}" />
    <meta name="description" content="{{ $channel->home_seo['meta_description'] ?? '' }}" />
@endpush

@push('scripts')
    @if (! empty($categories))
        <script>
            localStorage.setItem('categories', JSON.stringify(@json($categories)));
        </script>
    @endif
@endpush

<x-shop::layouts>
    <x-slot:title>
        {{ $channel->home_seo['meta_title'] ?? config('app.name') }}
    </x-slot>

    <div class="mx-auto max-w-[1200px] px-4 py-8">
        {{-- Hero --}}
        <div class="grid gap-4 md:grid-cols-[1fr_280px] lg:grid-cols-[220px_1fr_280px]">
            {{-- Shop by category sidebar --}}
            <aside class="hidden lg:block">
                <p class="mb-3 rounded-t-xl bg-brandNavy px-3 py-2 text-sm font-semibold text-white">Shop by Category</p>

                <ul class="space-y-1 rounded-b-xl border border-t-0 border-slate-200 p-2">
                    @forelse ($categories->take(12) as $category)
                        <li>
                            <a
                                href="{{ $category->url }}"
                                class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-brandGreen/10 hover:text-brandNavy"
                            >
                                <span class="flex items-center gap-2 truncate">
                                    @include('marketplace::components.category-icon', ['icon' => CategoryIcon::keyFor($category->name), 'class' => 'h-4 w-4 shrink-0'])
                                    <span class="truncate">{{ $category->name }}</span>
                                </span>

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">No categories yet.</li>
                    @endforelse

                    @if ($rootCategory)
                        <li>
                            <a
                                href="{{ $rootCategory->url }}"
                                class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm font-medium text-brandNavy hover:bg-brandGreen/10"
                            >
                                Other Categories

                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </a>
                        </li>
                    @endif
                </ul>
            </aside>

            {{-- Hero banner --}}
            <div class="relative overflow-hidden rounded-2xl bg-brandNavy p-8 text-white md:p-10">
                <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[52%] md:block">
                    <svg xmlns="http://www.w3.org/2000/svg" class="absolute right-6 top-4 h-9 w-9 text-brandGreen" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C7.6 2 4 5.6 4 10c0 6 8 12 8 12s8-6 8-12c0-4.4-3.6-8-8-8zm0 11a3 3 0 110-6 3 3 0 010 6z" /></svg>

                    <div class="absolute right-8 top-16 flex h-20 w-20 items-center justify-center rounded-2xl bg-white/15 text-white">
                        @include('marketplace::components.category-icon', ['icon' => 'feed', 'class' => 'h-9 w-9'])
                    </div>

                    <div class="absolute right-40 top-8 flex h-16 w-16 items-center justify-center rounded-2xl bg-brandGreen shadow-lg">
                        @include('marketplace::components.category-icon', ['icon' => 'paw', 'class' => 'h-8 w-8 text-white'])
                    </div>

                    <div class="absolute bottom-10 right-32 flex h-24 w-24 items-center justify-center rounded-full bg-white/10">
                        @include('marketplace::components.category-icon', ['icon' => 'syringe', 'class' => 'h-10 w-10'])
                    </div>

                    <div class="absolute bottom-6 right-6 flex h-16 w-16 items-center justify-center rounded-xl bg-white/20">
                        @include('marketplace::components.category-icon', ['icon' => 'droplet', 'class' => 'h-8 w-8'])
                    </div>
                </div>

                <div class="relative max-w-md">
                    <span class="inline-block rounded-full bg-brandGreen px-3 py-1 text-xs font-semibold uppercase tracking-wide text-white">Marketplace</span>
                    <h1 class="mt-3 text-3xl font-bold leading-tight md:text-4xl">Everything You Need, Closer to You</h1>
                    <p class="mt-3 text-white/80">Shop trusted vendors near you</p>
                    <a
                        href="#top-categories"
                        class="mt-6 inline-block rounded-lg bg-brandGreen px-6 py-3 font-semibold text-white transition hover:opacity-90"
                    >
                        Shop Now
                    </a>
                </div>
            </div>

            {{-- Promo cards --}}
            <div class="flex flex-col gap-4">
                <div class="flex flex-1 items-start justify-between gap-3 rounded-2xl bg-slate-100 p-5">
                    <div>
                        <p class="font-semibold text-slate-800">Fast Local Delivery</p>
                        <p class="mt-1 text-sm text-slate-500">Get your items delivered quickly and reliably.</p>
                        <a href="#top-categories" class="mt-2 inline-block text-sm font-semibold text-brandNavy">Learn more &rsaquo;</a>
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 shrink-0 text-brandNavy/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="6" cy="18" r="2" /><circle cx="17" cy="18" r="2" />
                        <path d="M4 18h1M8 18h6M4 14l2-5h4l3 5M9 9V6h3l3 3" />
                    </svg>
                </div>

                <div class="flex flex-1 items-start justify-between gap-3 rounded-2xl bg-brandGreen/10 p-5">
                    <div>
                        <p class="font-semibold text-slate-800">Become a Vendor</p>
                        <p class="mt-1 text-sm text-slate-500">Grow your business by reaching more customers.</p>
                        <a href="{{ route('marketplace.seller.register.index') }}" class="mt-2 inline-block text-sm font-semibold text-brandGreen">Join Now &rsaquo;</a>
                    </div>

                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l1.5-5h15L21 9M3 9v10a1 1 0 001 1h16a1 1 0 001-1V9M3 9h18M9 20v-6h6v6" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Trust badges --}}
        <div class="mt-6 grid grid-cols-2 divide-y divide-slate-200 rounded-2xl border border-slate-200 p-6 sm:grid-cols-4 sm:divide-y-0 sm:divide-x">
            @foreach ([
                ['title' => 'Verified Vendors', 'text' => 'Quality products from trusted local vendors'],
                ['title' => 'Secure Payments', 'text' => 'Your payment information is safe and encrypted'],
                ['title' => 'Fast Delivery', 'text' => 'Quick delivery from vendors near you'],
                ['title' => 'Easy Returns', 'text' => 'Hassle-free returns within 7-14 days'],
            ] as $badge)
                <div class="flex items-start gap-3 py-3 first:pt-0 sm:py-0 sm:first:pl-0 sm:px-5">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brandGreen/10 text-brandGreen">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $badge['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $badge['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Top categories icon grid --}}
        <section id="top-categories" class="mt-10">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-brandNavy">Top Categories</h2>

                @if ($rootCategory)
                    <a href="{{ $rootCategory->url }}" class="text-sm font-semibold text-brandGreen">See All &rsaquo;</a>
                @endif
            </div>

            <div class="mt-4 flex gap-5 overflow-x-auto pb-2 sm:grid sm:grid-cols-6 sm:gap-4 md:grid-cols-11 sm:overflow-visible">
                @php
                    $swatches = ['bg-brandGreen/10 text-brandGreen', 'bg-brandNavy/10 text-brandNavy', 'bg-amber-100 text-amber-700', 'bg-sky-100 text-sky-700', 'bg-rose-100 text-rose-700'];
                @endphp

                @foreach ($categories->take(10) as $index => $category)
                    <a href="{{ $category->url }}" class="flex shrink-0 flex-col items-center gap-2 text-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full {{ $swatches[$index % count($swatches)] }}">
                            @include('marketplace::components.category-icon', ['icon' => CategoryIcon::keyFor($category->name), 'class' => 'h-7 w-7'])
                        </span>
                        <span class="w-16 truncate text-xs text-slate-600">{{ $category->name }}</span>
                    </a>
                @endforeach

                @if ($rootCategory)
                    <a href="{{ $rootCategory->url }}" class="flex shrink-0 flex-col items-center gap-2 text-center">
                        <span class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        </span>
                        <span class="text-xs text-slate-600">View All</span>
                    </a>
                @endif
            </div>
        </section>

        {{-- Recommended near you --}}
        <section class="mt-10">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-brandNavy">Recommended Near You</h2>
                    <p class="text-xs text-slate-400">
                        Products available from vendors close to
                        {{ session('marketplace.customer_location.label', 'you') }}
                    </p>
                </div>

                @if ($rootCategory)
                    <a href="{{ $rootCategory->url }}" class="text-sm font-semibold text-brandGreen">See All &rsaquo;</a>
                @endif
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @forelse ($recommendedOffers as $offer)
                    @include('shop::home.partials.offer-card', ['offer' => $offer, 'reviewHelper' => $reviewHelper])
                @empty
                    <p class="col-span-full text-sm text-slate-400">No vendor offers yet - check back soon.</p>
                @endforelse
            </div>
        </section>

        {{-- Flash deals --}}
        @if ($flashOffers->isNotEmpty())
            <section class="mt-10">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M13 2L3 14h7l-1 8 11-14h-7l1-8z" /></svg>

                        <div>
                            <h2 class="text-lg font-semibold text-brandNavy">Flash Deals</h2>
                            <p class="text-xs text-slate-400">Limited time offers - don't miss out!</p>
                        </div>
                    </div>

                    @if ($rootCategory)
                        <a href="{{ $rootCategory->url }}" class="text-sm font-semibold text-brandGreen">See All Deals &rsaquo;</a>
                    @endif
                </div>

                <div class="mt-4 flex gap-4 overflow-x-auto pb-2">
                    @foreach ($flashOffers as $offer)
                        <div class="w-44 shrink-0">
                            @include('shop::home.partials.offer-card', ['offer' => $offer, 'reviewHelper' => $reviewHelper, 'showCountdown' => true])
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    {{-- Floating cart button --}}
    <a
        href="{{ route('shop.checkout.cart.index') }}"
        class="fixed bottom-6 z-20 flex h-14 w-14 items-center justify-center rounded-full bg-brandGreen text-white shadow-lg transition hover:scale-105 hover:opacity-90 ltr:right-6 rtl:left-6"
        aria-label="View cart"
        id="floating-cart-button"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1" /><circle cx="20" cy="21" r="1" /><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6" /></svg>

        <span
            id="floating-cart-count"
            class="absolute -right-1 -top-1 hidden h-5 min-w-5 items-center justify-center rounded-full bg-white px-1 text-xs font-bold text-brandGreen"
        ></span>
    </a>

    @push('scripts')
        <script>
            (function () {
                function updateFloatingCartCount(count) {
                    const badge = document.getElementById('floating-cart-count');

                    if (! badge) return;

                    if (count > 0) {
                        badge.textContent = count;
                        badge.classList.remove('hidden');
                        badge.classList.add('flex');
                    } else {
                        badge.classList.add('hidden');
                        badge.classList.remove('flex');
                    }
                }

                function marketplaceEmitter() {
                    return window.app?.config?.globalProperties?.$emitter ?? null;
                }

                document.addEventListener('DOMContentLoaded', function () {
                    if (window.axios) {
                        window.axios.get('{{ route('shop.api.checkout.cart.index') }}')
                            .then(response => updateFloatingCartCount(response.data?.data?.items_count ?? 0))
                            .catch(() => {});
                    }

                    const emitter = marketplaceEmitter();

                    if (emitter) {
                        emitter.on('update-mini-cart', (cart) => updateFloatingCartCount(cart?.items_count ?? 0));
                    }
                });

                // Flash deal countdowns
                document.addEventListener('DOMContentLoaded', function () {
                    const nodes = document.querySelectorAll('[data-countdown-to]');

                    if (! nodes.length) return;

                    function tick() {
                        nodes.forEach(node => {
                            const target = new Date(node.dataset.countdownTo).getTime();
                            const diff = Math.max(0, target - Date.now());

                            const hours = Math.floor(diff / 3600000);
                            const minutes = Math.floor((diff % 3600000) / 60000);
                            const seconds = Math.floor((diff % 60000) / 1000);

                            node.textContent = String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');
                        });
                    }

                    tick();
                    setInterval(tick, 1000);
                });

                // Marketplace wishlist / add-to-cart buttons (custom offer cards)
                window.marketplaceAddToWishlist = function (productId, btn) {
                    if (! window.axios) return;

                    window.axios.post('{{ route('shop.api.customers.account.wishlist.store') }}', { product_id: productId })
                        .then(() => {
                            btn.classList.add('text-red-500');
                            btn.classList.remove('text-slate-400');
                        })
                        .catch(error => {
                            if (error.response && error.response.status === 401) {
                                window.location.href = '{{ route('shop.customer.session.index') }}';
                            }
                        });
                };

                window.marketplaceAddToCart = function (productId, btn) {
                    if (! window.axios) return;

                    const original = btn.textContent;
                    btn.disabled = true;
                    btn.textContent = 'Adding...';

                    window.axios.post('{{ route('shop.api.checkout.cart.store') }}', { product_id: productId, quantity: 1 })
                        .then(response => {
                            btn.textContent = 'Added';

                            if (response.data?.data) {
                                updateFloatingCartCount(response.data.data.items_count ?? 0);

                                const emitter = marketplaceEmitter();

                                if (emitter) {
                                    emitter.emit('update-mini-cart', response.data.data);
                                }
                            }

                            setTimeout(() => { btn.textContent = original; btn.disabled = false; }, 1500);
                        })
                        .catch(() => {
                            btn.textContent = original;
                            btn.disabled = false;
                        });
                };
            })();
        </script>
    @endpush
</x-shop::layouts>
