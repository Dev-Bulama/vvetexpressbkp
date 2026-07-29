<x-admin::layouts>
    <x-slot:title>
        ERPNext Products - Marketplace
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            ERPNext Products
        </p>

        @if ($uncategorizedCount > 0)
            <a
                href="{{ route('marketplace.admin.erpnext-products.index', ['uncategorized' => $uncategorizedOnly ? null : 1]) }}"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all
                    {{ $uncategorizedOnly ? 'bg-blue-600 text-white' : 'bg-amber-100 text-amber-800 hover:bg-amber-200 dark:bg-amber-900 dark:text-amber-200' }}"
            >
                {{ $uncategorizedOnly ? 'Showing uncategorized only - click to show all' : "{$uncategorizedCount} uncategorized - click to filter" }}
            </a>
        @endif
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Every product synced in from the external ERPNext instance. Only listings with a real price and at least one
        photo show to customers automatically - an incomplete item is hidden until it's fixed in ERPNext, unless you
        override that below. Hide any confidential or internal-only item from the public storefront without removing
        it from the catalog either way - your decision stays through future syncs until you change it again here.
        @if ($uncategorizedCount > 0)
            <span class="font-medium text-amber-700 dark:text-amber-400">{{ $uncategorizedCount }} {{ $uncategorizedCount === 1 ? 'product has' : 'products have' }} no ERPNext category assigned yet, so they won't appear when a customer browses by category - set the item's Item Group in ERPNext and re-run the sync to fix this.</span>
        @endif
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
                        <th class="px-4 py-3 font-semibold">Image</th>
                        <th class="px-4 py-3 font-semibold">SKU</th>
                        <th class="px-4 py-3 font-semibold">Product</th>
                        <th class="px-4 py-3 font-semibold">Item Code</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                        <th class="px-4 py-3 font-semibold">Last Synced</th>
                        <th class="px-4 py-3 font-semibold">Visibility</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($mappings as $mapping)
                        @php $image = $mapping->product?->images?->first(); @endphp
                        <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                            <td class="px-4 py-3">
                                @if ($image)
                                    <img src="{{ $image->url }}" alt="{{ $mapping->product?->name }}" class="h-12 w-12 rounded-md object-cover" />
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-md bg-gray-100 text-[10px] text-gray-400 dark:bg-gray-800">No image</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $mapping->product?->sku }}</td>
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $mapping->product?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $mapping->item_code }}</td>
                            <td class="px-4 py-3">
                                @php $category = $mapping->product?->categories?->first(); @endphp
                                @if ($category)
                                    <span class="text-gray-700 dark:text-gray-300">{{ $category->name }}</span>
                                @else
                                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-200">
                                        Uncategorized
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $mapping->last_synced_at?->format('d M Y, H:i') ?? '—' }}</td>
                            <td class="px-4 py-3">
                                @php
                                    $isPublic = (bool) $mapping->product?->status;
                                    $isAutoDecided = is_null($mapping->visibility_override);
                                @endphp
                                <span
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => ! $isPublic,
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $isPublic,
                                    ])
                                    title="{{ $isAutoDecided ? ($isPublic ? 'Automatically public - has a real price and a photo.' : 'Automatically hidden - missing a real price or a photo.') : 'Manually set by an admin.' }}"
                                >
                                    {{ $isPublic ? 'Public' : 'Hidden' }}{{ $isAutoDecided ? ' (auto)' : '' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('marketplace.admin.erpnext-products.toggle-visibility', $mapping->id) }}">
                                    @csrf

                                    @if ($isPublic)
                                        <button type="submit" class="font-medium text-red-600 hover:underline dark:text-red-400">
                                            Hide From Public
                                        </button>
                                    @else
                                        <button type="submit" class="font-medium text-green-600 hover:underline dark:text-green-400">
                                            Make Public
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
