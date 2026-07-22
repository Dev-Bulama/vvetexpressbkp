@push('meta')
    <meta name="title" content="Delivery &amp; Vendor" />
@endpush

@php
    $vendorSelection = session('marketplace.vendor_selection', []);
@endphp

<x-shop::layouts>
    <x-slot:title>Delivery &amp; Vendor</x-slot>

    <div class="mx-auto max-w-[1200px] px-4 py-8">
        {{-- Stepper --}}
        <div class="mb-8 flex items-center justify-center gap-2 text-xs sm:gap-4 sm:text-sm">
            <div class="flex items-center gap-2 text-brandNavy">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-brandGreen bg-brandGreen text-white">&#10003;</span>
                <span class="font-medium">Cart</span>
            </div>
            <div class="h-px w-8 bg-brandGreen sm:w-16"></div>
            <div class="flex items-center gap-2 text-brandNavy">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-brandGreen bg-brandGreen text-white">2</span>
                <span class="font-medium">Delivery &amp; Vendor</span>
            </div>
            <div class="h-px w-8 bg-slate-200 sm:w-16"></div>
            <div class="flex items-center gap-2 text-slate-400">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-slate-300">3</span>
                <span>Payment</span>
            </div>
        </div>

        <div class="grid min-w-0 gap-8 lg:grid-cols-[1fr_360px]">
            <div class="min-w-0">
                {{-- Delivery Address --}}
                <div class="mb-6 rounded-xl border border-slate-200 p-4 sm:p-5">
                    <p class="mb-3 font-semibold text-slate-800">Delivery Address</p>

                    @if ($deliveryAddress)
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3">
                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brandGreen/10 text-brandGreen">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
                                </span>

                                <div class="min-w-0 flex-1 text-sm">
                                    <p class="font-medium text-slate-800">{{ $deliveryAddress->name }}</p>
                                    <p class="break-words text-slate-500">{{ $deliveryAddress->address1 }}, {{ $deliveryAddress->city }}</p>
                                    <p class="text-slate-500">{{ $deliveryAddress->phone }}</p>
                                </div>
                            </div>

                            <a
                                href="{{ route('shop.customers.account.addresses.index') }}"
                                class="shrink-0 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-brandNavy hover:text-brandNavy"
                            >
                                Change
                            </a>
                        </div>
                    @elseif ($customer)
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <p class="text-slate-500">You don't have a saved delivery address yet.</p>

                            <a
                                href="{{ route('shop.customers.account.addresses.create') }}"
                                class="shrink-0 rounded-lg bg-brandNavy px-3 py-1.5 text-xs font-semibold text-white hover:opacity-90"
                            >
                                Add Address
                            </a>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">
                            <a href="{{ route('shop.customer.session.index') }}" class="font-semibold text-brandNavy hover:underline">Sign in</a>
                            to use a saved address, or continue - you'll be asked for delivery details at the next step.
                        </p>
                    @endif
                </div>

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
                            class="rounded-lg border border-brandGreen px-3 py-1.5 text-xs font-semibold text-brandNavy hover:bg-brandGreen/5"
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

                {{-- Availability banner --}}
                @if ($allSelectable)
                    <div class="mb-6 flex items-center gap-2 rounded-xl border border-brandGreen/30 bg-brandGreen/10 p-4 text-sm font-medium text-brandNavy">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        All items in stock and ready for delivery.
                    </div>
                @else
                    <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Some items are unavailable from any vendor near you right now. Please update your cart before continuing.
                    </div>
                @endif

                <p class="mb-4 text-lg font-semibold text-brandNavy">Select a Nearest Vendor</p>
                <p class="-mt-3 mb-4 text-xs text-slate-400">Recommended vendors that have your items in stock</p>

                <form method="POST" action="{{ route('marketplace.checkout.vendor.store') }}" id="vendor-form">
                    @csrf

                    @foreach ($products as $row)
                        <div class="mb-8">
                            <h3 class="mb-3 flex items-center gap-2 font-semibold text-slate-800">
                                {{ $row['cart_item']->name }}
                                <span class="font-normal text-slate-400">&times; {{ $row['cart_item']->quantity }}</span>
                            </h3>

                            @if ($row['offers']->isEmpty())
                                <p class="rounded-lg border border-slate-200 p-4 text-sm text-slate-400">No vendor currently stocks this item.</p>
                            @else
                                <div class="space-y-3">
                                    @foreach ($row['offers'] as $offer)
                                        <label
                                            class="vendor-option-label flex cursor-pointer flex-col gap-3 rounded-xl border p-4 transition sm:flex-row sm:items-center sm:justify-between {{ (int) $row['selected_seller_id'] === (int) $offer->seller_id ? 'border-brandGreen bg-brandGreen/5 ring-1 ring-brandGreen' : 'border-slate-200 hover:border-slate-300' }}"
                                            data-product-id="{{ $row['cart_item']->product_id }}"
                                        >
                                            <div class="flex items-start gap-3">
                                                <input
                                                    type="radio"
                                                    name="vendor[{{ $row['cart_item']->product_id }}]"
                                                    value="{{ $offer->seller_id }}"
                                                    class="mt-1 h-4 w-4 accent-brandGreen vendor-radio"
                                                    data-product-id="{{ $row['cart_item']->product_id }}"
                                                    data-price="{{ (float) $offer->price }}"
                                                    data-delivery-fee="{{ (float) $offer->delivery_fee }}"
                                                    data-shop-name="{{ $offer->shop_name }}"
                                                    onchange="marketplaceRecalculate()"
                                                    {{ (int) $row['selected_seller_id'] === (int) $offer->seller_id ? 'checked' : '' }}
                                                >

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
                                                        </span>

                                                        <span class="font-medium text-slate-800">{{ $offer->shop_name }}</span>

                                                        @if ($offer->is_nearest ?? false)
                                                            <span class="rounded-full bg-brandGreen/10 px-2 py-0.5 text-[11px] font-semibold text-brandGreen">Nearest</span>
                                                        @endif

                                                        @if (($offer->is_fastest ?? false) && ! ($offer->is_nearest ?? false))
                                                            <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[11px] font-semibold text-blue-600">Fastest</span>
                                                        @endif

                                                        @if (($offer->is_lowest_fee ?? false) && ! ($offer->is_nearest ?? false))
                                                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Lowest Fee</span>
                                                        @endif
                                                    </div>

                                                    <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                        @if (isset($offer->distance_km))
                                                            <span>{{ number_format($offer->distance_km, 1) }} km away</span>
                                                        @endif

                                                        <span class="flex items-center gap-1">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 3" /></svg>
                                                            {{ $offer->eta_label }}
                                                        </span>

                                                        <span class="flex items-center gap-1 text-amber-500">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01z" /></svg>
                                                            <span class="text-slate-600">{{ number_format((float) $offer->rating, 1) }}</span>
                                                        </span>

                                                        <span>Delivery {{ core()->formatPrice($offer->delivery_fee) }}</span>

                                                        <span class="font-medium text-brandGreen">{{ $offer->quantity }} in stock</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="shrink-0 text-right sm:pl-3">
                                                <p class="font-semibold text-slate-800">{{ core()->formatPrice($offer->price) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </form>
            </div>

            {{-- Order summary --}}
            <aside class="h-fit min-w-0 rounded-xl border border-slate-200 p-5 lg:sticky lg:top-24">
                <h3 class="mb-3 font-semibold text-slate-800">Order Summary</h3>

                <ul class="max-h-64 space-y-3 overflow-y-auto text-sm" id="order-summary-items">
                    @foreach ($products as $row)
                        @php
                            $product = $row['cart_item']->product;
                            $selectedOffer = $row['offers']->firstWhere('seller_id', (int) $row['selected_seller_id']);
                            $lineTotal = ($selectedOffer->price ?? $row['cart_item']->price) * $row['cart_item']->quantity;
                        @endphp

                        <li class="flex items-center gap-3" data-summary-row data-product-id="{{ $row['cart_item']->product_id }}" data-quantity="{{ $row['cart_item']->quantity }}">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-50 text-slate-300">
                                @if ($product?->base_image_url)
                                    <img src="{{ $product->base_image_url }}" alt="{{ $row['cart_item']->name }}" class="h-full w-full object-cover">
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4zm4 4h8v8H8V8z" /></svg>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-slate-700">{{ $row['cart_item']->name }} &times; {{ $row['cart_item']->quantity }}</p>
                                <p class="truncate text-xs text-slate-400" data-summary-vendor>{{ $selectedOffer->shop_name ?? '' }}</p>
                            </div>

                            <span class="shrink-0 font-medium text-slate-700" data-summary-line-total>{{ core()->formatPrice($lineTotal) }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="mt-4 border-t border-slate-100 pt-4">
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            id="promo-code-input"
                            placeholder="Enter promo code"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none"
                        >
                        <button
                            type="button"
                            id="promo-apply-btn"
                            onclick="marketplacePromoApply()"
                            class="shrink-0 rounded-lg bg-brandNavy px-3 py-2 text-sm font-semibold text-white hover:opacity-90"
                        >
                            Apply
                        </button>
                    </div>
                    <p id="promo-message" class="mt-1.5 hidden text-xs"></p>
                </div>

                <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal</span>
                        <span id="summary-subtotal">{{ core()->formatPrice(collect($products)->sum(fn ($row) => ($row['offers']->firstWhere('seller_id', (int) $row['selected_seller_id'])->price ?? $row['cart_item']->price) * $row['cart_item']->quantity)) }}</span>
                    </div>

                    <div class="flex justify-between text-slate-600">
                        <span>Delivery Fee</span>
                        <span id="summary-delivery-fee">{{ core()->formatPrice(collect($products)->map(fn ($row) => (int) $row['selected_seller_id'])->unique()->map(fn ($sellerId) => collect($products)->flatMap(fn ($row) => $row['offers'])->firstWhere('seller_id', $sellerId)?->delivery_fee ?? 0)->sum()) }}</span>
                    </div>

                    @if ($cart->discount_amount > 0)
                        <div class="flex justify-between text-brandGreen">
                            <span>Discount</span>
                            <span>&minus; {{ core()->formatPrice($cart->discount_amount) }}</span>
                        </div>
                    @endif

                    @if ($cart->tax_total > 0)
                        <div class="flex justify-between text-slate-600">
                            <span>Tax</span>
                            <span>{{ core()->formatPrice($cart->tax_total) }}</span>
                        </div>
                    @endif
                </div>

                <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                    <span class="font-semibold text-slate-800">Total</span>
                    <span class="text-lg font-bold text-brandGreen" id="summary-total">
                        {{ core()->formatPrice(
                            collect($products)->sum(fn ($row) => ($row['offers']->firstWhere('seller_id', (int) $row['selected_seller_id'])->price ?? $row['cart_item']->price) * $row['cart_item']->quantity)
                            + collect($products)->map(fn ($row) => (int) $row['selected_seller_id'])->unique()->map(fn ($sellerId) => collect($products)->flatMap(fn ($row) => $row['offers'])->firstWhere('seller_id', $sellerId)?->delivery_fee ?? 0)->sum()
                            - $cart->discount_amount
                            + $cart->tax_total
                        ) }}
                    </span>
                </div>

                <p class="mt-1 text-[11px] text-slate-400">Final total is confirmed securely at payment.</p>

                <button
                    type="submit"
                    form="vendor-form"
                    {{ $allSelectable ? '' : 'disabled' }}
                    class="mt-4 w-full rounded-xl bg-brandGreen py-3 font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    Proceed to Payment
                </button>

                <p class="mt-3 flex items-center gap-1.5 text-[11px] text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
                    Secure checkout. Your payment information is encrypted and safe.
                </p>
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

        // Live-recalculate the order summary as the customer picks vendors.
        (function () {
            const currencySymbol = "{{ core()->getCurrentCurrency()->symbol ?? '' }}";

            function formatPrice(amount) {
                return currencySymbol + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            const staticDiscount = {{ (float) $cart->discount_amount }};
            const staticTax = {{ (float) $cart->tax_total }};

            function recalculate() {
                let subtotal = 0;
                const sellerFees = {};

                document.querySelectorAll('[data-summary-row]').forEach(row => {
                    const productId = row.dataset.productId;
                    const quantity = parseFloat(row.dataset.quantity);
                    const checked = document.querySelector(`.vendor-radio[data-product-id="${productId}"]:checked`);

                    if (! checked) return;

                    const price = parseFloat(checked.dataset.price);
                    const lineTotal = price * quantity;
                    subtotal += lineTotal;

                    sellerFees[checked.value] = parseFloat(checked.dataset.deliveryFee);

                    row.querySelector('[data-summary-line-total]').textContent = formatPrice(lineTotal);
                    row.querySelector('[data-summary-vendor]').textContent = checked.dataset.shopName;
                });

                const deliveryFee = Object.values(sellerFees).reduce((sum, fee) => sum + fee, 0);
                const total = subtotal + deliveryFee - staticDiscount + staticTax;

                document.getElementById('summary-subtotal').textContent = formatPrice(subtotal);
                document.getElementById('summary-delivery-fee').textContent = formatPrice(deliveryFee);
                document.getElementById('summary-total').textContent = formatPrice(total);

                document.querySelectorAll('.vendor-option-label').forEach(label => {
                    const radio = label.querySelector('.vendor-radio');
                    const isSelected = radio && radio.checked;

                    label.classList.toggle('border-brandGreen', isSelected);
                    label.classList.toggle('bg-brandGreen/5', isSelected);
                    label.classList.toggle('ring-1', isSelected);
                    label.classList.toggle('ring-brandGreen', isSelected);
                    label.classList.toggle('border-slate-200', ! isSelected);
                });
            }

            window.marketplaceRecalculate = recalculate;
        })();

        // Promo code - reuses the real cart coupon API.
        (function () {
            function apply() {
                const input = document.getElementById('promo-code-input');
                const button = document.getElementById('promo-apply-btn');
                const message = document.getElementById('promo-message');

                if (! input || ! button || ! message || ! window.axios) return;

                const code = input.value.trim();

                if (! code) return;

                button.disabled = true;
                button.textContent = 'Applying...';

                window.axios.post('{{ route('shop.api.checkout.cart.coupon.apply') }}', { code })
                    .then(response => {
                        message.textContent = response.data.message || 'Coupon applied.';
                        message.className = 'mt-1.5 text-xs text-brandGreen';
                        message.classList.remove('hidden');

                        setTimeout(() => window.location.reload(), 1200);
                    })
                    .catch(error => {
                        message.textContent = error.response?.data?.message || 'This coupon code is invalid.';
                        message.className = 'mt-1.5 text-xs text-rose-600';
                        message.classList.remove('hidden');

                        button.disabled = false;
                        button.textContent = 'Apply';
                    });
            }

            window.marketplacePromoApply = apply;
        })();
    </script>
</x-shop::layouts>
