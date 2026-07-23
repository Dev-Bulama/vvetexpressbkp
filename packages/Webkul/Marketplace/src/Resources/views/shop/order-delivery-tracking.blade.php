@php
    $deliveries = \Webkul\Marketplace\Models\Delivery::where('order_id', $order->id)->with('seller')->get();
@endphp

@if ($deliveries->isNotEmpty())
    <div class="mb-4 flex flex-col gap-2 rounded-xl border border-brandGreen/30 bg-brandGreen/5 p-4">
        @foreach ($deliveries as $delivery)
            <a
                href="{{ route('marketplace.tracking.show', $delivery->id) }}"
                class="flex items-center justify-between gap-3 text-sm font-medium text-brandNavy hover:underline"
            >
                <span>Track delivery from {{ $delivery->seller->shop_name }} &middot; {{ str_replace('_', ' ', ucfirst($delivery->status)) }}</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        @endforeach
    </div>
@endif
