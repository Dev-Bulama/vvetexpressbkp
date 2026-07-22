<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Offer</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { font-family: system-ui, sans-serif; background: #f4f5f7; margin: 0; padding: 40px 16px; }
        .card { max-width: 420px; margin: 0 auto; background: #fff; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .sku { color: #6b7280; font-size: 13px; margin-bottom: 20px; }
        label { display: block; font-size: 13px; font-weight: 600; margin: 16px 0 4px; }
        input[type=number] { width: 100%; box-sizing: border-box; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        label.checkbox { display: flex; align-items: center; gap: 8px; font-weight: normal; }
        button { width: 100%; margin-top: 24px; padding: 12px; background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; }
        a.back { color: #16a34a; text-decoration: none; font-size: 13px; }
    </style>
</head>
<body>
    <p style="max-width:420px;margin:0 auto 12px;"><a class="back" href="{{ route('marketplace.seller.products.index') }}">&larr; My Products</a></p>

    <div class="card">
        <h1>{{ $offer->product?->name }}</h1>
        <div class="sku">SKU: {{ $offer->product?->sku }}</div>

        <form method="POST" action="{{ route('marketplace.seller.products.update', $offer->id) }}">
            @csrf
            @method('PUT')

            <label>Price</label>
            <input type="number" step="0.01" min="0" name="price" value="{{ $offer->price }}" required>

            <label>Quantity</label>
            <input type="number" min="0" name="quantity" value="{{ $offer->quantity }}" required>

            <label class="checkbox" style="margin-top:16px;">
                <input type="checkbox" name="is_active" value="1" {{ $offer->is_active ? 'checked' : '' }}>
                Active (visible to customers)
            </label>

            <button type="submit">Save</button>
        </form>
    </div>
</body>
</html>
