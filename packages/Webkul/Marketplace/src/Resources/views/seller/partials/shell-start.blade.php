<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Seller Portal' }} - {{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f4f5f7; margin: 0; color: #1f2937; }
        a { text-decoration: none; }

        .shell { display: flex; min-height: 100vh; }

        .sidebar { width: 220px; flex-shrink: 0; background: #11455B; color: #fff; display: flex; flex-direction: column; }
        .sidebar .brand { padding: 20px; font-size: 18px; font-weight: 700; border-bottom: 1px solid rgba(255,255,255,.12); }
        .sidebar .brand span { color: #2FCB6E; }
        .sidebar nav { padding: 12px; flex: 1; }
        .sidebar nav a { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 8px; color: rgba(255,255,255,.75); font-size: 14px; font-weight: 500; margin-bottom: 4px; }
        .sidebar nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .sidebar nav a.active { background: #2FCB6E; color: #fff; }
        .sidebar .seller-info { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,.12); font-size: 13px; }
        .sidebar .seller-info .name { font-weight: 600; color: #fff; }
        .sidebar .seller-info .status { display: inline-block; margin-top: 4px; padding: 2px 8px; border-radius: 999px; font-size: 11px; background: rgba(47,203,110,.2); color: #2FCB6E; }
        .sidebar form.logout button { width: 100%; margin: 8px 12px 16px; padding: 9px; background: rgba(255,255,255,.08); color: #fff; border: none; border-radius: 8px; font-size: 13px; cursor: pointer; }
        .sidebar form.logout button:hover { background: rgba(255,255,255,.16); }

        .content { flex: 1; min-width: 0; }
        .content .topbar { background: #fff; padding: 16px 28px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; }
        .content .topbar h1 { font-size: 18px; margin: 0; }
        .content .body { padding: 24px 28px; }

        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .msg.success { background: #dcfce7; color: #166534; }
        .msg.error { background: #fee2e2; color: #991b1b; }

        .stat-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 16px 18px; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
        .stat-card .label { font-size: 12px; color: #6b7280; }
        .stat-card .value { font-size: 22px; font-weight: 700; color: #11455B; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="shell">
        <aside class="sidebar">
            <div class="brand">Vet<span>Express</span> Seller</div>

            <nav>
                <a href="{{ route('marketplace.seller.dashboard.index') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('marketplace.seller.pos.index') }}" class="{{ ($active ?? '') === 'pos' ? 'active' : '' }}">Point of Sale</a>
                <a href="{{ route('marketplace.seller.products.index') }}" class="{{ ($active ?? '') === 'products' ? 'active' : '' }}">Products</a>
            </nav>

            @php $seller = $seller ?? auth()->guard('seller')->user(); @endphp

            <div class="seller-info">
                <div class="name">{{ $seller->shop_name }}</div>
                <span class="status">{{ ucfirst($seller->status) }}</span>
            </div>

            <form class="logout" method="POST" action="{{ route('marketplace.seller.session.destroy') }}">
                @csrf
                <button type="submit">Log out</button>
            </form>
        </aside>

        <div class="content">
            <div class="topbar">
                <h1>{{ $heading ?? ($title ?? 'Dashboard') }}</h1>
            </div>

            <div class="body">
                @if (session('success'))
                    <div class="msg success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="msg error">{{ session('error') }}</div>
                @endif
