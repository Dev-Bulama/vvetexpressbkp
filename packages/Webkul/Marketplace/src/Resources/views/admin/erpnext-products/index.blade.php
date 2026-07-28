<x-admin::layouts>
    <x-slot:title>
        ERPNext Products - Marketplace
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            ERPNext Products
        </p>
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Every product synced in from the external ERPNext instance. Hide any confidential or internal-only item from
        the public storefront without removing it from the catalog - hidden items stay hidden through future syncs
        until you make them visible again here.
    </p>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @if ($mappings->isEmpty())
            <p class="p-8 text-center text-gray-500 dark:text-gray-400">No ERPNext-synced products yet.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3 font-semibold">SKU</th>
                        <th class="px-4 py-3 font-semibold">Product</th>
                        <th class="px-4 py-3 font-semibold">Item Code</th>
                        <th class="px-4 py-3 font-semibold">Last Synced</th>
                        <th class="px-4 py-3 font-semibold">Visibility</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($mappings as $mapping)
                        <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                            <td class="px-4 py-3">{{ $mapping->product?->sku }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $mapping->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $mapping->item_code }}</td>
                            <td class="px-4 py-3">{{ $mapping->last_synced_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $mapping->is_hidden_from_public,
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => ! $mapping->is_hidden_from_public,
                                    ])
                                >
                                    {{ $mapping->is_hidden_from_public ? 'Hidden' : 'Public' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id) }}">
                                    @csrf

                                    @if ($mapping->is_hidden_from_public)
                                        <button type="submit" class="font-medium text-green-600 hover:underline dark:text-green-400">
                                            Make Public
                                        </button>
                                    @else
                                        <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-400">
                                            Hide From Public
                                        </button>
                                    @endif
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $mappings->links() }}
    </div>
</x-admin::layouts>
