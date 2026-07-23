@push('meta')
    <meta name="title" content="Choose Your Vendor" />
@endpush

<x-shop::layouts>
    <x-slot:title>Choose Your Vendor</x-slot>

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
                <span class="font-medium">Vendor</span>
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

                {{-- Location --}}
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-4">
                    <div class="text-sm">
                        @if ($hasLocation)
                            <span class="font-medium text-slate-700">Showing vendors near your current location</span>
                        @else
                            <span class="text-slate-500">Share your location to see the nearest complete vendors and accurate delivery estimates.</span>
                        @endif
                    </div>

                    <button
                        type="button"
                        id="use-my-location"
                        class="rounded-lg border border-brandGreen px-3 py-1.5 text-xs font-semibold text-brandNavy hover:bg-brandGreen/5"
                    >
                        Use my location
                    </button>
                </div>

                @if ($eligibleVendors->isEmpty())
                    {{-- No single vendor can fulfil the complete cart --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>

                            <div>
                                <p class="font-semibold text-amber-900">
                                    No single vendor currently has all the products and quantities in your cart.
                                </p>
                                <p class="mt-1 text-sm text-amber-800">
                                    You cannot complete this order until one vendor can fulfil the entire cart. Please adjust your cart, change your delivery location, or check again later.
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="{{ route('shop.checkout.cart.index') }}" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100">
                                Adjust Cart
                            </a>

                            <button type="button" id="use-my-location-2" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100">
                                Change Delivery Location
                            </button>

                            <button type="button" onclick="window.location.reload()" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100">
                                Try Again Later
                            </button>

                            <a href="{{ route('shop.home.index') }}" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-amber-900 ring-1 ring-amber-300 hover:bg-amber-100">
                                Return to Shopping
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mb-4 flex items-center gap-2 rounded-xl border border-brandGreen/30 bg-brandGreen/10 p-4 text-sm font-medium text-brandNavy">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        Only vendors with every item and quantity in your cart are shown below.
                    </div>

                    <p class="mb-1 text-lg font-semibold text-brandNavy">Choose a Vendor for Your Whole Order</p>
                    <p class="mb-4 text-xs text-slate-400">One vendor fulfils your entire order - not split across stores.</p>

                    <form method="POST" action="{{ route('marketplace.checkout.vendor.store') }}" id="vendor-form">
                        @csrf

                        <div class="space-y-3">
                            @foreach ($eligibleVendors as $row)
                                <label
                                    class="vendor-option-label flex cursor-pointer flex-col gap-3 rounded-xl border p-4 transition sm:flex-row sm:items-center sm:justify-between {{ (int) $selectedSellerId === $row->seller->id ? 'border-brandGreen bg-brandGreen/5 ring-1 ring-brandGreen' : 'border-slate-200 hover:border-slate-300' }}"
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            name="seller_id"
                                            value="{{ $row->seller->id }}"
                                            class="mt-1 h-4 w-4 accent-brandGreen vendor-radio"
                                            data-cart-total="{{ core()->convertPrice($row->cart_total) }}"
                                            data-delivery-fee="{{ core()->convertPrice($row->delivery_fee) }}"
                                            data-shop-name="{{ $row->seller->shop_name }}"
                                            onchange="marketplaceRecalculate()"
                                            {{ (int) $selectedSellerId === $row->seller->id ? 'checked' : '' }}
                                        >

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
                                                </span>

                                                <span class="font-medium text-slate-800">{{ $row->seller->shop_name }}</span>

                                                <span class="rounded-full bg-brandGreen/10 px-2 py-0.5 text-[11px] font-semibold text-brandGreen">Complete cart available</span>

                                                @if ($loop->first)
                                                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Recommended</span>
                                                @endif
                                            </div>

                                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                @if ($row->distance_km !== null)
                                                    <span>{{ number_format($row->distance_km, 1) }} km away</span>
                                                @endif

                                                <span class="flex items-center gap-1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9" /><path stroke-linecap="round" d="M12 7v5l3 3" /></svg>
                                                    {{ $row->eta_label }}
                                                </span>

                                                <span class="flex items-center gap-1 text-amber-500">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01z" /></svg>
                                                    <span class="text-slate-600">{{ number_format((float) $row->seller->rating, 1) }}</span>
                                                </span>

                                                <span>Delivery {{ core()->currency($row->delivery_fee) }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="shrink-0 text-right sm:pl-3">
                                        <p class="font-semibold text-slate-800">{{ core()->currency($row->cart_total) }}</p>
                                        <p class="text-[11px] text-slate-400">Full cart total</p>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </form>
                @endif
            </div>

            {{-- Order summary --}}
            <aside class="h-fit min-w-0 rounded-xl border border-slate-200 p-5 lg:sticky lg:top-24">
                <h3 class="mb-3 font-semibold text-slate-800">Order Summary</h3>

                <ul class="max-h-64 space-y-3 overflow-y-auto text-sm">
                    @foreach ($lines as $line)
                        <li class="flex items-center gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-slate-700">{{ $line->name }} &times; {{ $line->quantity }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                @if ($eligibleVendors->isNotEmpty())
                    <div class="mt-4 space-y-1.5 border-t border-slate-100 pt-4 text-sm">
                        <div class="flex justify-between text-slate-600">
                            <span>Vendor</span>
                            <span id="summary-vendor">{{ $eligibleVendors->firstWhere('seller.id', (int) $selectedSellerId)?->seller->shop_name }}</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Cart Subtotal</span>
                            <span id="summary-subtotal">{{ core()->currency($eligibleVendors->firstWhere('seller.id', (int) $selectedSellerId)?->cart_total ?? 0) }}</span>
                        </div>

                        <div class="flex justify-between text-slate-600">
                            <span>Delivery Fee</span>
                            <span id="summary-delivery-fee">{{ core()->currency($eligibleVendors->firstWhere('seller.id', (int) $selectedSellerId)?->delivery_fee ?? 0) }}</span>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-3">
                        <span class="font-semibold text-slate-800">Total</span>
                        <span class="text-lg font-bold text-brandGreen" id="summary-total">
                            {{ core()->currency(
                                ($eligibleVendors->firstWhere('seller.id', (int) $selectedSellerId)?->cart_total ?? 0)
                                + ($eligibleVendors->firstWhere('seller.id', (int) $selectedSellerId)?->delivery_fee ?? 0)
                            ) }}
                        </span>
                    </div>

                    <p class="mt-1 text-[11px] text-slate-400">Final total is confirmed securely at payment.</p>

                    <button
                        type="submit"
                        form="vendor-form"
                        class="mt-4 w-full rounded-xl bg-brandGreen py-3 font-semibold text-white transition hover:opacity-90"
                    >
                        Continue with This Vendor
                    </button>
                @else
                    <p class="mt-4 text-sm text-slate-500">Adjust your cart or check back later to see available vendors.</p>
                @endif

                <p class="mt-3 flex items-center gap-1.5 text-[11px] text-slate-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
                    Secure checkout. Your payment information is encrypted and safe.
                </p>
            </aside>
        </div>
    </div>

    <script>
        // Delegated on document, not attached directly to the button: this
        // page's Vue-driven shell re-renders shortly after mount, which can
        // replace the button's DOM node and silently drop a directly
        // attached listener. A delegated listener on a stable ancestor
        // survives that.
        document.addEventListener('click', function (event) {
            if (! event.target.closest('#use-my-location') && ! event.target.closest('#use-my-location-2')) {
                return;
            }

            if (! navigator.geolocation) {
                return;
            }

            const button = event.target.closest('#use-my-location') || event.target.closest('#use-my-location-2');
            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Detecting your location...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('lat', position.coords.latitude);
                    url.searchParams.set('lng', position.coords.longitude);
                    window.location.href = url.toString();
                },
                function () {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            );
        });

        // Live-recalculate the order summary as the customer picks a vendor.
        (function () {
            const currencySymbol = "{{ core()->getCurrentCurrency()->symbol ?? '' }}";

            function formatPrice(amount) {
                return currencySymbol + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalculate() {
                const checked = document.querySelector('.vendor-radio:checked');

                if (! checked) return;

                const cartTotal = parseFloat(checked.dataset.cartTotal);
                const deliveryFee = parseFloat(checked.dataset.deliveryFee);

                document.getElementById('summary-vendor').textContent = checked.dataset.shopName;
                document.getElementById('summary-subtotal').textContent = formatPrice(cartTotal);
                document.getElementById('summary-delivery-fee').textContent = formatPrice(deliveryFee);
                document.getElementById('summary-total').textContent = formatPrice(cartTotal + deliveryFee);

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
    </script>
</x-shop::layouts>
