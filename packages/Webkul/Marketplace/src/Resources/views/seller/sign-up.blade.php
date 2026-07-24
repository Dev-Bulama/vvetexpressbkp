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

    <script>
        (function () {
            /**
             * Shop location auto-detect - same getCurrentPosition ->
             * Nominatim reverse-geocode -> fill-fields pattern already used
             * for customer delivery location (see
             * shop/components/layouts/header/location-modal.blade.php).
             * Nominatim is free/keyless, so this needs no Google Maps API
             * key. Never fakes an address: on any failure the seller just
             * fills the fields in manually.
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

            const detectBtn = document.getElementById('detect-location-btn');
            const detectStatus = document.getElementById('detect-location-status');

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

                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;

                        document.getElementById('loc-latitude').value = lat;
                        document.getElementById('loc-longitude').value = lng;

                        detectBtn.textContent = 'Location detected - looking up your address...';

                        reverseGeocode(lat, lng)
                            .then(function (resolved) {
                                if (resolved.address) {
                                    document.getElementById('loc-address').value = resolved.address;
                                }

                                if (resolved.city) {
                                    document.getElementById('loc-city').value = resolved.city;
                                }

                                detectStatus.className = 'hint ok';
                                detectStatus.textContent = 'Location detected. Please confirm the address below.';
                            })
                            .finally(function () {
                                detectBtn.disabled = false;
                                detectBtn.textContent = 'Detect My Shop Location';
                            });
                    },
                    function (error) {
                        detectBtn.disabled = false;
                        detectBtn.textContent = 'Detect My Shop Location';

                        const messages = {
                            1: 'Location permission denied. Please enter your address manually.',
                            2: 'Your location is currently unavailable. Please enter your address manually.',
                            3: 'Location request timed out. Please enter your address manually.',
                        };

                        detectStatus.className = 'hint warn';
                        detectStatus.textContent = messages[error.code] || 'Could not detect your location. Please enter your address manually.';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
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

            startBtn.addEventListener('click', function () {
                statusEl.className = 'hint';
                statusEl.textContent = 'Requesting camera access...';
                startBtn.disabled = true;

                navigator.mediaDevices.getUserMedia({
                    video: { width: { ideal: 640 }, height: { ideal: 480 } },
                    audio: true,
                }).then(function (mediaStream) {
                    stream = mediaStream;
                    box.style.display = 'block';
                    preview.style.display = 'block';
                    preview.srcObject = stream;
                    playback.style.display = 'none';

                    startBtn.style.display = 'none';
                    recordBtn.style.display = 'block';
                    statusEl.className = 'hint ok';
                    statusEl.textContent = 'Camera ready. Walk around and show your shop, then record.';
                }).catch(function () {
                    startBtn.disabled = false;
                    statusEl.className = 'hint warn';
                    statusEl.textContent = 'Could not access your camera. Check permissions and try again.';
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

                    recordBtn.style.display = 'none';
                    stopBtn.style.display = 'none';
                    retakeBtn.style.display = 'block';
                    recIndicator.style.display = 'none';

                    statusEl.className = 'hint ok';
                    statusEl.textContent = 'Recording captured. Review it below, or re-record.';
                };

                recorder.start();

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
