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

        .menu-toggle { display: none; background: none; border: none; padding: 4px; cursor: pointer; }
        .menu-toggle svg { width: 24px; height: 24px; color: #11455B; }
        .sidebar-overlay { display: none; }

        /* Below this width the sidebar becomes an off-canvas drawer (hidden
           by default, slides in over the content) opened via the hamburger
           button in the topbar, instead of permanently squeezing the page
           into a sliver next to a full-height nav column. */
        @media (max-width: 860px) {
            .shell { display: block; }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: 240px;
                z-index: 40;
                transform: translateX(-100%);
                transition: transform .2s ease;
            }

            .sidebar.open { transform: translateX(0); }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,.4);
                z-index: 30;
            }

            .sidebar-overlay.open { display: block; }

            .content { width: 100%; }

            .menu-toggle { display: block; }

            table { display: block; overflow-x: auto; white-space: nowrap; }
        }
    </style>
</head>
<body>
    <div class="shell">
        <div class="sidebar-overlay" id="sidebar-overlay" onclick="document.getElementById('seller-sidebar').classList.remove('open'); this.classList.remove('open');"></div>

        <aside class="sidebar" id="seller-sidebar">
            <div class="brand">Vet<span>Express</span> Seller</div>

            <nav>
                <a href="{{ route('marketplace.seller.dashboard.index') }}" class="{{ ($active ?? '') === 'dashboard' ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('marketplace.seller.pos.index') }}" class="{{ ($active ?? '') === 'pos' ? 'active' : '' }}">Point of Sale</a>
                <a href="{{ route('marketplace.seller.products.index') }}" class="{{ ($active ?? '') === 'products' ? 'active' : '' }}">Products</a>
                <a href="{{ route('marketplace.seller.profile.edit') }}" class="{{ ($active ?? '') === 'profile' ? 'active' : '' }}">Profile</a>
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
                <button type="button" class="menu-toggle" aria-label="Open menu" onclick="document.getElementById('seller-sidebar').classList.add('open'); document.getElementById('sidebar-overlay').classList.add('open');">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                </button>

                <h1>{{ $heading ?? ($title ?? 'Dashboard') }}</h1>
            </div>

            <div class="body">
                @if (session('success'))
                    <div class="msg success">{{ session('success') }}</div>
                @endif

                @if (session('error'))
                    <div class="msg error">{{ session('error') }}</div>
                @endif
