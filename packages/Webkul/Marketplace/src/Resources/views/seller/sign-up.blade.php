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
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .msg.success { background: #dcfce7; color: #166534; }
        .error { color: #dc2626; font-size: 12px; margin-top: 4px; }
        a { color: #2FCB6E; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Become a Seller</h1>

        @if (session('success'))
            <div class="msg success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('marketplace.seller.register.store') }}">
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

            <label>Address</label>
            <input type="text" name="address" value="{{ old('address') }}">

            <label>City</label>
            <input type="text" name="city" value="{{ old('city') }}">

            <label>Password</label>
            <input type="password" name="password" required>
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required>

            <button type="submit">Create Seller Account</button>
        </form>

        <p style="margin-top:16px;font-size:13px;">Already have an account? <a href="{{ route('marketplace.seller.session.index') }}">Log in</a></p>
    </div>
</body>
</html>
