<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Track Delivery #{{ $delivery->id }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Pulls in the shop's Echo/axios plugins (packages/Webkul/Shop/src/Resources/assets/js/plugins/echo.js)
         so window.Echo exists here for every guard, not just customer-facing shop pages. This page renders
         no Vue components, so app.js loading without an #app element to mount into is harmless. --}}
    @bagistoVite(['src/Resources/assets/css/app.css', 'src/Resources/assets/js/app.js'])

    <style>
        :root { --navy: #11455B; --green: #2FCB6E; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; background: #F7FAF9; margin: 0; color: #102A43; }
        header { background: var(--navy); color: #fff; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        header a.back { color: rgba(255,255,255,.85); text-decoration: none; font-size: 13px; }
        header a.back:hover { color: #fff; }
        header .order-id { font-weight: 600; }
        main { max-width: 1100px; margin: 0 auto; padding: 20px 16px 60px; display: grid; gap: 20px; grid-template-columns: 1fr; }
        @media (min-width: 900px) { main { grid-template-columns: 1.3fr 1fr; align-items: start; } }
        .card { background: #fff; border: 1px solid #E2E8ED; border-radius: 14px; padding: 20px; }
        .card + .card { margin-top: 20px; }
        h2 { font-size: 14px; margin: 0 0 14px; color: var(--navy); text-transform: uppercase; letter-spacing: .04em; }
        .status-pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-weight: 600; font-size: 13px; background: #E7F9EF; color: #079447; }
        .status-pill.terminal-failed { background: #FDECEC; color: #C0392B; }
        .status-pill.terminal-cancelled { background: #F1F5F9; color: #64748B; }
        #map-area { height: 320px; border-radius: 12px; background: #EDF2F5; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative; }
        #map-canvas { width: 100%; height: 100%; }
        .map-disabled { text-align: center; color: #5C6B7A; font-size: 13px; padding: 0 24px; }
        .map-disabled strong { display: block; color: var(--navy); font-size: 15px; margin-bottom: 6px; }
        .recenter-btn { position: absolute; bottom: 12px; right: 12px; background: #fff; border: 1px solid #DFE6EA; border-radius: 8px; padding: 8px 12px; font-size: 12px; font-weight: 600; color: var(--navy); cursor: pointer; box-shadow: 0 2px 6px rgba(0,0,0,.08); }
        .meta-row { display: flex; justify-content: space-between; font-size: 14px; padding: 7px 0; border-bottom: 1px solid #F1F5F9; }
        .meta-row:last-child { border-bottom: none; }
        .meta-row span:first-child { color: #5C6B7A; }
        .meta-row span:last-child { font-weight: 600; text-align: right; }
        .timeline { list-style: none; margin: 0; padding: 0; }
        .timeline li { position: relative; padding: 0 0 18px 26px; font-size: 13px; }
        .timeline li:last-child { padding-bottom: 0; }
        .timeline li::before { content: ''; position: absolute; left: 4px; top: 3px; width: 10px; height: 10px; border-radius: 50%; background: var(--green); }
        .timeline li::after { content: ''; position: absolute; left: 8px; top: 13px; bottom: 0; width: 2px; background: #E2E8ED; }
        .timeline li:last-child::after { display: none; }
        .timeline .label { font-weight: 600; color: #102A43; }
        .timeline .time { color: #94A3B8; font-size: 11px; margin-top: 2px; }
        .verification-code { font-family: ui-monospace, monospace; font-size: 18px; letter-spacing: .1em; font-weight: 700; color: var(--navy); background: #F7FAF9; border-radius: 8px; padding: 8px 12px; display: inline-block; }
        .muted { color: #94A3B8; font-size: 12px; margin-top: 4px; }
        .live-note { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #5C6B7A; margin-top: 10px; }
        .live-dot { width: 8px; height: 8px; border-radius: 50%; background: #CBD5E1; }
        .live-dot.on { background: var(--green); box-shadow: 0 0 0 3px rgba(47,203,110,.2); }
    </style>
</head>
<body>
    <header>
        <a class="back" href="{{
            match($viewerRole) {
                'seller' => route('marketplace.seller.dashboard.index'),
                'agent' => route('marketplace.agent.dashboard.index'),
                'admin' => route('admin.dashboard.index'),
                default => route('shop.customers.account.orders.index'),
            }
        }}">&larr; Back</a>

        <span class="order-id">Order #{{ $delivery->order->increment_id ?? $delivery->order_id }}</span>

        <span class="status-pill {{ in_array($delivery->status, ['failed']) ? 'terminal-failed' : (in_array($delivery->status, ['cancelled']) ? 'terminal-cancelled' : '') }}" id="status-pill">
            {{ str_replace('_', ' ', ucfirst($delivery->status)) }}
        </span>
    </header>

    <main>
        <div>
            <div class="card">
                <h2>Live Location</h2>

                <div id="map-area">
                    @if ($mapsApiKey)
                        <div id="map-canvas"></div>
                        <button type="button" class="recenter-btn" id="recenter-btn" style="display:none;">Recenter</button>
                    @else
                        <div class="map-disabled">
                            <strong>Live map is not configured</strong>
                            Add a GOOGLE_MAPS_API_KEY to enable the live delivery map. Status updates below are still real-time.
                        </div>
                    @endif
                </div>

                <div class="live-note">
                    <span class="live-dot" id="live-dot"></span>
                    <span id="live-note-text">Connecting to live updates&hellip;</span>
                </div>
            </div>

            <div class="card">
                <h2>Delivery Progress</h2>

                <ul class="timeline" id="timeline-list">
                    @forelse ($delivery->statusHistory->sortBy('created_at') as $event)
                        <li>
                            <div class="label">{{ str_replace('_', ' ', ucfirst($event->to_status)) }}</div>
                            <div class="time">{{ $event->created_at->format('M j, g:i A') }}</div>
                        </li>
                    @empty
                        <li>
                            <div class="label">{{ str_replace('_', ' ', ucfirst($delivery->status)) }}</div>
                            <div class="time">{{ $delivery->created_at->format('M j, g:i A') }}</div>
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div>
            <div class="card">
                <h2>Delivery Details</h2>

                <div class="meta-row"><span>Vendor</span><span>{{ $delivery->seller->shop_name }}</span></div>
                <div class="meta-row"><span>Service</span><span>{{ $delivery->serviceType->name ?? '-' }}</span></div>
                <div class="meta-row"><span>Provider</span><span>{{ $delivery->serviceType->provider->name ?? '-' }}</span></div>

                @if ($delivery->distance_km)
                    <div class="meta-row"><span>Distance</span><span>{{ number_format($delivery->distance_km, 1) }} km</span></div>
                @endif

                @if ($delivery->duration_minutes_estimate)
                    <div class="meta-row"><span>Estimated time</span><span>~{{ $delivery->duration_minutes_estimate }} min</span></div>
                @endif

                @if ($delivery->fee_minor !== null)
                    <div class="meta-row"><span>Delivery fee</span><span>{{ core()->formatPrice($delivery->fee_minor / 100) }}</span></div>
                @endif
            </div>

            <div class="card">
                <h2>Delivery Agent</h2>

                @if ($delivery->agent)
                    <div class="meta-row"><span>Name</span><span>{{ $delivery->agent->name }}</span></div>

                    @if ($delivery->agent->phone)
                        <div class="meta-row">
                            <span>Phone</span>
                            <span>
                                @php
                                    $phone = $delivery->agent->phone;
                                    $maskedPhone = strlen($phone) > 4
                                        ? str_repeat('•', strlen($phone) - 4).substr($phone, -4)
                                        : $phone;
                                @endphp
                                {{ $maskedPhone }}
                            </span>
                        </div>
                    @endif

                    @if ($delivery->agent->vehicle)
                        <div class="meta-row"><span>Vehicle</span><span>{{ ucfirst($delivery->agent->vehicle->type) }} &middot; {{ $delivery->agent->vehicle->plate_number }}</span></div>
                    @endif
                @else
                    <p class="muted">No agent assigned yet.</p>
                @endif
            </div>

            @if ($showPickupCode || $showDropoffCode)
                <div class="card">
                    <h2>Verification Codes</h2>

                    @if ($showPickupCode)
                        <p class="muted" style="margin-top:0;">Pickup code (share with the agent at the vendor)</p>
                        <span class="verification-code">{{ $delivery->pickup_verification_code }}</span>
                    @endif

                    @if ($showPickupCode && $showDropoffCode)
                        <div style="height:16px;"></div>
                    @endif

                    @if ($showDropoffCode)
                        <p class="muted" style="margin-top:0;">Dropoff code (share with the agent on arrival)</p>
                        <span class="verification-code">{{ $delivery->dropoff_verification_code }}</span>
                    @endif
                </div>
            @endif
        </div>
    </main>

    @if ($mapsApiKey)
        <script>
            window.__trackingMapsReady = function () {
                window.dispatchEvent(new Event('marketplace:maps-ready'));
            };
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsApiKey }}&callback=__trackingMapsReady&region={{ config('services.google_maps.region') }}&language={{ config('services.google_maps.language') }}"></script>
    @endif

    <script>
        (function () {
            const deliveryId = {{ $delivery->id }};
            const mapId = {!! $mapId ? json_encode($mapId) : 'null' !!};
            const hasMapsKey = {!! $mapsApiKey ? 'true' : 'false' !!};

            const pickup = { lat: {{ (float) ($delivery->pickup_latitude ?? 0) }}, lng: {{ (float) ($delivery->pickup_longitude ?? 0) }} };
            const dropoff = { lat: {{ (float) ($delivery->dropoff_latitude ?? 0) }}, lng: {{ (float) ($delivery->dropoff_longitude ?? 0) }} };
            const initialAgent = {!! ($delivery->agent && $delivery->agent->current_latitude && $delivery->agent->current_longitude)
                ? json_encode(['lat' => (float) $delivery->agent->current_latitude, 'lng' => (float) $delivery->agent->current_longitude])
                : 'null' !!};

            let map, agentMarker, vendorMarker, customerMarker, routePolyline;

            function initMap() {
                if (! hasMapsKey || ! window.google) {
                    return;
                }

                const canvas = document.getElementById('map-canvas');

                if (! canvas) {
                    return;
                }

                map = new google.maps.Map(canvas, {
                    center: initialAgent || pickup,
                    zoom: 13,
                    mapId: mapId || undefined,
                    disableDefaultUI: true,
                    zoomControl: true,
                });

                vendorMarker = new google.maps.Marker({
                    position: pickup,
                    map,
                    label: { text: 'V', color: '#fff' },
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#11455B', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
                });

                customerMarker = new google.maps.Marker({
                    position: dropoff,
                    map,
                    label: { text: 'C', color: '#fff' },
                    icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#2FCB6E', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
                });

                if (initialAgent) {
                    placeAgentMarker(initialAgent);
                }

                routePolyline = new google.maps.Polyline({
                    path: [pickup, dropoff],
                    strokeColor: '#2FCB6E',
                    strokeOpacity: 0.7,
                    strokeWeight: 3,
                    map,
                });

                const bounds = new google.maps.LatLngBounds();
                bounds.extend(pickup);
                bounds.extend(dropoff);
                if (initialAgent) bounds.extend(initialAgent);
                map.fitBounds(bounds, 60);

                const recenterBtn = document.getElementById('recenter-btn');
                if (recenterBtn) {
                    recenterBtn.style.display = 'block';
                    recenterBtn.addEventListener('click', function () {
                        map.fitBounds(bounds, 60);
                    });
                }
            }

            function placeAgentMarker(position) {
                if (! map) return;

                if (! agentMarker) {
                    agentMarker = new google.maps.Marker({
                        position,
                        map,
                        label: { text: 'A', color: '#fff' },
                        icon: { path: google.maps.SymbolPath.CIRCLE, scale: 10, fillColor: '#F59E0B', fillOpacity: 1, strokeColor: '#fff', strokeWeight: 2 },
                    });
                } else {
                    agentMarker.setPosition(position);
                }
            }

            window.addEventListener('marketplace:maps-ready', initMap);

            // Real-time updates via Laravel Echo (Reverb). Echo itself is only
            // created when VITE_REVERB_APP_KEY is configured - without it this
            // page still works, it just shows the static status timeline above.
            // window.Echo is set up by a `type="module"` script (Vite), which
            // is deferred and runs after this classic inline script - so the
            // Echo-dependent setup has to wait for DOMContentLoaded too,
            // otherwise `window.Echo` reads as undefined even when configured.
            const liveDot = document.getElementById('live-dot');
            const liveNoteText = document.getElementById('live-note-text');
            const statusPill = document.getElementById('status-pill');
            const timelineList = document.getElementById('timeline-list');

            function setUpLiveUpdates() {
                if (! window.Echo) {
                    liveNoteText.textContent = 'Live updates are not configured for this environment';
                    return;
                }

                window.Echo.private('delivery.' + deliveryId)
                    .subscribed(function () {
                        liveDot.classList.add('on');
                        liveNoteText.textContent = 'Live updates connected';
                    })
                    .error(function () {
                        liveNoteText.textContent = 'Live updates unavailable';
                    })
                    .listen('.delivery.location-updated', function (e) {
                        placeAgentMarker({ lat: e.latitude, lng: e.longitude });
                        liveNoteText.textContent = 'Updated ' + new Date(e.recorded_at).toLocaleTimeString();
                    })
                    .listen('.delivery.status-changed', function (e) {
                        const label = e.to_status.replace(/_/g, ' ');
                        const capitalized = label.charAt(0).toUpperCase() + label.slice(1);

                        statusPill.textContent = capitalized;
                        statusPill.classList.toggle('terminal-failed', e.to_status === 'failed');
                        statusPill.classList.toggle('terminal-cancelled', e.to_status === 'cancelled');

                        const li = document.createElement('li');
                        li.innerHTML = '<div class="label">' + capitalized + '</div><div class="time">' + new Date(e.updated_at).toLocaleString() + '</div>';
                        timelineList.appendChild(li);

                        if (e.agent && e.agent.latitude && e.agent.longitude) {
                            placeAgentMarker({ lat: parseFloat(e.agent.latitude), lng: parseFloat(e.agent.longitude) });
                        }
                    });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setUpLiveUpdates);
            } else {
                setUpLiveUpdates();
            }
        })();
    </script>
</body>
</html>
