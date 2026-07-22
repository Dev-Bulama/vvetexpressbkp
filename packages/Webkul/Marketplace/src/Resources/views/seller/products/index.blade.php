<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>My Products</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; }
        header { background: #0f172a; color: #fff; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; }
        header a.button { background: #16a34a; color: #fff; text-decoration: none; padding: 8px 14px; border-radius: 8px; font-size: 14px; font-weight: 600; }
        main { max-width: 900px; margin: 24px auto; padding: 0 16px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
        th { background: #f8fafc; }
        .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }
        .msg { padding: 10px 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .msg.success { background: #dcfce7; color: #166534; }
        .msg.error { background: #fee2e2; color: #991b1b; }
        a.edit { color: #16a34a; text-decoration: none; margin-right: 10px; }
        button.delete { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 14px; padding: 0; }
    </style>
</head>
<body>
    <header>
        <div>My Products</div>
        <a class="button" href="{{ route('marketplace.seller.products.create') }}">+ Add Product</a>
    </header>

    <main>
        @if (session('success'))
            <div class="msg success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="msg error">{{ session('error') }}</div>
        @endif

        @if ($offers->isEmpty())
            <div class="empty">You haven't listed any products yet.</div>
        @else
            <table>
                <thead>
                    <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    @foreach ($offers as $offer)
                        <tr>
                            <td>{{ $offer->product?->name ?? $offer->product?->sku }}</td>
                            <td>{{ $offer->price }}</td>
                            <td>{{ $offer->quantity }}</td>
                            <td>{{ $offer->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                <a class="edit" href="{{ route('marketplace.seller.products.edit', $offer->id) }}">Edit</a>
                                <form style="display:inline" method="POST" action="{{ route('marketplace.seller.products.destroy', $offer->id) }}" onsubmit="return confirm('Remove this offer?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="delete" type="submit">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div style="margin-top:16px;">{{ $offers->links() }}</div>
        @endif
    </main>
</body>
</html>
