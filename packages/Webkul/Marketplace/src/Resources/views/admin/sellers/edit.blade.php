<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $seller->shop_name }} - Marketplace</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; }
        header { background: #11455B; color: #fff; padding: 16px 24px; }
        main { max-width: 600px; margin: 24px auto; padding: 0 16px; }
        .card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        dl { display: grid; grid-template-columns: 140px 1fr; row-gap: 10px; font-size: 14px; }
        dt { font-weight: 600; color: #374151; }
        .badge { padding: 2px 8px; border-radius: 999px; font-size: 12px; }
        .badge.pending { background: #fef9c3; color: #854d0e; }
        .badge.approved { background: #dcfce7; color: #166534; }
        .badge.suspended { background: #fee2e2; color: #991b1b; }
        .actions { margin-top: 24px; display: flex; gap: 10px; }
        button { padding: 10px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        button.approve { background: #2FCB6E; color: #fff; }
        button.suspend { background: #dc2626; color: #fff; }
        button.pending { background: #ca8a04; color: #fff; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; background: #dcfce7; color: #166534; }
        a.back { color: #2FCB6E; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <header>Marketplace &middot; Sellers</header>

    <main>
        <p><a class="back" href="{{ route('marketplace.admin.sellers.index') }}">&larr; Back to sellers</a></p>

        @if (session('success'))
            <div class="msg">{{ session('success') }}</div>
        @endif

        <div class="card">
            <dl>
                <dt>Shop Name</dt><dd>{{ $seller->shop_name }}</dd>
                <dt>Owner</dt><dd>{{ $seller->name }}</dd>
                <dt>Email</dt><dd>{{ $seller->email }}</dd>
                <dt>Phone</dt><dd>{{ $seller->phone ?? '—' }}</dd>
                <dt>Address</dt><dd>{{ $seller->address ?? '—' }}</dd>
                <dt>City</dt><dd>{{ $seller->city ?? '—' }}</dd>
                <dt>Coordinates</dt><dd>{{ $seller->latitude ?? '—' }}, {{ $seller->longitude ?? '—' }}</dd>
                <dt>Status</dt><dd><span class="badge {{ $seller->status }}">{{ ucfirst($seller->status) }}</span></dd>
                <dt>Joined</dt><dd>{{ $seller->created_at->format('d M Y') }}</dd>
            </dl>

            <div class="actions">
                @if ($seller->status !== 'approved')
                    <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                        @csrf
                        <input type="hidden" name="status" value="approved">
                        <button class="approve" type="submit">Approve</button>
                    </form>
                @endif

                @if ($seller->status !== 'suspended')
                    <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                        @csrf
                        <input type="hidden" name="status" value="suspended">
                        <button class="suspend" type="submit">Suspend</button>
                    </form>
                @endif

                @if ($seller->status !== 'pending')
                    <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                        @csrf
                        <input type="hidden" name="status" value="pending">
                        <button class="pending" type="submit">Reset to Pending</button>
                    </form>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
