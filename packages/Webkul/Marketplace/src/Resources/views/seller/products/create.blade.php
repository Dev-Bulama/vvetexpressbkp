<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Add Product</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; }
        header { background: #11455B; color: #fff; padding: 16px 24px; }
        main { max-width: 700px; margin: 24px auto; padding: 0 16px; }
        .search { display: flex; gap: 8px; margin-bottom: 20px; }
        .search input { flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .search button { padding: 10px 16px; background: #11455B; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
        .result { background: #fff; border-radius: 8px; padding: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; }
        .result .info { font-size: 14px; }
        .result .sku { color: #6b7280; font-size: 12px; }
        .result form { display: flex; gap: 6px; align-items: center; }
        .result input { width: 80px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
        .result button { padding: 6px 12px; background: #2FCB6E; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
        .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }
        a.back { color: #2FCB6E; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <header>Add Product</header>

    <main>
        <p><a class="back" href="{{ route('marketplace.seller.products.index') }}">&larr; My Products</a></p>

        <form class="search" method="GET" action="{{ route('marketplace.seller.products.create') }}">
            <input type="text" name="q" value="{{ $search }}" placeholder="Search products by name or SKU...">
            <button type="submit">Search</button>
        </form>

        @if ($search && $results->isEmpty())
            <div class="empty">No products found for "{{ $search }}".</div>
        @endif

        @foreach ($results as $product)
            <div class="result">
                <div class="info">
                    <div>{{ $product->name }}</div>
                    <div class="sku">SKU: {{ $product->sku }}</div>
                </div>
                <form method="POST" action="{{ route('marketplace.seller.products.store') }}">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="number" step="0.01" min="0" name="price" placeholder="Price" required>
                    <input type="number" min="0" name="quantity" placeholder="Qty" required>
                    <button type="submit">Add</button>
                </form>
            </div>
        @endforeach
    </main>
</body>
</html>
