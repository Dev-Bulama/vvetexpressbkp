<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Seller Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; padding: 40px 16px; }
        .card { max-width: 400px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 22px; margin: 0 0 24px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 16px 0 4px; }
        input { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        button { width: 100%; margin-top: 24px; padding: 12px; background: #2FCB6E; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .msg.error { background: #fee2e2; color: #991b1b; }
        .msg.warning { background: #fef9c3; color: #854d0e; }
        a { color: #2FCB6E; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Seller Login</h1>

        @if (session('error'))
            <div class="msg error">{{ session('error') }}</div>
        @endif
        @if (session('warning'))
            <div class="msg warning">{{ session('warning') }}</div>
        @endif

        <form method="POST" action="{{ route('marketplace.seller.session.create') }}">
            @csrf

            <label>Email</label>
            <input type="email" name="email" value="{{ old('email') }}" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button type="submit">Log In</button>
        </form>

        <p style="margin-top:16px;font-size:13px;">New seller? <a href="{{ route('marketplace.seller.register.index') }}">Create an account</a></p>
    </div>
</body>
</html>
