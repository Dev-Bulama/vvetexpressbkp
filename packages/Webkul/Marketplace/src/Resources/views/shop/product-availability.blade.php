@php
    $offers = app(\Webkul\Marketplace\Repositories\SellerProductRepository::class)->findOffersForProduct($product->id);
@endphp

@if ($offers->isNotEmpty())
    <div class="mt-3 rounded-lg border border-brandGreen/30 bg-brandGreen/10 px-4 py-3 text-sm">
        <p class="font-medium text-brandNavy">
            Available from {{ $offers->count() }} {{ Str::plural('vendor', $offers->count()) }} near you
        </p>
        <p class="mt-0.5 text-brandGreen">
            From {{ core()->formatPrice($offers->min('price')) }} &middot; best offer: {{ $offers->first()->shop_name }}
        </p>
    </div>
@else
    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
        No vendors currently stock this product.
    </div>
@endif
