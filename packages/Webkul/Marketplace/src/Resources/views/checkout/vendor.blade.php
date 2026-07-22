@push('meta')
    <meta name="title" content="Delivery &amp; Vendor" />
@endpush

<x-shop::layouts>
    <x-slot:title>Delivery &amp; Vendor</x-slot>

    <div class="mx-auto max-w-[1100px] px-4 py-8">
        {{-- Stepper --}}
        <div class="mb-8 flex items-center justify-center gap-4 text-sm">
            <div class="flex items-center gap-2 text-emerald-700">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-emerald-600 bg-emerald-600 text-white">&#10003;</span>
                <span class="font-medium">Cart</span>
            </div>
            <div class="h-px w-16 bg-emerald-600"></div>
            <div class="flex items-center gap-2 text-emerald-700">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-emerald-600 bg-emerald-600 text-white">2</span>
                <span class="font-medium">Delivery &amp; Vendor</span>
            </div>
            <div class="h-px w-16 bg-slate-200"></div>
            <div class="flex items-center gap-2 text-slate-400">
                <span class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-slate-300">3</span>
                <span>Payment</span>
            </div>
        </div>

        <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
            <div>
                {{-- Location + sort --}}
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                    <div class="text-sm">
                        @if ($hasLocation)
                            <span class="font-medium text-slate-700">Showing vendors near your current location</span>
                        @else
                            <span class="text-slate-500">Share your location to see the nearest vendors and accurate delivery estimates.</span>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            id="use-my-location"
                            class="rounded-lg border border-emerald-600 px-3 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-50"
                        >
                            Use my location
                        </button>

                        <form method="GET" action="{{ route('marketplace.checkout.vendor.index') }}" class="flex items-center gap-1">
                            <input type="hidden" name="lat" value="{{ request('lat') }}">
                            <input type="hidden" name="lng" value="{{ request('lng') }}">
                            <select name="sort" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
                                <option value="recommended" {{ $sort === 'recommended' ? 'selected' : '' }}>Recommended</option>
                                <option value="distance" {{ $sort === 'distance' ? 'selected' : '' }}>Nearest first</option>
                                <option value="price" {{ $sort === 'price' ? 'selected' : '' }}>Lowest price</option>
                            </select>
                        </form>
                    </div>
                </div>

                @if (! $allSelectable)
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        One or more items in your cart currently have no available vendor. Please update your cart before continuing.
                    </div>
                @endif

                <form method="POST" action="{{ route('marketplace.checkout.vendor.store') }}">
                    @csrf

                    @foreach ($products as $row)
                        <div class="mb-8">
                            <h3 class="mb-3 font-semibold text-slate-800">{{ $row['cart_item']->name }}</h3>

                            @if ($row['offers']->isEmpty())
                                <p class="rounded-lg border border-slate-200 p-4 text-sm text-slate-400">No vendor currently stocks this item.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach ($row['offers'] as $offer)
                                        <label class="flex cursor-pointer items-center justify-between rounded-xl border p-4 transition {{ (int) $row['selected_seller_id'] === (int) $offer->seller_id ? 'border-emerald-600 ring-1 ring-emerald-600' : 'border-slate-200 hover:border-slate-300' }}">
                                            <div class="flex items-center gap-3">
                                                <input
                                                    type="radio"
                                                    name="vendor[{{ $row['cart_item']->product_id }}]"
                                                    value="{{ $offer->seller_id }}"
                                                    class="h-4 w-4 accent-emerald-600"
                                                    {{ (int) $row['selected_seller_id'] === (int) $offer->seller_id ? 'checked' : '' }}
                                                >

                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="font-medium text-slate-800">{{ $offer->shop_name }}</span>

                                                        @if ($loop->first && $sort === 'recommended')
                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Recommended</span>
                                                        @endif
                                                    </div>

                                                    <p class="mt-0.5 text-xs text-slate-500">
                                                        {{ $offer->city ?? 'Location not set' }}

                                                        @if (isset($offer->distance_km))
                                                            &middot; {{ number_format($offer->distance_km, 1) }} km away
                                                        @endif

                                                        &middot; {{ $offer->quantity }} in stock
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <p class="font-semibold text-slate-800">{{ core()->formatPrice($offer->price) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <button
                        type="submit"
                        {{ $allSelectable ? '' : 'disabled' }}
                        class="w-full rounded-xl bg-emerald-700 py-3 font-semibold text-white transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:bg-slate-300"
                    >
                        Continue to Payment
                    </button>
                </form>
            </div>

            {{-- Order summary --}}
            <aside class="h-fit rounded-xl border border-slate-200 p-5">
                <h3 class="mb-3 font-semibold text-slate-800">Order Summary</h3>

                <ul class="space-y-2 text-sm">
                    @foreach ($products as $row)
                        <li class="flex justify-between text-slate-600">
                            <span>{{ $row['cart_item']->name }} &times; {{ $row['cart_item']->quantity }}</span>
                            <span>{{ core()->formatPrice($row['cart_item']->price * $row['cart_item']->quantity) }}</span>
                        </li>
                    @endforeach
                </ul>
            </aside>
        </div>
    </div>

    <script>
        document.getElementById('use-my-location')?.addEventListener('click', function () {
            if (! navigator.geolocation) {
                return;
            }

            navigator.geolocation.getCurrentPosition(function (position) {
                const url = new URL(window.location.href);
                url.searchParams.set('lat', position.coords.latitude);
                url.searchParams.set('lng', position.coords.longitude);
                window.location.href = url.toString();
            });
        });
    </script>
</x-shop::layouts>
