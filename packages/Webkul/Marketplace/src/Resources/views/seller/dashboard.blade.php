@include('marketplace::seller.partials.shell-start', ['title' => 'Dashboard', 'heading' => 'Dashboard', 'active' => 'dashboard'])

<style>
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    th { background: #f8fafc; color: #6b7280; font-weight: 600; font-size: 12px; text-transform: uppercase; }
    .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 12px; }
    .section-title { font-size: 15px; font-weight: 600; margin: 24px 0 10px; }
</style>

<div class="stat-cards">
    <div class="stat-card">
        <div class="label">Today's Sales</div>
        <div class="value">{{ core()->formatPrice($todaysSales) }}</div>
    </div>

    <div class="stat-card">
        <div class="label">Products Listed</div>
        <div class="value">{{ $products->total() }}</div>
    </div>

    <div class="stat-card">
        <div class="label">Low Stock (&le;5)</div>
        <div class="value">{{ $lowStockCount }}</div>
    </div>
</div>

<p class="section-title">Your Products</p>

@if ($products->isEmpty())
    <div class="empty">No products listed yet. <a href="{{ route('marketplace.seller.products.create') }}" style="color:#2FCB6E;">Add your first offer &rsaquo;</a></div>
@else
    <table>
        <thead>
            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Status</th></tr>
        </thead>
        <tbody>
            @foreach ($products as $offer)
                <tr>
                    <td>{{ $offer->product?->name ?? $offer->product?->sku }}</td>
                    <td>{{ core()->formatPrice($offer->price) }}</td>
                    <td>{{ $offer->quantity }}</td>
                    <td>{{ $offer->is_active ? 'Active' : 'Inactive' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top:16px;">{{ $products->links() }}</div>
@endif

@include('marketplace::seller.partials.shell-end')
