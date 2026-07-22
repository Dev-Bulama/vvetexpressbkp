@php
    $customer = auth()->guard('customer')->user();
    $savedAddresses = $customer?->addresses()->whereNotNull('latitude')->whereNotNull('longitude')->get();
@endphp

<div id="marketplace-location-overlay" class="fixed inset-0 z-[1000] hidden items-center justify-center bg-black/40 p-4" role="dialog" aria-modal="true" aria-labelledby="marketplace-location-title">
    <div class="w-full max-w-[460px] rounded-2xl bg-white p-6 shadow-xl max-sm:p-4">
        <div class="flex items-center justify-between">
            <h2 id="marketplace-location-title" class="text-lg font-semibold text-brandNavy">Choose delivery location</h2>

            <button type="button" onclick="marketplaceCloseLocationModal()" aria-label="Close" class="text-2xl leading-none text-slate-400 hover:text-slate-600">&times;</button>
        </div>

        <button
            type="button"
            id="marketplace-use-current-location"
            onclick="marketplaceUseCurrentLocation()"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-brandGreen px-4 py-3 text-sm font-semibold text-brandNavy hover:bg-brandGreen/5"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
            Use my current location
        </button>

        <p id="marketplace-location-error" class="mt-2 hidden text-xs text-rose-600"></p>

        @if ($savedAddresses && $savedAddresses->isNotEmpty())
            <div class="mt-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Saved addresses</p>

                <div class="grid gap-2">
                    @foreach ($savedAddresses as $address)
                        <button
                            type="button"
                            class="flex items-start gap-2 rounded-lg border border-slate-200 p-3 text-left text-sm hover:border-brandGreen"
                            onclick="marketplaceUseSavedAddress({{ $address->id }}, {{ (float) $address->latitude }}, {{ (float) $address->longitude }}, {{ \Illuminate\Support\Js::from($address->first_name.' - '.$address->city) }}, {{ \Illuminate\Support\Js::from($address->address) }}, {{ \Illuminate\Support\Js::from($address->city) }}, {{ \Illuminate\Support\Js::from((string) $address->state) }})"
                        >
                            <span class="mt-0.5 text-brandGreen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>
                            </span>
                            <span>
                                <span class="block font-medium text-brandNavy">{{ $address->first_name }} {{ $address->last_name }}</span>
                                <span class="block text-slate-500">{{ $address->address }}, {{ $address->city }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="mt-4 border-t border-slate-100 pt-4">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Or enter manually</p>

            <form id="marketplace-location-form" class="grid gap-2.5" onsubmit="event.preventDefault(); marketplaceSubmitLocation();">
                <input type="text" id="loc-label" placeholder="Label (e.g. Home, Office)" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none">

                <input type="text" id="loc-address" placeholder="Street address" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none">

                <div class="grid grid-cols-2 gap-2.5">
                    <input type="text" id="loc-city" placeholder="City" required class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none">
                    <input type="text" id="loc-state" placeholder="State" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none">
                </div>

                <input type="text" id="loc-landmark" placeholder="Nearby landmark (optional)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none">

                <textarea id="loc-instructions" placeholder="Delivery instructions (optional)" rows="2" class="rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-brandGreen focus:outline-none"></textarea>

                @auth('customer')
                    <label class="flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" id="loc-save-address" class="rounded border-slate-300 text-brandGreen focus:ring-brandGreen">
                        Save this address to my account
                    </label>
                @endauth

                <button type="submit" class="mt-1 w-full rounded-xl bg-brandGreen py-3 text-sm font-semibold text-white hover:opacity-90">
                    Confirm delivery location
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        let pendingCoords = null;

        window.marketplaceOpenLocationModal = function () {
            document.getElementById('marketplace-location-overlay').classList.remove('hidden');
            document.getElementById('marketplace-location-overlay').classList.add('flex');
        };

        window.marketplaceCloseLocationModal = function () {
            document.getElementById('marketplace-location-overlay').classList.add('hidden');
            document.getElementById('marketplace-location-overlay').classList.remove('flex');
        };

        window.marketplaceUseCurrentLocation = function () {
            const errorEl = document.getElementById('marketplace-location-error');
            errorEl.classList.add('hidden');

            if (! navigator.geolocation) {
                errorEl.textContent = 'Your browser does not support location detection. Please enter your address manually below.';
                errorEl.classList.remove('hidden');
                return;
            }

            const button = document.getElementById('marketplace-use-current-location');
            button.disabled = true;
            button.textContent = 'Detecting your location...';

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    pendingCoords = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    button.disabled = false;
                    button.textContent = 'Location detected - confirm address below';

                    document.getElementById('loc-label').focus();
                },
                function (error) {
                    button.disabled = false;
                    button.textContent = 'Use my current location';

                    const messages = {
                        1: 'Location permission denied. Please enter your address manually below.',
                        2: 'Your location is currently unavailable. Please enter your address manually below.',
                        3: 'Location request timed out. Please enter your address manually below.',
                    };

                    errorEl.textContent = messages[error.code] || 'Could not detect your location. Please enter your address manually below.';
                    errorEl.classList.remove('hidden');
                },
                { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
            );
        };

        function updateLabels(label) {
            const utility = document.getElementById('marketplace-location-label-utility');
            const mobile = document.getElementById('marketplace-location-label-mobile');

            if (utility) utility.innerHTML = 'Deliver to: <strong class="font-semibold">' + label + '</strong>';
            if (mobile) mobile.innerHTML = 'Deliver to: <strong class="font-semibold text-brandNavy">' + label + '</strong>';
        }

        function postLocation(payload) {
            return window.axios.post('{{ route('marketplace.location.store') }}', payload)
                .then(function (response) {
                    updateLabels(payload.label);
                    window.marketplaceCloseLocationModal();
                    window.location.reload();
                });
        }

        window.marketplaceSubmitLocation = function () {
            const payload = {
                label: document.getElementById('loc-label').value,
                address: document.getElementById('loc-address').value,
                city: document.getElementById('loc-city').value,
                state: document.getElementById('loc-state').value,
                landmark: document.getElementById('loc-landmark').value,
                delivery_instructions: document.getElementById('loc-instructions').value,
            };

            if (pendingCoords) {
                payload.latitude = pendingCoords.lat;
                payload.longitude = pendingCoords.lng;
            }

            const saveCheckbox = document.getElementById('loc-save-address');
            if (saveCheckbox && saveCheckbox.checked) {
                payload.save_address = 1;
            }

            postLocation(payload);
        };

        window.marketplaceUseSavedAddress = function (id, lat, lng, label, address, city, state) {
            postLocation({
                label: label,
                address: address,
                city: city,
                state: state,
                latitude: lat,
                longitude: lng,
            });
        };
    })();
</script>
