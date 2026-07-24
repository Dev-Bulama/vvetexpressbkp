<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Seller Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; padding: 40px 16px; }
        .card { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 22px; margin: 0 0 24px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 16px 0 4px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        button { width: 100%; margin-top: 24px; padding: 12px; background: #2FCB6E; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        button:disabled { opacity: .6; cursor: not-allowed; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .msg.success { background: #dcfce7; color: #166534; }
        .error { color: #dc2626; font-size: 12px; margin-top: 4px; }
        a { color: #2FCB6E; }

        .section { margin-top: 20px; padding-top: 16px; border-top: 1px solid #eef0f2; }
        .section-title { font-size: 14px; font-weight: 700; margin-bottom: 2px; }
        .section-hint { font-size: 12px; color: #6b7280; margin-bottom: 10px; }

        .btn-secondary { margin-top: 8px; background: #11455B; }
        .btn-outline { margin-top: 8px; background: #fff; color: #11455B; border: 1px solid #11455B; }
        .btn-danger { margin-top: 8px; background: #dc2626; }
        .btn-row { display: flex; gap: 8px; }
        .btn-row > button { margin-top: 8px; }

        .hint { font-size: 12px; color: #6b7280; margin-top: 4px; }
        .hint.ok { color: #166534; }
        .hint.warn { color: #b45309; }

        #loc-map-canvas { display: none; width: 100%; height: 220px; border-radius: 8px; margin-top: 10px; }
        .map-hint { font-size: 11px; color: #6b7280; margin-top: 4px; }

        #camera-box { display: none; margin-top: 10px; }
        #camera-preview, #camera-playback { width: 100%; border-radius: 8px; background: #000; display: none; max-height: 260px; }
        .rec-indicator { display: none; align-items: center; gap: 6px; font-size: 12px; font-weight: 600; color: #dc2626; margin-top: 6px; }
        .rec-dot { width: 8px; height: 8px; border-radius: 999px; background: #dc2626; animation: pulse 1s infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    </style>
</head>
<body>
    <div class="card">
        <h1>Become a Seller</h1>

        @if (session('success'))
            <div class="msg success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('marketplace.seller.register.store') }}" enctype="multipart/form-data" id="seller-signup-form">
            @csrf

            <label>Your Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label>Shop Name</label>
            <input type="text" name="shop_name" value="{{ old('shop_name') }}" required>
            @error('shop_name') <div class="error">{{ $message }}</div> @enderror

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label>Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}">

            <div class="section">
                <div class="section-title">Shop Location</div>
                <div class="section-hint">Detect your shop's real location automatically, or enter it manually below.</div>

                <button type="button" class="btn-secondary" id="detect-location-btn">Detect My Shop Location</button>
                <div class="hint" id="detect-location-status"></div>

                <input type="hidden" name="latitude" id="loc-latitude" value="{{ old('latitude') }}">
                <input type="hidden" name="longitude" id="loc-longitude" value="{{ old('longitude') }}">

                @if ($mapsApiKey)
                    <div id="loc-map-canvas"></div>
                    <div class="map-hint" id="loc-map-hint" style="display:none;">Drag the pin, or tap the map, to pinpoint the exact spot.</div>
                @endif

                <label>Address</label>
                <input type="text" name="address" id="loc-address" value="{{ old('address') }}">

                <label>City</label>
                <input type="text" name="city" id="loc-city" value="{{ old('city') }}">
            </div>

            <div class="section">
                <div class="section-title">Verify Your Shop (optional)</div>
                <div class="section-hint">
                    Record a quick live walkthrough of your shop and surroundings - similar to how Google verifies a
                    business listing. This is captured live from your camera right now; you can't attach an existing
                    video file.
                </div>

                <button type="button" class="btn-outline" id="camera-start-btn">Start Camera</button>
                <div class="hint" id="camera-status"></div>

                <div id="camera-box">
                    <video id="camera-preview" autoplay muted playsinline></video>
                    <video id="camera-playback" controls playsinline></video>

                    <div class="rec-indicator" id="rec-indicator"><span class="rec-dot"></span> Recording&hellip; <span id="rec-timer">0s</span> / 60s</div>

                    <div class="btn-row">
                        <button type="button" class="btn-outline" id="camera-switch-btn" style="display:none;">Switch Camera</button>
                        <button type="button" class="btn-secondary" id="camera-record-btn" style="display:none;">Start Recording</button>
                        <button type="button" class="btn-danger" id="camera-stop-btn" style="display:none;">Stop Recording</button>
                        <button type="button" class="btn-outline" id="camera-retake-btn" style="display:none;">Re-record</button>
                    </div>
                </div>

                @error('verification_video') <div class="error">{{ $message }}</div> @enderror
            </div>

            <label>Password</label>
            <input type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit">Create Seller Account</button>
        </form>

        <p style="margin-top:16px;font-size:13px;">Already have an account? <a href="{{ route('marketplace.seller.session.index') }}">Log in</a></p>
    </div>

    @if ($mapsApiKey)
        <script>
            window.__sellerSignupMapsReady = function () {
                window.dispatchEvent(new Event('marketplace:signup-maps-ready'));
            };
        </script>
        <script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $mapsApiKey }}&callback=__sellerSignupMapsReady&region={{ config('services.google_maps.region') }}&language={{ config('services.google_maps.language') }}"></script>
    @endif

    <script>
        (function () {
            const hasMapsKey = {!! $mapsApiKey ? 'true' : 'false' !!};
            const mapId = {!! $mapId ? json_encode($mapId) : 'null' !!};

            let map = null;
            let marker = null;

            /**
             * Shop location auto-detect + pin placement. Reverse-geocoding
             * (turning coordinates into an address) uses OpenStreetMap's
             * free, keyless Nominatim service - same as the customer
             * delivery-location modal. The Google Map here (only rendered
             * when GOOGLE_MAPS_API_KEY is configured) is purely a visual
             * "confirm/adjust the exact pin" aid, since raw GPS alone can
             * be tens of meters off, especially indoors - dragging the pin
             * or tapping the map re-runs the same reverse-geocode.
             */
            function reverseGeocode(lat, lng) {
                const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat='
                    + encodeURIComponent(lat) + '&lon=' + encodeURIComponent(lng) + '&addressdetails=1';

                return fetch(url, { headers: { Accept: 'application/json' } })
                    .then(function (response) {
                        if (! response.ok) {
                            throw new Error('reverse geocode failed');
                        }

                        return response.json();
                    })
                    .then(function (data) {
                        const address = data && data.address ? data.address : {};
                        const streetParts = [address.house_number, address.road].filter(Boolean);

                        return {
                            address: streetParts.join(' ') || address.neighbourhood || address.suburb || '',
                            city: address.city || address.town || address.village || address.county || '',
                        };
                    })
                    .catch(function () {
                        return {};
                    });
            }

            function setCoords(lat, lng, updateFields) {
                document.getElementById('loc-latitude').value = lat;
                document.getElementById('loc-longitude').value = lng;

                if (map && marker) {
                    const position = { lat: lat, lng: lng };
                    marker.setPosition(position);
                    map.panTo(position);
                }

                if (updateFields === false) {
                    return Promise.resolve();
                }

                return reverseGeocode(lat, lng).then(function (resolved) {
                    if (resolved.address) {
                        document.getElementById('loc-address').value = resolved.address;
                    }

                    if (resolved.city) {
                        document.getElementById('loc-city').value = resolved.city;
                    }
                });
            }

            function initMapIfReady() {
                if (! hasMapsKey || ! window.google || map) {
                    return;
                }

                const canvas = document.getElementById('loc-map-canvas');

                if (! canvas) {
                    return;
                }

                const lat = parseFloat(document.getElementById('loc-latitude').value) || 6.5244;
                const lng = parseFloat(document.getElementById('loc-longitude').value) || 3.3792;

                canvas.style.display = 'block';
                document.getElementById('loc-map-hint').style.display = 'block';

                map = new google.maps.Map(canvas, {
                    center: { lat: lat, lng: lng },
                    zoom: 15,
                    mapId: mapId || undefined,
                    disableDefaultUI: true,
                    zoomControl: true,
                });

                marker = new google.maps.Marker({
                    position: { lat: lat, lng: lng },
                    map: map,
                    draggable: true,
                });

                marker.addListener('dragend', function () {
                    const position = marker.getPosition();
                    document.getElementById('loc-latitude').value = position.lat();
                    document.getElementById('loc-longitude').value = position.lng();

                    reverseGeocode(position.lat(), position.lng()).then(function (resolved) {
                        if (resolved.address) document.getElementById('loc-address').value = resolved.address;
                        if (resolved.city) document.getElementById('loc-city').value = resolved.city;
                    });
                });

                map.addListener('click', function (event) {
                    marker.setPosition(event.latLng);
                    document.getElementById('loc-latitude').value = event.latLng.lat();
                    document.getElementById('loc-longitude').value = event.latLng.lng();

                    reverseGeocode(event.latLng.lat(), event.latLng.lng()).then(function (resolved) {
                        if (resolved.address) document.getElementById('loc-address').value = resolved.address;
                        if (resolved.city) document.getElementById('loc-city').value = resolved.city;
                    });
                });
            }

            window.addEventListener('marketplace:signup-maps-ready', initMapIfReady);

            const detectBtn = document.getElementById('detect-location-btn');
            const detectStatus = document.getElementById('detect-location-status');

            /**
             * Browser geolocation with a high-accuracy attempt first, and
             * a fallback to a much cheaper (network/WiFi-based) lookup if
             * that one times out or is unavailable. A device without a
             * real GPS chip (most laptops) can take a long time - or
             * never resolve - a high-accuracy request, but responds fast
             * to the low-accuracy one, so this fallback is what actually
             * fixes "always times out" for that class of hardware rather
             * than just raising a timeout number.
             */
            function detectLocation(options, isFallback) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        detectBtn.textContent = 'Location detected - looking up your address...';

                        setCoords(lat, lng)
                            .then(function () {
                                detectStatus.className = 'hint ok';
                                detectStatus.textContent = 'Location detected. Drag the pin below if it is not exact, and confirm the address.';
                            })
                            .finally(function () {
                                detectBtn.disabled = false;
                                detectBtn.textContent = 'Detect My Shop Location';
                            });
                    },
                    function (error) {
                        // First attempt (high accuracy) timed out or the
                        // position is temporarily unavailable - retry once
                        // with a much cheaper, faster lookup before giving
                        // up and asking for manual entry.
                        if (! isFallback && (error.code === 2 || error.code === 3)) {
                            detectBtn.textContent = 'Still detecting (trying a faster method)...';

                            detectLocation({ enableHighAccuracy: false, timeout: 20000, maximumAge: 300000 }, true);
                            return;
                        }

                        detectBtn.disabled = false;
                        detectBtn.textContent = 'Detect My Shop Location';

                        const messages = {
                            1: 'Location permission denied. Please enter your address manually, or use the map below.',
                            2: 'Your location is currently unavailable. Please enter your address manually, or use the map below.',
                            3: 'Location request timed out. Please enter your address manually, or use the map below.',
                        };

                        detectStatus.className = 'hint warn';
                        detectStatus.textContent = messages[error.code] || 'Could not detect your location. Please enter your address manually.';
                    },
                    options
                );
            }

            detectBtn.addEventListener('click', function () {
                detectStatus.className = 'hint';
                detectStatus.textContent = '';

                if (! navigator.geolocation) {
                    detectStatus.className = 'hint warn';
                    detectStatus.textContent = 'Your browser does not support location detection. Please enter your address manually.';
                    return;
                }

                detectBtn.disabled = true;
                detectBtn.textContent = 'Detecting your location...';

                detectLocation({ enableHighAccuracy: true, timeout: 20000, maximumAge: 60000 }, false);
            });
        })();

        (function () {
            /**
             * Live shop-verification video. Deliberately camera-only: there
             * is no <input type="file"> the seller can pick an existing
             * video from - a MediaStream is opened live via getUserMedia,
             * recorded in the browser with MediaRecorder, and only the
             * resulting in-memory Blob is ever attached to the form (via
             * DataTransfer onto a hidden file input), as a real File
             * object so the existing multipart form submit just works.
             *
             * This can't cryptographically prove the clip wasn't somehow
             * faked by a determined bad actor crafting a raw HTTP request -
             * no browser-only mechanism can promise that - but it does mean
             * the normal UI never offers an "upload a file" path, matching
             * how Google's own business-verification video capture works.
             */
            const MAX_SECONDS = 60;

            const startBtn = document.getElementById('camera-start-btn');
            const switchBtn = document.getElementById('camera-switch-btn');
            const recordBtn = document.getElementById('camera-record-btn');
            const stopBtn = document.getElementById('camera-stop-btn');
            const retakeBtn = document.getElementById('camera-retake-btn');
            const statusEl = document.getElementById('camera-status');
            const box = document.getElementById('camera-box');
            const preview = document.getElementById('camera-preview');
            const playback = document.getElementById('camera-playback');
            const recIndicator = document.getElementById('rec-indicator');
            const recTimer = document.getElementById('rec-timer');
            const form = document.getElementById('seller-signup-form');

            if (
                ! navigator.mediaDevices
                || ! navigator.mediaDevices.getUserMedia
                || typeof MediaRecorder === 'undefined'
            ) {
                startBtn.disabled = true;
                statusEl.className = 'hint warn';
                statusEl.textContent = 'Live video capture is not supported in this browser.';
                return;
            }

            let stream = null;
            let recorder = null;
            let chunks = [];
            let timerHandle = null;
            let elapsed = 0;
            let hiddenInput = null;

            // Shop verification should show the shop, not the seller's
            // face - default to the rear/environment camera on devices
            // that have one. Toggled by the "Switch Camera" button below,
            // which just flips this and re-opens the stream.
            let facingMode = 'environment';

            function stopStream() {
                if (stream) {
                    stream.getTracks().forEach(function (track) { track.stop(); });
                    stream = null;
                }
            }

            function pickMimeType() {
                const candidates = ['video/webm;codecs=vp8,opus', 'video/webm', 'video/mp4'];

                for (const type of candidates) {
                    if (MediaRecorder.isTypeSupported(type)) {
                        return type;
                    }
                }

                return '';
            }

            function attachRecordingToForm(blob, mimeType) {
                const extension = mimeType.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
                const file = new File([blob], 'shop-verification.' + extension, { type: mimeType || blob.type });

                if (! hiddenInput) {
                    hiddenInput = document.createElement('input');
                    hiddenInput.type = 'file';
                    hiddenInput.name = 'verification_video';
                    hiddenInput.style.display = 'none';
                    form.appendChild(hiddenInput);
                }

                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                hiddenInput.files = dataTransfer.files;
            }

            function openCamera() {
                return navigator.mediaDevices.getUserMedia({
                    video: { facingMode: { ideal: facingMode }, width: { ideal: 1280 }, height: { ideal: 720 } },
                    audio: true,
                }).then(function (mediaStream) {
                    stopStream();
                    stream = mediaStream;
                    preview.srcObject = stream;
                });
            }

            startBtn.addEventListener('click', function () {
                statusEl.className = 'hint';
                statusEl.textContent = 'Requesting camera access...';
                startBtn.disabled = true;

                openCamera().then(function () {
                    box.style.display = 'block';
                    preview.style.display = 'block';
                    playback.style.display = 'none';

                    startBtn.style.display = 'none';
                    switchBtn.style.display = 'block';
                    recordBtn.style.display = 'block';
                    statusEl.className = 'hint ok';
                    statusEl.textContent = 'Camera ready. Walk around and show your shop, then record.';
                }).catch(function () {
                    startBtn.disabled = false;
                    statusEl.className = 'hint warn';
                    statusEl.textContent = 'Could not access your camera. Check permissions and try again.';
                });
            });

            switchBtn.addEventListener('click', function () {
                const previousFacingMode = facingMode;
                facingMode = facingMode === 'environment' ? 'user' : 'environment';

                switchBtn.disabled = true;
                statusEl.className = 'hint';
                statusEl.textContent = 'Switching camera...';

                openCamera().then(function () {
                    switchBtn.disabled = false;
                    statusEl.className = 'hint ok';
                    statusEl.textContent = 'Camera switched.';
                }).catch(function () {
                    // Likely only one camera is available on this device -
                    // revert to whichever facing mode was already working.
                    facingMode = previousFacingMode;
                    switchBtn.disabled = false;
                    statusEl.className = 'hint warn';
                    statusEl.textContent = 'Could not switch camera - this device may only have one.';
                });
            });

            recordBtn.addEventListener('click', function () {
                chunks = [];
                elapsed = 0;

                const mimeType = pickMimeType();

                try {
                    recorder = mimeType ? new MediaRecorder(stream, { mimeType }) : new MediaRecorder(stream);
                } catch (e) {
                    statusEl.className = 'hint warn';
                    statusEl.textContent = 'Recording is not supported in this browser.';
                    return;
                }

                recorder.ondataavailable = function (e) {
                    if (e.data && e.data.size > 0) {
                        chunks.push(e.data);
                    }
                };

                recorder.onstop = function () {
                    const blob = new Blob(chunks, { type: recorder.mimeType || 'video/webm' });

                    attachRecordingToForm(blob, recorder.mimeType || 'video/webm');

                    preview.style.display = 'none';
                    playback.style.display = 'block';
                    playback.src = URL.createObjectURL(blob);

                    stopStream();

                    switchBtn.style.display = 'none';
                    recordBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    retakeBtn.style.display = 'block';
                    recIndicator.style.display = 'none';

                    statusEl.className = 'hint ok';
                    statusEl.textContent = 'Recording captured. Review it below, or re-record.';
                };

                recorder.start();

                // Camera can't be swapped mid-recording without rebuilding
                // the MediaRecorder against a new stream, so hide it for
                // the duration of the recording rather than let it silently
                // do nothing (or worse, drop frames) if tapped.
                switchBtn.style.display = 'none';
                recordBtn.style.display = 'none';
                stopBtn.style.display = 'block';
                recIndicator.style.display = 'flex';
                statusEl.textContent = '';

                timerHandle = setInterval(function () {
                    elapsed += 1;
                    recTimer.textContent = elapsed + 's';

                    if (elapsed >= MAX_SECONDS) {
                        recorder.stop();
                        clearInterval(timerHandle);
                    }
                }, 1000);
            });

            stopBtn.addEventListener('click', function () {
                clearInterval(timerHandle);

                if (recorder && recorder.state !== 'inactive') {
                    recorder.stop();
                }
            });

            retakeBtn.addEventListener('click', function () {
                if (hiddenInput) {
                    hiddenInput.value = '';
                }

                playback.removeAttribute('src');
                playback.style.display = 'none';
                retakeBtn.style.display = 'none';
                statusEl.textContent = '';

                startBtn.style.display = 'block';
                startBtn.disabled = false;
                startBtn.click();
            });
        })();
    </script>
</body>
</html>
