@push('meta')
    <meta name="title" content="Delivery Service" />
@endpush

<x-shop::layouts>
    <x-slot:title>Delivery Service</x-slot>

    <div class="mx-auto max-w-[1200px] px-4 py-8">
        {{-- Stepper --}}
        <div class="mb-8 flex items-center justify-center gap-2 text-xs sm:gap-4 sm:text-sm">
            <div class="flex items-center gap-2 text-brandNavy">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-brandGreen bg-brandGreen text-white">&#10003;</span>
                <span class="font-medium">Cart</span>
            </div>
            <div class="h-px w-8 bg-brandGreen sm:w-16"></div>
            <div class="flex items-center gap-2 text-brandNavy">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-brandGreen bg-brandGreen text-white">&#10003;</span>
                <span class="font-medium">Vendor</span>
            </div>
            <div class="h-px w-8 bg-brandGreen sm:w-16"></div>
            <div class="flex items-center gap-2 text-brandNavy">
                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full border-2 border-brandGreen bg-brandGreen text-white">3</span>
                <span class="font-medium">Payment</span>
            </div>
        </div>

        <div class="grid min-w-0 gap-8 lg:grid-cols-[1fr_360px]">
            <div class="min-w-0">
                <h1 class="mb-1 text-xl font-semibold text-brandNavy">Choose a Delivery Service</h1>
                <p class="mb-6 text-sm text-slate-500">Real quotes for your route from {{ $seller->shop_name }}, priced by distance and vehicle type.</p>

                <form id="delivery-form" method="POST" action="{{ route('marketplace.checkout.delivery.store') }}">
                    @csrf

                    <div class="rounded-xl border border-slate-200 p-4 sm:p-5">
                        <p class="mb-1 font-semibold text-slate-800">{{ $seller->shop_name }}</p>
                        <p class="mb-4 text-xs text-slate-500">Your whole order ships from this vendor.</p>

                        @if ($quotes->isEmpty())
                            <p class="rounded-lg bg-amber-50 p-3 text-sm text-amber-700">No delivery service can currently reach this address from this vendor.</p>
                        @else
                            <div class="grid gap-3">
                                @foreach ($quotes as $index => $entry)
                                    <label class="delivery-option-label flex cursor-pointer items-center justify-between gap-3 rounded-xl border p-4 transition {{ $index === 0 ? 'border-brandGreen bg-brandGreen/5 ring-1 ring-brandGreen' : 'border-slate-200 hover:border-slate-300' }}">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <input
                                                type="radio"
                                                name="quote_token"
                                                value="{{ $entry['record']->quote_token }}"
                                                class="delivery-radio h-4 w-4 accent-brandGreen"
                                                data-fee="{{ core()->convertPrice($entry['quote']->feeMinor / 100) }}"
                                                onchange="marketplaceRecalculateDelivery()"
                                                {{ $index === 0 ? 'checked' : '' }}
                                            >

                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                                                @php
                                                    $vehicleIcons = [
                                                        'motorcycle' => 'M5 17a2 2 0 100-4 2 2 0 000 4zm14 0a2 2 0 100-4 2 2 0 000 4zM7 17h6l3-7h3M8 10h5l2 4',
                                                        'van' => 'M3 17h1a2 2 0 002-2v-3l2-4h6l3 4h2a1 1 0 011 1v4a2 2 0 01-2 2h-1M7 17a2 2 0 100-4 2 2 0 000 4zm10 0a2 2 0 100-4 2 2 0 000 4z',
                                                        'car' => 'M5 17a2 2 0 100-4 2 2 0 000 4zm14 0a2 2 0 100-4 2 2 0 000 4zM5 15l1-5h12l1 5M6 15h12',
                                                        'bicycle' => 'M5.5 17a2.5 2.5 0 100-5 2.5 2.5 0 000 5zm13 0a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM7.5 15l3-7h4l2 4M9 8h3',
                                                    ];
                                                @endphp
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $vehicleIcons[$entry['service_type']->vehicle_type] ?? $vehicleIcons['motorcycle'] }}" /></svg>
                                            </span>

                                            <div class="min-w-0">
                                                <p class="font-medium text-slate-800">{{ $entry['service_type']->name }}</p>
                                                <p class="mt-0.5 flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-xs text-slate-500">
                                                    <span>{{ number_format($entry['quote']->distanceKm, 1) }} km</span>
                                                    <span>&middot;</span>
                                                    <span>~{{ $entry['quote']->durationMinutes }} min</span>
                                                    @if ($entry['service_type']->tracking_available)
                                                        <span>&middot;</span>
                                                        <span class="text-brandGreen">Live tracking</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <span class="shrink-0 font-semibold text-slate-800">{{ core()->currency($entry['quote']->feeMinor / 100) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </form>
            </div>

            <aside class="h-fit min-w-0 rounded-xl border border-slate-200 p-5 lg:sticky lg:top-24">
                <p class="mb-4 text-lg font-semibold text-brandNavy">Delivery Summary</p>

                <div class="flex items-center justify-between border-t border-slate-200 pt-3 text-sm">
                    <span class="font-semibold text-slate-800">Delivery fee</span>
                    <span class="text-lg font-bold text-brandGreen" id="delivery-total">
                        {{ core()->currency(($quotes->first()['quote']->feeMinor ?? 0) / 100) }}
                    </span>
                </div>

                <p class="mt-1 text-[11px] text-slate-400">Final delivery fee is confirmed securely at payment.</p>

                <button
                    type="submit"
                    form="delivery-form"
                    {{ $quotes->isNotEmpty() ? '' : 'disabled' }}
                    class="mt-4 w-full rounded-xl bg-brandGreen py-3 font-semibold text-white transition hover:opacity-90 disabled:cursor-not-allowed disabled:bg-slate-300"
                >
                    Proceed to Payment
                </button>
            </aside>
        </div>
    </div>

    <script>
        (function () {
            const currencySymbol = "{{ core()->getCurrentCurrency()->symbol ?? '' }}";

            function formatPrice(amount) {
                return currencySymbol + amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            function recalculate() {
                const checked = document.querySelector('.delivery-radio:checked');

                if (! checked) return;

                document.getElementById('delivery-total').textContent = formatPrice(parseFloat(checked.dataset.fee));

                document.querySelectorAll('.delivery-option-label').forEach(label => {
                    const radio = label.querySelector('.delivery-radio');
                    const isSelected = radio && radio.checked;

                    label.classList.toggle('border-brandGreen', isSelected);
                    label.classList.toggle('bg-brandGreen/5', isSelected);
                    label.classList.toggle('ring-1', isSelected);
                    label.classList.toggle('ring-brandGreen', isSelected);
                    label.classList.toggle('border-slate-200', ! isSelected);
                });
            }

            window.marketplaceRecalculateDelivery = recalculate;
        })();
    </script>
</x-shop::layouts>
