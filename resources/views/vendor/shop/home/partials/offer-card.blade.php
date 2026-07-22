@php
    $product = $offer->productModel ?? null;
    $catalogPrice = (float) ($offer->catalog_price ?? 0);
    $price = (float) $offer->price;
    $hasDiscount = $catalogPrice > 0 && $price < $catalogPrice;
    $discountPercent = $hasDiscount ? round((($catalogPrice - $price) / $catalogPrice) * 100) : 0;

    $averageRating = $product ? $reviewHelper->getAverageRating($product) : 0;
    $totalReviews = $product ? $reviewHelper->getTotalReviews($product) : 0;

    $isNear = isset($offer->distance_km) && $offer->distance_km !== null && (float) $offer->distance_km < 5;
@endphp

<div class="relative flex h-full flex-col rounded-xl border border-slate-200 p-3 transition hover:border-brandGreen hover:shadow-sm">
    @if ($hasDiscount)
        <span class="absolute left-2 top-2 z-[1] rounded-full bg-brandGreen px-2 py-0.5 text-[11px] font-semibold text-white">
            -{{ $discountPercent }}%
        </span>
    @endif

    <button
        type="button"
        class="absolute right-2 top-2 z-[1] flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-400 hover:text-red-500"
        aria-label="Add to wishlist"
        onclick="marketplaceAddToWishlist({{ $offer->product_id }}, this)"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-4.35-10-9.03C.5 8.5 2 5 5.5 5c2 0 3.5 1.2 4.5 2.5C11 6.2 12.5 5 14.5 5 18 5 19.5 8.5 22 11.97 19 16.65 12 21 12 21z" /></svg>
    </button>

    <a href="{{ $offer->url_key ? route('shop.product_or_category.index', $offer->url_key) : '#' }}" class="block">
        <div class="flex h-24 items-center justify-center overflow-hidden rounded-lg bg-slate-50 text-slate-300">
            @if ($product?->base_image_url)
                <img src="{{ $product->base_image_url }}" alt="{{ $offer->name }}" class="h-full w-full object-cover">
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4zm4 4h8v8H8V8z" /></svg>
            @endif
        </div>

        <p class="mt-2 truncate text-sm font-medium text-slate-800">{{ $offer->name ?? $offer->sku }}</p>
    </a>

    @if ($totalReviews > 0)
        <div class="mt-0.5 flex items-center gap-1 text-xs text-amber-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01z" /></svg>
            <span class="font-medium text-slate-600">{{ number_format($averageRating, 1) }}</span>
            <span class="text-slate-400">({{ $totalReviews }})</span>
        </div>
    @endif

    <div class="mt-1 flex flex-wrap items-baseline gap-1.5">
        <p class="text-sm font-semibold text-brandGreen">{{ core()->formatPrice($price) }}</p>

        @if ($hasDiscount)
            <p class="text-xs text-slate-400 line-through">{{ core()->formatPrice($catalogPrice) }}</p>
        @endif
    </div>

    <p class="mt-1 flex items-center gap-1 truncate text-xs text-slate-400">
        {{ $offer->shop_name }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.4 1.6 2.85-.3 1.2 2.6 2.6 1.2-.3 2.85L22 12l-1.6 2.4.3 2.85-2.6 1.2-1.2 2.6-2.85-.3L12 22l-2.4-1.6-2.85.3-1.2-2.6-2.6-1.2.3-2.85L2 12l1.6-2.4-.3-2.85 2.6-1.2 1.2-2.6 2.85.3z" /><path d="M9 12l2 2 4-4" stroke="white" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round" /></svg>
    </p>

    @if (isset($offer->distance_km) && $offer->distance_km !== null)
        <p class="mt-0.5 text-xs text-slate-400">
            {{ number_format($offer->distance_km, 1) }} km away

            @if ($isNear)
                &middot; <span class="font-medium text-brandGreen">Delivery today</span>
            @endif
        </p>
    @endif

    @if (! empty($showCountdown) && ! empty($offer->special_price_to))
        <p class="mt-1 text-xs font-semibold text-rose-600">
            Ends in <span data-countdown-to="{{ \Illuminate\Support\Carbon::parse($offer->special_price_to)->endOfDay()->toIso8601String() }}">--:--:--</span>
        </p>
    @endif

    <button
        type="button"
        class="mt-2 w-full rounded-lg border border-brandNavy px-2 py-1.5 text-xs font-medium text-brandNavy transition hover:bg-brandNavy hover:text-white"
        onclick="marketplaceAddToCart({{ $offer->product_id }}, this)"
    >
        Add to Cart
    </button>
</div>
