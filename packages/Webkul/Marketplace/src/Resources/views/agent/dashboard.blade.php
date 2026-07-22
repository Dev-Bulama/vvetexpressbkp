<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rider Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #F7FAF9; margin: 0; color: #102A43; }
        header { background: #11455B; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; }
        header form button { background: transparent; border: 1px solid rgba(255,255,255,.4); color: #fff; padding: 6px 12px; border-radius: 8px; cursor: pointer; }
        main { max-width: 560px; margin: 0 auto; padding: 20px 16px 60px; }
        .card { background: #fff; border: 1px solid #DFE6EA; border-radius: 12px; padding: 20px; margin-bottom: 16px; }
        .status-row { display: flex; align-items: center; justify-content: space-between; }
        .pill { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; font-weight: 600; font-size: 13px; }
        .pill.offline { background: #F1F5F9; color: #64748B; }
        .pill.available { background: #E7F9EF; color: #079447; }
        .pill.sharing { background: #FFF4E5; color: #B45309; }
        button.toggle { padding: 10px 18px; border-radius: 10px; border: none; font-weight: 600; cursor: pointer; }
        button.toggle.start { background: #2FCB6E; color: #fff; }
        button.toggle.stop { background: #DC3545; color: #fff; }
        .muted { color: #5C6B7A; font-size: 13px; }
        h2 { font-size: 15px; margin: 0 0 12px; color: #11455B; }
        .order-row { display: flex; justify-content: space-between; font-size: 14px; padding: 6px 0; border-bottom: 1px solid #F1F5F9; }
        .order-row:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <header>
        <strong>{{ $agent->name }}</strong>

        <form method="POST" action="{{ route('marketplace.agent.session.destroy') }}">
            @csrf
            @method('DELETE')
            <button type="submit">Log Out</button>
        </form>
    </header>

    <main>
        <div class="card">
            <div class="status-row">
                <span class="pill" id="status-pill">Loading...</span>
                <button class="toggle start" id="share-toggle" type="button">Start Sharing Location</button>
            </div>

            <p class="muted" id="location-note" style="margin-top:12px;">
                Location is only shared with the platform while you have an active delivery, and stops the moment it's completed or cancelled. Browser location sharing may pause if this tab isn't in the foreground.
            </p>
        </div>

        <div class="card">
            <h2>Active Delivery</h2>

            @if ($activeDelivery)
                <div class="order-row"><span>Order</span><span>#{{ $activeDelivery->order->increment_id }}</span></div>
                <div class="order-row"><span>Vendor</span><span>{{ $activeDelivery->seller->shop_name }}</span></div>
                <div class="order-row"><span>Service</span><span>{{ $activeDelivery->serviceType->name }}</span></div>
                <div class="order-row"><span>Status</span><span>{{ str_replace('_', ' ', ucfirst($activeDelivery->status)) }}</span></div>
                <div class="order-row"><span>Pickup verification</span><span>{{ $activeDelivery->pickup_verification_code }}</span></div>
                <div class="order-row"><span>Dropoff verification</span><span>{{ $activeDelivery->dropoff_verification_code }}</span></div>
            @else
                <p class="muted">No active delivery assigned right now.</p>
            @endif
        </div>
    </main>

    <script>
        (function () {
            const pill = document.getElementById('status-pill');
            const toggle = document.getElementById('share-toggle');
            let watchId = null;

            function setPillState(state) {
                pill.className = 'pill ' + state;
                pill.textContent = state === 'sharing' ? 'Sharing location' : state === 'available' ? 'Available' : 'Offline';
            }

            setPillState({!! $activeDelivery ? "'available'" : "'offline'" !!});

            function sendPosition(position) {
                fetch('{{ route('marketplace.agent.location.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude,
                        accuracy: position.coords.accuracy,
                        heading: position.coords.heading,
                        speed: position.coords.speed,
                    }),
                }).catch(() => {});
            }

            function startSharing() {
                if (! navigator.geolocation) {
                    alert('This browser does not support location sharing.');
                    return;
                }

                watchId = navigator.geolocation.watchPosition(sendPosition, function () {}, {
                    enableHighAccuracy: true,
                    maximumAge: 5000,
                    timeout: 15000,
                });

                setPillState('sharing');
                toggle.textContent = 'Stop Sharing Location';
                toggle.classList.remove('start');
                toggle.classList.add('stop');
            }

            function stopSharing() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }

                setPillState({!! $activeDelivery ? "'available'" : "'offline'" !!});
                toggle.textContent = 'Start Sharing Location';
                toggle.classList.remove('stop');
                toggle.classList.add('start');
            }

            toggle.addEventListener('click', function () {
                if (watchId === null) {
                    startSharing();
                } else {
                    stopSharing();
                }
            });
        })();
    </script>
</body>
</html>
