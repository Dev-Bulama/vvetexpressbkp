@component('shop::emails.layout')
    <div style="margin-bottom: 24px;">
        <p style="font-weight: bold;font-size: 20px;color: #121A26;line-height: 24px;margin-bottom: 24px">
            Hello, {{ $seller->name }} 👋
        </p>

        <p style="font-size: 16px;color: #384860;line-height: 24px;">
            Your VetExpress catalogue for <strong>{{ $seller->shop_name }}</strong> is currently incomplete.
            Because some required products are missing or unavailable, your store will not be recommended for
            customer orders containing those items. This may result in lost orders and reduced fulfilment
            opportunities. Please review the products listed below and update your inventory as soon as possible.
        </p>
    </div>

    <div style="margin-bottom: 24px; padding: 16px; border: 1px solid #E3E3E3; border-radius: 6px;">
        <p style="font-size: 14px; color: #6B7280; margin: 0 0 8px;">Reminder date: {{ now()->format('d M Y') }}</p>

        <table style="width: 100%; font-size: 14px; color: #384860;">
            <tr>
                <td style="padding: 4px 0;">Catalogue coverage</td>
                <td style="padding: 4px 0; text-align: right; font-weight: bold;">{{ $coverage->coverage_percent }}%</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">Missing products</td>
                <td style="padding: 4px 0; text-align: right; font-weight: bold;">{{ $coverage->missing_products }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">Out-of-stock products</td>
                <td style="padding: 4px 0; text-align: right; font-weight: bold;">{{ $coverage->out_of_stock_products }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0;">Low-stock products</td>
                <td style="padding: 4px 0; text-align: right; font-weight: bold;">{{ $coverage->low_stock_products }}</td>
            </tr>
        </table>
    </div>

    @if ($missingProducts->isNotEmpty())
        <p style="font-weight: bold; font-size: 16px; color: #121A26;">Missing products</p>

        <table style="width: 100%; font-size: 13px; color: #384860; border-collapse: collapse; margin-bottom: 24px;">
            <tr style="border-bottom: 1px solid #E3E3E3;">
                <td style="padding: 6px 4px; font-weight: bold;">SKU</td>
                <td style="padding: 6px 4px; font-weight: bold;">Product</td>
                <td style="padding: 6px 4px; font-weight: bold;">Demand</td>
            </tr>
            @foreach ($missingProducts->take(15) as $product)
                <tr style="border-bottom: 1px solid #F1F1F1;">
                    <td style="padding: 6px 4px;">{{ $product->sku }}</td>
                    <td style="padding: 6px 4px;">{{ $product->name }}</td>
                    <td style="padding: 6px 4px; text-transform: capitalize;">{{ $product->demand_level }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($outOfStockProducts->isNotEmpty())
        <p style="font-weight: bold; font-size: 16px; color: #121A26;">Out of stock</p>

        <table style="width: 100%; font-size: 13px; color: #384860; border-collapse: collapse; margin-bottom: 24px;">
            @foreach ($outOfStockProducts->take(15) as $offer)
                <tr style="border-bottom: 1px solid #F1F1F1;">
                    <td style="padding: 6px 4px;">{{ $offer->product?->sku }}</td>
                    <td style="padding: 6px 4px;">{{ $offer->product?->name }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div style="margin: 24px 0; padding: 16px; background: #FFF7ED; border-radius: 6px;">
        <p style="font-weight: bold; font-size: 14px; color: #9A3412; margin: 0 0 8px;">What this means for {{ $seller->shop_name }}</p>

        <ul style="font-size: 13px; color: #9A3412; margin: 0; padding-left: 18px;">
            <li>You will not be recommended for customer orders requiring these missing products.</li>
            <li>Customers cannot purchase from your store when your complete cart isn't available.</li>
            <li>You may lose sales opportunities to nearby vendors who can fulfil the same order.</li>
            <li>Repeated stock gaps reduce your fulfilment performance visible to VetExpress admin.</li>
            <li>Completing your catalogue improves your recommendation eligibility and order opportunities.</li>
        </ul>
    </div>

    <div style="display: flex;margin-bottom: 24px">
        <a
            href="{{ route('marketplace.seller.products.index') }}"
            style="padding: 16px 45px;justify-content: center;align-items: center;gap: 10px;border-radius: 2px;background: #11455B;color: #FFFFFF;text-decoration: none;text-transform: uppercase;font-weight: 700;"
        >
            Update Your Inventory
        </a>
    </div>
@endcomponent
