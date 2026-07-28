@include('marketplace::seller.partials.shell-start', ['title' => 'Profile', 'heading' => 'Shop Profile', 'active' => 'profile'])

<style>
    .card { max-width: 560px; background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .card label { display: block; font-size: 13px; font-weight: 600; margin: 16px 0 4px; color: #374151; }
    .card label:first-child { margin-top: 0; }
    .card input[type=text], .card input[type=email], .card input[type=password] {
        width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px;
    }
    .card .error { color: #dc2626; font-size: 12px; margin-top: 4px; }
    .card .hint { color: #6b7280; font-size: 12px; margin-top: 4px; }
    .card button { width: auto; padding: 11px 20px; margin-top: 24px; background: #2FCB6E; color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
    .card hr { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
    .logo-preview { display: flex; align-items: center; gap: 14px; }
    .logo-preview img { width: 64px; height: 64px; border-radius: 10px; object-fit: cover; background: #f1f5f9; }
    .logo-preview .placeholder { width: 64px; height: 64px; border-radius: 10px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 11px; text-align: center; }
</style>

<div class="card">
    <form method="POST" action="{{ route('marketplace.seller.profile.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <label>Shop Logo</label>
        <div class="logo-preview">
            @if ($seller->logoUrl())
                <img src="{{ $seller->logoUrl() }}" alt="{{ $seller->shop_name }}">
            @else
                <div class="placeholder">No logo</div>
            @endif

            <input type="file" name="logo" accept="image/*">
        </div>
        @error('logo') <div class="error">{{ $message }}</div> @enderror

        <label>Your Name</label>
        <input type="text" name="name" value="{{ old('name', $seller->name) }}" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <label>Shop Name</label>
        <input type="text" name="shop_name" value="{{ old('shop_name', $seller->shop_name) }}" required>
        @error('shop_name') <div class="error">{{ $message }}</div> @enderror

        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $seller->email) }}" required>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label>Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $seller->phone) }}">

        <label>Address</label>
        <input type="text" name="address" value="{{ old('address', $seller->address) }}">

        <label>City</label>
        <input type="text" name="city" value="{{ old('city', $seller->city) }}">

        <hr>

        <p style="margin:0;font-size:14px;font-weight:600;">Change Password</p>
        <p class="hint" style="margin-top:2px;">Leave blank to keep your current password.</p>

        <label>Current Password</label>
        <input type="password" name="current_password">
        @error('current_password') <div class="error">{{ $message }}</div> @enderror

        <label>New Password</label>
        <input type="password" name="password">
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>Confirm New Password</label>
        <input type="password" name="password_confirmation">

        <button type="submit">Save Changes</button>
    </form>
</div>

@include('marketplace::seller.partials.shell-end')
