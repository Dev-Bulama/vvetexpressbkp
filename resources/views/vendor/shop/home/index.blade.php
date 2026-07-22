@php
    $channel = core()->getCurrentChannel();

    $recommended = \Webkul\Marketplace\Models\SellerProduct::query()
        ->with(['seller', 'product'])
        ->where('is_active', true)
        ->where('quantity', '>', 0)
        ->whereHas('seller', fn ($q) => $q->where('status', 'approved'))
        ->latest()
        ->take(6)
        ->get();
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
        <div class="grid gap-4 md:grid-cols-[1fr_280px]">
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-700 to-emerald-900 p-8 text-white md:p-10">
                <div class="relative z-10 max-w-md">
                    <h1 class="text-3xl font-bold leading-tight md:text-4xl">Everything You Need, Closer to You</h1>
                    <p class="mt-3 text-emerald-100">Shop trusted vendors near you</p>
                    <a
                        href="{{ route('shop.home.index') }}#top-categories"
                        class="mt-6 inline-block rounded-lg bg-white px-6 py-3 font-semibold text-emerald-800 transition hover:bg-emerald-50"
                    >
                        Shop Now
                    </a>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex-1 rounded-2xl bg-slate-100 p-5">
                    <p class="font-semibold text-slate-800">Fast Local Delivery</p>
                    <p class="mt-1 text-sm text-slate-500">Get your items delivered quickly and reliably.</p>
                    <a href="#" class="mt-2 inline-block text-sm font-semibold text-emerald-700">Learn more &rsaquo;</a>
                </div>

                <div class="flex-1 rounded-2xl bg-slate-100 p-5">
                    <p class="font-semibold text-slate-800">Become a Vendor</p>
                    <p class="mt-1 text-sm text-slate-500">Grow your business by reaching more customers.</p>
                    <a href="{{ route('marketplace.seller.register.index') }}" class="mt-2 inline-block text-sm font-semibold text-emerald-700">Join Now &rsaquo;</a>
                </div>
            </div>
        </div>

        {{-- Trust badges --}}
        <div class="mt-6 grid grid-cols-2 gap-4 rounded-2xl border border-slate-200 p-6 sm:grid-cols-4">
            @foreach ([
                ['title' => 'Verified Vendors', 'text' => 'Quality products from trusted local vendors'],
                ['title' => 'Secure Payments', 'text' => 'Your payment information is safe and encrypted'],
                ['title' => 'Fast Delivery', 'text' => 'Quick delivery from vendors near you'],
                ['title' => 'Easy Returns', 'text' => 'Hassle-free returns within 7-14 days'],
            ] as $badge)
                <div class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $badge['title'] }}</p>
                        <p class="text-xs text-slate-500">{{ $badge['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-8 grid gap-8 md:grid-cols-[220px_1fr]">
            {{-- Shop by category sidebar --}}
            <aside class="hidden md:block">
                <p class="mb-3 text-sm font-semibold text-slate-800">Shop by Category</p>

                <ul class="space-y-1">
                    @forelse ($categories as $category)
                        <li>
                            <a
                                href="{{ $category->url }}"
                                class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-100 hover:text-emerald-700"
                            >
                                {{ $category->name }}

                                @if (! empty($category->children) && count($category->children))
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                @endif
                            </a>
                        </li>
                    @empty
                        <li class="text-sm text-slate-400">No categories yet.</li>
                    @endforelse
                </ul>
            </aside>

            <div>
                {{-- Top categories icon grid --}}
                <section id="top-categories">
                    <h2 class="text-lg font-semibold text-slate-800">Top Categories</h2>

                    <div class="mt-4 grid grid-cols-4 gap-4 sm:grid-cols-6 md:grid-cols-7">
                        @foreach ($categories->take(13) as $category)
                            <a href="{{ $category->url }}" class="flex flex-col items-center gap-2 text-center">
                                <span class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </span>
                                <span class="text-xs text-slate-600">{{ $category->name }}</span>
                            </a>
                        @endforeach

                        <a href="{{ route('shop.home.index') }}" class="flex flex-col items-center gap-2 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                            </span>
                            <span class="text-xs text-slate-600">View All</span>
                        </a>
                    </div>
                </section>

                {{-- Recommended near you --}}
                <section class="mt-10">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-800">Recommended Near You</h2>
                            <p class="text-xs text-slate-400">Products available from vendors close to you</p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        @forelse ($recommended as $offer)
                            <div class="rounded-xl border border-slate-200 p-3">
                                <div class="flex h-24 items-center justify-center rounded-lg bg-slate-50 text-slate-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4zm4 4h8v8H8V8z" /></svg>
                                </div>

                                <p class="mt-2 truncate text-sm font-medium text-slate-800">{{ $offer->product?->name ?? $offer->product?->sku }}</p>

                                <p class="text-sm font-semibold text-emerald-700">{{ core()->formatPrice($offer->price) }}</p>

                                <p class="mt-1 truncate text-xs text-slate-400">{{ $offer->seller?->shop_name }}</p>
                            </div>
                        @empty
                            <p class="col-span-full text-sm text-slate-400">No vendor offers yet - check back soon.</p>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-shop::layouts>
