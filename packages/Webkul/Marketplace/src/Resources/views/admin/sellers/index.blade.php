<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sellers - Marketplace</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; }
        header { background: #0f172a; color: #fff; padding: 16px 24px; }
        main { max-width: 1000px; margin: 24px auto; padding: 0 16px; }
        .tabs { margin-bottom: 16px; }
        .tabs a { display: inline-block; padding: 6px 14px; border-radius: 999px; font-size: 13px; text-decoration: none; color: #374151; background: #e5e7eb; margin-right: 8px; }
        .tabs a.active { background: #0f172a; color: #fff; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f8fafc; }
        .badge { padding: 2px 8px; border-radius: 999px; font-size: 12px; }
        .badge.pending { background: #fef9c3; color: #854d0e; }
        .badge.approved { background: #dcfce7; color: #166534; }
        .badge.suspended { background: #fee2e2; color: #991b1b; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; background: #dcfce7; color: #166534; }
        a.view { color: #16a34a; text-decoration: none; }
        .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }
    </style>
</head>
<body>
    <header>Marketplace &middot; Sellers</header>

    <main>
        @if (session('success'))
            <div class="msg">{{ session('success') }}</div>
        @endif

        <div class="tabs">
            <a href="{{ route('marketplace.admin.sellers.index') }}" class="{{ $status ? '' : 'active' }}">All</a>
            <a href="{{ route('marketplace.admin.sellers.index', ['status' => 'pending']) }}" class="{{ $status === 'pending' ? 'active' : '' }}">Pending</a>
            <a href="{{ route('marketplace.admin.sellers.index', ['status' => 'approved']) }}" class="{{ $status === 'approved' ? 'active' : '' }}">Approved</a>
            <a href="{{ route('marketplace.admin.sellers.index', ['status' => 'suspended']) }}" class="{{ $status === 'suspended' ? 'active' : '' }}">Suspended</a>
        </div>

        @if ($sellers->isEmpty())
            <div class="empty">No sellers found.</div>
        @else
            <table>
                <thead>
                    <tr><th>Shop</th><th>Owner</th><th>Email</th><th>City</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($sellers as $seller)
                        <tr>
                            <td>{{ $seller->shop_name }}</td>
                            <td>{{ $seller->name }}</td>
                            <td>{{ $seller->email }}</td>
                            <td>{{ $seller->city }}</td>
                            <td><span class="badge {{ $seller->status }}">{{ ucfirst($seller->status) }}</span></td>
                            <td><a class="view" href="{{ route('marketplace.admin.sellers.edit', $seller->id) }}">Manage</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:16px;">{{ $sellers->links() }}</div>
        @endif
    </main>
</body>
</html>
