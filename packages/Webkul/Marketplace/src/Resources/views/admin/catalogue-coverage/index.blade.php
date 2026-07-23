<x-admin::layouts>
    <x-slot:title>
        Vendor Catalogue Coverage
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            Vendor Catalogue Coverage
        </p>
    </div>

    <p class="mb-4 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
        Every vendor's coverage of the active platform catalogue. This is separate from per-order eligibility - a
        vendor can stock only part of the catalogue and still be fully eligible for orders it can completely fulfil.
    </p>

    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total Vendors', 'value' => $summary['total']],
            ['label' => 'Complete', 'value' => $summary['complete'], 'accent' => 'text-green-600 dark:text-green-400'],
            ['label' => 'Incomplete', 'value' => $summary['incomplete'], 'accent' => 'text-amber-600 dark:text-amber-400'],
            ['label' => 'Below '.$threshold.'% Coverage', 'value' => $summary['below_threshold'], 'accent' => 'text-red-600 dark:text-red-400'],
            ['label' => 'No Update in 14d', 'value' => $summary['no_recent_update']],
            ['label' => 'Failed Cart Matches (30d)', 'value' => $summary['failed_cart_matches_30d']],
        ] as $card)
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $card['accent'] ?? 'text-gray-800 dark:text-white' }}">{{ $card['value'] }}</p>
            </div>
        @endforeach
    </div>

    @if ($mostCommonMissing->isNotEmpty())
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <p class="mb-3 font-semibold text-gray-800 dark:text-white">Most Commonly Missing Products</p>

            <div class="flex flex-wrap gap-2">
                @foreach ($mostCommonMissing as $row)
                    <span class="rounded-full bg-red-50 px-3 py-1 text-xs font-medium text-red-700 dark:bg-red-900 dark:text-red-200">
                        {{ $row['product']->name }} &middot; missing from {{ $row['vendor_count'] }} vendor{{ $row['vendor_count'] === 1 ? '' : 's' }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        <table class="min-w-full text-left text-sm">
            <thead>
                <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                    <th class="px-4 py-3 font-semibold">Vendor</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Coverage</th>
                    <th class="px-4 py-3 font-semibold">Missing</th>
                    <th class="px-4 py-3 font-semibold">Out of Stock</th>
                    <th class="px-4 py-3 font-semibold">Low Stock</th>
                    <th class="px-4 py-3 font-semibold">Last Update</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>

            <tbody>
                @foreach ($vendors as $row)
                    <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                        <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $row->seller->shop_name }}</td>
                        <td class="px-4 py-3">
                            <span
                                @class([
                                    'rounded-full px-2.5 py-1 text-xs font-medium capitalize',
                                    'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $row->status === 'complete',
                                    'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => in_array($row->status, ['low_stock', 'requires_attention']),
                                    'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => in_array($row->status, ['incomplete', 'out_of_stock']),
                                    'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' => $row->status === 'inactive',
                                ])
                            >
                                {{ str_replace('_', ' ', $row->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $row->coverage_percent }}%</td>
                        <td class="px-4 py-3">{{ $row->missing_products }}</td>
                        <td class="px-4 py-3">{{ $row->out_of_stock_products }}</td>
                        <td class="px-4 py-3">{{ $row->low_stock_products }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                            {{ $row->last_inventory_update ? \Illuminate\Support\Carbon::parse($row->last_inventory_update)->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a
                                class="font-medium text-blue-600 hover:underline dark:text-blue-400"
                                href="{{ route('marketplace.admin.catalogue-coverage.show', $row->seller->id) }}"
                            >
                                View
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin::layouts>
