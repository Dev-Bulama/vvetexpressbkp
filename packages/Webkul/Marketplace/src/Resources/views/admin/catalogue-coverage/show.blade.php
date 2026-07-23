<x-admin::layouts>
    <x-slot:title>
        {{ $seller->shop_name }} - Catalogue Coverage
    </x-slot>

    <p class="mb-3">
        <a
            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            href="{{ route('marketplace.admin.catalogue-coverage.index') }}"
        >
            &larr; Back to vendor catalogue coverage
        </a>
    </p>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md bg-red-100 px-4 py-3 text-sm text-red-700 dark:bg-red-900 dark:text-red-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xl font-bold text-gray-800 dark:text-white">{{ $seller->shop_name }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $seller->name }} &middot; {{ $seller->city }}</p>
        </div>

        <div class="flex gap-2">
            <form method="POST" action="{{ route('marketplace.admin.catalogue-coverage.remind', $seller->id) }}">
                @csrf
                <button
                    type="submit"
                    {{ $onCooldown ? 'disabled' : '' }}
                    class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Send Reminder
                </button>
            </form>

            <form method="POST" action="{{ route('marketplace.admin.catalogue-coverage.remind', $seller->id) }}">
                @csrf
                <input type="hidden" name="urgent" value="1">
                <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Send Urgent Reminder
                </button>
            </form>
        </div>
    </div>

    @if ($onCooldown)
        <div class="mb-4 rounded-md bg-yellow-100 px-4 py-3 text-sm text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
            A reminder was already sent recently - "Send Reminder" is on cooldown. Use "Send Urgent Reminder" to override.
        </div>
    @endif

    {{-- Catalogue summary --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
        @foreach ([
            ['label' => 'Coverage', 'value' => $coverage->coverage_percent.'%'],
            ['label' => 'Category Coverage', 'value' => $coverage->category_coverage_percent.'%'],
            ['label' => 'Stocked', 'value' => $coverage->stocked_products.' / '.$coverage->total_active_products],
            ['label' => 'Missing', 'value' => $coverage->missing_products],
            ['label' => 'Out of Stock', 'value' => $coverage->out_of_stock_products],
            ['label' => 'Low Stock', 'value' => $coverage->low_stock_products],
            ['label' => 'Last Inventory Update', 'value' => $coverage->last_inventory_update ? \Illuminate\Support\Carbon::parse($coverage->last_inventory_update)->diffForHumans() : 'Never'],
            ['label' => 'Eligible for Recommendations', 'value' => $coverage->eligible_for_recommendations ? 'Yes' : 'No'],
        ] as $card)
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($almostEligible->missed_match_count > 0)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950">
            <p class="font-semibold text-amber-900 dark:text-amber-200">Almost-Eligible Follow-Up</p>
            <p class="mt-1 text-sm text-amber-800 dark:text-amber-300">
                This vendor was close to qualifying for {{ $almostEligible->missed_match_count }} customer cart(s) in the last 90 days,
                representing an estimated {{ core()->formatPrice($almostEligible->estimated_lost_value) }} in missed order value.
            </p>
        </div>
    @endif

    {{-- Missing products --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <p class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">
            Missing Products ({{ $missingProducts->count() }})
        </p>

        @if ($missingProducts->isEmpty())
            <p class="p-4 text-sm text-gray-500 dark:text-gray-400">No missing products - this vendor stocks every active platform product.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3 font-semibold">SKU</th>
                        <th class="px-4 py-3 font-semibold">Product</th>
                        <th class="px-4 py-3 font-semibold">Demand</th>
                        <th class="px-4 py-3 font-semibold">Failed Cart Matches</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($missingProducts->take(30) as $product)
                        <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                            <td class="px-4 py-3">{{ $product->sku }}</td>
                            <td class="px-4 py-3">{{ $product->name }}</td>
                            <td class="px-4 py-3 capitalize">{{ $product->demand_level }}</td>
                            <td class="px-4 py-3">{{ $product->failed_cart_match_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mb-6 grid gap-6 md:grid-cols-2">
        {{-- Out of stock --}}
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <p class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">
                Out of Stock ({{ $outOfStockProducts->count() }})
            </p>

            @if ($outOfStockProducts->isEmpty())
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400">Nothing currently out of stock.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($outOfStockProducts as $offer)
                        <li class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $offer->product?->name ?? 'Product #'.$offer->product_id }}</li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Low stock --}}
        <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <p class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">
                Low Stock ({{ $lowStockProducts->count() }})
            </p>

            @if ($lowStockProducts->isEmpty())
                <p class="p-4 text-sm text-gray-500 dark:text-gray-400">Nothing currently low on stock.</p>
            @else
                <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($lowStockProducts as $offer)
                        <li class="flex justify-between px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <span>{{ $offer->product?->name ?? 'Product #'.$offer->product_id }}</span>
                            <span class="text-gray-400">{{ $offer->quantity }} left</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Reminder history --}}
    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <p class="border-b border-gray-200 px-4 py-3 font-semibold text-gray-800 dark:border-gray-800 dark:text-white">
            Reminder History ({{ $reminders->count() }})
        </p>

        @if ($reminders->isEmpty())
            <p class="p-4 text-sm text-gray-500 dark:text-gray-400">No reminders sent yet.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3 font-semibold">Type</th>
                        <th class="px-4 py-3 font-semibold">Channel</th>
                        <th class="px-4 py-3 font-semibold">Coverage at Send</th>
                        <th class="px-4 py-3 font-semibold">Missing Count</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($reminders as $reminder)
                        <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                            <td class="px-4 py-3">{{ $reminder->created_at->format('d M Y H:i') }}</td>
                            <td class="px-4 py-3 capitalize">{{ $reminder->type }}</td>
                            <td class="px-4 py-3 capitalize">{{ $reminder->channel }}</td>
                            <td class="px-4 py-3">{{ $reminder->coverage_percent_at_send }}%</td>
                            <td class="px-4 py-3">{{ $reminder->missing_products_count }}</td>
                            <td class="px-4 py-3 capitalize">{{ $reminder->delivery_status }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin::layouts>
