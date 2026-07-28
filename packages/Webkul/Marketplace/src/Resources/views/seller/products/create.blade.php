@include('marketplace::seller.partials.shell-start', ['title' => 'Add Product', 'heading' => 'Add Product', 'active' => 'products'])

<style>
    .search { display: flex; gap: 8px; margin-bottom: 20px; }
    .search input { flex: 1; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; box-sizing: border-box; }
    .search button { padding: 10px 16px; background: #11455B; color: #fff; border: none; border-radius: 8px; cursor: pointer; }
    .result { background: #fff; border-radius: 8px; padding: 14px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
    .result .info { font-size: 14px; }
    .result .sku { color: #6b7280; font-size: 12px; }
    .result form { display: flex; gap: 6px; align-items: center; }
    .result input { width: 80px; padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px; }
    .result button { padding: 6px 12px; background: #2FCB6E; color: #fff; border: none; border-radius: 6px; font-size: 13px; cursor: pointer; }
    .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }

    @media (max-width: 480px) {
        .result { flex-direction: column; align-items: stretch; }
        .result form { flex-wrap: wrap; }
        .result input { width: auto; flex: 1; }
    }
</style>

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

@include('marketplace::seller.partials.shell-end')
