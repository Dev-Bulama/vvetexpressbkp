@php
    $marketplaceLat = session('marketplace.customer_location.lat');
    $marketplaceLng = session('marketplace.customer_location.lng');

    $offers = app(\Webkul\Marketplace\Repositories\SellerProductRepository::class)->findOffersForProduct(
        $product->id,
        $marketplaceLat ? (float) $marketplaceLat : null,
        $marketplaceLng ? (float) $marketplaceLng : null
    );

    $deliveryLabel = session('marketplace.customer_location.label', 'Set your location');
    $widgetId = 'vendor-availability-'.$product->id;

    $vendorLabel = fn ($offer) => $offer->shop_name === \Webkul\Marketplace\Models\Seller::SYSTEM_SELLER_SHOP_NAME
        ? 'In stock'
        : $offer->shop_name;
@endphp

<div class="mt-3 rounded-lg border border-slate-200 text-sm">
    <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-2.5 text-slate-600">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brandNavy" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
        Deliver to: <span class="font-medium text-brandNavy">{{ $deliveryLabel }}</span>
    </div>

    @if ($offers->isNotEmpty())
        <button
            type="button"
            class="flex w-full items-center justify-between px-4 py-3 text-left"
            aria-expanded="false"
            aria-controls="{{ $widgetId }}"
            onclick="
                const panel = document.getElementById('{{ $widgetId }}');
                const expanded = this.getAttribute('aria-expanded') === 'true';
                this.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                panel.classList.toggle('hidden');
                this.querySelector('[data-chevron]').style.transform = expanded ? 'rotate(0deg)' : 'rotate(180deg)';
            "
        >
            <span>
                <span class="font-medium text-brandNavy">
                    Available from {{ $offers->count() }} nearby {{ Str::plural('vendor', $offers->count()) }}
                </span>
                <span class="block text-xs text-brandGreen">
                    From {{ core()->currency($offers->min('price')) }} &middot; best offer: {{ $vendorLabel($offers->first()) }}
                </span>
            </span>

            <svg data-chevron xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-slate-400 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
        </button>

        <div id="{{ $widgetId }}" class="hidden divide-y divide-slate-100 border-t border-slate-100">
            @foreach ($offers as $offer)
                <div class="flex items-center justify-between gap-3 px-4 py-2.5">
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-800">
                            {{ $vendorLabel($offer) }}

                            @if ($loop->first)
                                <span class="ml-1 rounded-full bg-brandGreen/10 px-1.5 py-0.5 text-[10px] font-semibold text-brandGreen">Best</span>
                            @endif
                        </p>

                        <p class="text-xs text-slate-400">
                            {{ $offer->city ?? 'Location not set' }}

                            @if (isset($offer->distance_km))
                                &middot; {{ number_format($offer->distance_km, 1) }} km away
                            @endif

                            &middot; {{ $offer->quantity }} in stock
                        </p>
                    </div>

                    <p class="shrink-0 font-semibold text-brandGreen">{{ core()->currency($offer->price) }}</p>
                </div>
            @endforeach

            <div class="px-4 py-2.5">
                <a href="{{ route('shop.checkout.cart.index') }}" class="text-xs font-semibold text-brandNavy hover:underline">
                    Add to cart to choose your vendor at checkout &rsaquo;
                </a>
            </div>
        </div>
    @else
        <div class="px-4 py-3 text-slate-500">
            No vendors currently stock this product.
        </div>
    @endif
</div>
