<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Seller Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; }
        header { background: #0f172a; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        main { max-width: 900px; margin: 24px auto; padding: 0 16px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f8fafc; }
        form.logout button { background: none; border: none; color: #fff; cursor: pointer; font-size: 14px; }
        .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }
    </style>
</head>
<body>
    <header>
        <div>{{ $seller->shop_name }} &middot; <span style="opacity:.7">{{ ucfirst($seller->status) }}</span></div>
        <form class="logout" method="POST" action="{{ route('marketplace.seller.session.destroy') }}">
            @csrf
            <button type="submit">Log out</button>
        </form>
    </header>

    <main>
        <h2>Your Products</h2>

        @if ($products->isEmpty())
            <div class="empty">No products listed yet.</div>
        @else
            <table>
                <thead>
                    <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($products as $offer)
                        <tr>
                            <td>{{ $offer->product?->name ?? $offer->product?->sku }}</td>
                            <td>{{ $offer->price }}</td>
                            <td>{{ $offer->quantity }}</td>
                            <td>{{ $offer->is_active ? 'Active' : 'Inactive' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            {{ $products->links() }}
        @endif
    </main>
</body>
</html>
