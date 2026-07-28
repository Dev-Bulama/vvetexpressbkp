@include('marketplace::seller.partials.shell-start', ['title' => 'My Products', 'heading' => 'My Products', 'active' => 'products'])

<style>
    table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; }
    th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #e5e7eb; font-size: 14px; }
    th { background: #f8fafc; }
    .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 8px; }
    a.edit { color: #2FCB6E; text-decoration: none; margin-right: 10px; }
    button.delete { background: none; border: none; color: #dc2626; cursor: pointer; font-size: 14px; padding: 0; }
    .add-btn { display: inline-block; width: auto; margin: 0 0 16px; background: #2FCB6E; color: #fff; padding: 9px 16px; border-radius: 8px; font-size: 14px; font-weight: 600; }
</style>

<a class="add-btn" href="{{ route('marketplace.seller.products.create') }}">+ Add Product</a>

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
                    <td>{{ core()->formatPrice($offer->price) }}</td>
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

@include('marketplace::seller.partials.shell-end')
