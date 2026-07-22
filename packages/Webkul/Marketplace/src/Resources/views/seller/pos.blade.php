@include('marketplace::seller.partials.shell-start', ['title' => 'Point of Sale', 'heading' => 'Point of Sale', 'active' => 'pos'])

<style>
    .pos-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; align-items: start; }

    .search-bar { margin-bottom: 16px; }
    .search-bar input { width: 100%; box-sizing: border-box; padding: 11px 14px; border: 1px solid #d1d5db; border-radius: 10px; font-size: 14px; }

    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
    .product-tile { background: #fff; border-radius: 12px; padding: 14px; box-shadow: 0 1px 3px rgba(0,0,0,.06); cursor: pointer; border: 2px solid transparent; transition: border-color .15s; }
    .product-tile:hover { border-color: #2FCB6E; }
    .product-tile.out-of-stock { opacity: .45; cursor: not-allowed; }
    .product-tile .thumb { height: 70px; border-radius: 8px; background: #f1f5f9; display: flex; align-items: center; justify-content: center; color: #94a3b8; margin-bottom: 8px; }
    .product-tile .name { font-size: 13px; font-weight: 600; color: #1f2937; line-height: 1.3; margin-bottom: 4px; }
    .product-tile .price { font-size: 13px; font-weight: 700; color: #2FCB6E; }
    .product-tile .stock { font-size: 11px; color: #9ca3af; margin-top: 2px; }
    .empty { padding: 24px; text-align: center; color: #6b7280; background: #fff; border-radius: 12px; }

    .cart-panel { background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06); display: flex; flex-direction: column; max-height: calc(100vh - 130px); position: sticky; top: 20px; }
    .cart-panel .cart-header { padding: 16px 18px; border-bottom: 1px solid #e5e7eb; font-weight: 700; }
    .cart-items { padding: 8px 18px; overflow-y: auto; flex: 1; }
    .cart-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; gap: 8px; }
    .cart-item .info { min-width: 0; }
    .cart-item .info .name { font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 140px; }
    .cart-item .info .unit-price { font-size: 12px; color: #6b7280; }
    .cart-item .qty { display: flex; align-items: center; gap: 6px; }
    .cart-item .qty button { width: 22px; height: 22px; border-radius: 6px; border: 1px solid #d1d5db; background: #f9fafb; cursor: pointer; font-size: 13px; line-height: 1; }
    .cart-item .qty span { min-width: 16px; text-align: center; font-size: 13px; }
    .cart-item .remove { color: #ef4444; cursor: pointer; font-size: 12px; margin-left: 4px; }
    .cart-empty { padding: 24px 0; text-align: center; color: #9ca3af; font-size: 13px; }

    .cart-footer { padding: 16px 18px; border-top: 1px solid #e5e7eb; }
    .cart-footer label { display: block; font-size: 12px; font-weight: 600; margin: 10px 0 4px; color: #374151; }
    .cart-footer input, .cart-footer select { width: 100%; box-sizing: border-box; padding: 8px 10px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 13px; }
    .cart-total { display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; margin: 14px 0; color: #11455B; }
    .charge-btn { width: 100%; padding: 13px; background: #2FCB6E; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 700; cursor: pointer; }
    .charge-btn:disabled { background: #cbd5e1; cursor: not-allowed; }
    .charge-btn:not(:disabled):hover { opacity: .92; }

    .receipt-msg { display: none; margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: #dcfce7; color: #166534; font-size: 13px; }

    .recent-sales { margin-top: 24px; }
    .recent-sales table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.06); }
    .recent-sales th, .recent-sales td { text-align: left; padding: 9px 14px; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
    .recent-sales th { background: #f8fafc; color: #6b7280; font-weight: 600; font-size: 11px; text-transform: uppercase; }
</style>

<div class="pos-layout">
    <div>
        <div class="search-bar">
            <form method="GET" action="{{ route('marketplace.seller.pos.index') }}">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search your products by name or SKU..." onchange="this.form.submit()">
            </form>
        </div>

        @if ($offers->isEmpty())
            <div class="empty">No active offers to sell. <a href="{{ route('marketplace.seller.products.create') }}" style="color:#2FCB6E;">Add products &rsaquo;</a></div>
        @else
            <div class="product-grid" id="product-grid">
                @foreach ($offers as $offer)
                    <div
                        class="product-tile {{ $offer->quantity <= 0 ? 'out-of-stock' : '' }}"
                        data-id="{{ $offer->id }}"
                        data-name="{{ $offer->product?->name ?? $offer->product?->sku }}"
                        data-price="{{ (float) $offer->price }}"
                        data-stock="{{ $offer->quantity }}"
                        onclick="pos.addToCart(this)"
                    >
                        <div class="thumb">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4zm4 4h8v8H8V8z" /></svg>
                        </div>
                        <div class="name">{{ $offer->product?->name ?? $offer->product?->sku }}</div>
                        <div class="price">{{ core()->formatPrice($offer->price) }}</div>
                        <div class="stock">{{ $offer->quantity > 0 ? $offer->quantity.' in stock' : 'Out of stock' }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="recent-sales">
            <p class="section-title" style="font-size:15px;font-weight:600;margin:24px 0 10px;">Recent Sales</p>

            @if ($recentSales->isEmpty())
                <div class="empty">No sales recorded yet today.</div>
            @else
                <table>
                    <thead>
                        <tr><th>Reference</th><th>Total</th><th>Payment</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->reference }}</td>
                                <td>{{ core()->formatPrice($sale->total) }}</td>
                                <td>{{ ucfirst($sale->payment_method) }}</td>
                                <td>{{ $sale->created_at->format('H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    <div class="cart-panel">
        <div class="cart-header">Current Sale</div>

        <div class="cart-items" id="cart-items">
            <div class="cart-empty" id="cart-empty">Tap a product to add it to the sale.</div>
        </div>

        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span id="cart-total">{{ core()->getCurrentCurrency()->symbol ?? '' }}0.00</span>
            </div>

            <label>Customer name (optional)</label>
            <input type="text" id="customer-name" placeholder="Walk-in customer">

            <label>Payment method</label>
            <select id="payment-method">
                <option value="cash">Cash</option>
                <option value="card">Card</option>
                <option value="transfer">Bank Transfer</option>
            </select>

            <button class="charge-btn" id="charge-btn" onclick="pos.charge()" disabled>Complete Sale</button>

            <div class="receipt-msg" id="receipt-msg"></div>
        </div>
    </div>
</div>

<script>
    const posChargeUrl = "{{ route('marketplace.seller.pos.charge') }}";
    const posCurrencySymbol = "{{ core()->getCurrentCurrency()->symbol ?? '' }}";

    const pos = {
        cart: {},

        addToCart(el) {
            const stock = parseInt(el.dataset.stock, 10);

            if (stock <= 0) {
                return;
            }

            const id = el.dataset.id;

            if (! this.cart[id]) {
                this.cart[id] = {
                    id,
                    name: el.dataset.name,
                    price: parseFloat(el.dataset.price),
                    stock,
                    quantity: 0,
                };
            }

            if (this.cart[id].quantity < stock) {
                this.cart[id].quantity += 1;
            }

            this.render();
        },

        changeQuantity(id, delta) {
            const item = this.cart[id];

            if (! item) return;

            item.quantity += delta;

            if (item.quantity <= 0) {
                delete this.cart[id];
            } else if (item.quantity > item.stock) {
                item.quantity = item.stock;
            }

            this.render();
        },

        removeItem(id) {
            delete this.cart[id];
            this.render();
        },

        render() {
            const items = Object.values(this.cart);
            const container = document.getElementById('cart-items');
            const emptyState = document.getElementById('cart-empty');
            const chargeBtn = document.getElementById('charge-btn');

            container.querySelectorAll('.cart-item').forEach(node => node.remove());

            let total = 0;

            items.forEach(item => {
                total += item.price * item.quantity;

                const row = document.createElement('div');
                row.className = 'cart-item';
                row.innerHTML = `
                    <div class="info">
                        <div class="name">${item.name}</div>
                        <div class="unit-price">${posCurrencySymbol}${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="qty">
                        <button type="button" onclick="pos.changeQuantity('${item.id}', -1)">-</button>
                        <span>${item.quantity}</span>
                        <button type="button" onclick="pos.changeQuantity('${item.id}', 1)">+</button>
                        <span class="remove" onclick="pos.removeItem('${item.id}')">&times;</span>
                    </div>
                `;
                container.appendChild(row);
            });

            emptyState.style.display = items.length ? 'none' : 'block';
            chargeBtn.disabled = items.length === 0;
            document.getElementById('cart-total').textContent = posCurrencySymbol + total.toFixed(2);
        },

        async charge() {
            const items = Object.values(this.cart).map(item => ({ offer_id: item.id, quantity: item.quantity }));

            if (! items.length) return;

            const chargeBtn = document.getElementById('charge-btn');
            chargeBtn.disabled = true;
            chargeBtn.textContent = 'Processing...';

            try {
                const response = await fetch(posChargeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        items,
                        customer_name: document.getElementById('customer-name').value,
                        payment_method: document.getElementById('payment-method').value,
                    }),
                });

                if (! response.ok) {
                    const error = await response.json();
                    throw new Error(error.message || 'Sale failed.');
                }

                const data = await response.json();

                const receipt = document.getElementById('receipt-msg');
                receipt.style.display = 'block';
                receipt.textContent = `Sale ${data.reference} completed - ${data.total}`;

                this.cart = {};
                this.render();
                document.getElementById('customer-name').value = '';

                setTimeout(() => window.location.reload(), 1500);
            } catch (e) {
                alert(e.message);
                chargeBtn.disabled = false;
                chargeBtn.textContent = 'Complete Sale';
            }
        },
    };
</script>

@include('marketplace::seller.partials.shell-end')
