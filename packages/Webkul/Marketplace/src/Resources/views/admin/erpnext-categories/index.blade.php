<x-admin::layouts>
    <x-slot:title>
        ERPNext Categories - Marketplace
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            ERPNext Categories
        </p>

        <div class="flex gap-2">
            <form
                method="POST"
                action="{{ route('marketplace.admin.erpnext-categories.disable-non-api') }}"
                onsubmit="return confirm('Disable every category that is not synced from ERPNext? This only disables them (nothing is deleted) - they can be re-enabled from Catalog > Categories afterward.');"
            >
                @csrf
                <button type="submit" class="secondary-button">
                    Disable Non-ERPNext Categories
                </button>
            </form>

            <form method="POST" action="{{ route('marketplace.admin.erpnext-categories.sync') }}">
                @csrf
                <button type="submit" class="primary-button">
                    Sync Now
                </button>
            </form>
        </div>
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        The category tree synced from the connected ERPNext instance (Item Groups) - these are the same categories
        used across the storefront, filters, and product editing. Name and hierarchy are managed by ERPNext; only the
        Local Status column below is safe to change here, since a future sync will overwrite name/parent changes made
        any other way. ERPNext often includes internal/accounting Item Groups (assets, delivery fees, etc.) that
        aren't meant for customers - check the categories that should stay visible below and click "Keep Only Checked"
        to disable everything else in one go, rather than disabling dozens one at a time.
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

    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @if ($mappings->isEmpty())
            <p class="p-8 text-center text-gray-500 dark:text-gray-400">
                No ERPNext categories synced yet. Click "Sync Now" above, or run <code>php artisan erpnext:sync-categories</code>.
            </p>
        @else
            {{--
                One form wraps the whole table so the checkboxes can post
                together to "keep only checked". A <form> can't nest inside
                another, so each row's individual Enable/Disable button uses
                formaction to submit to a different route from within this
                same form instead of its own separate <form>.
            --}}
            <form method="POST" action="{{ route('marketplace.admin.erpnext-categories.keep-only-selected') }}">
                @csrf

                <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-4 py-3 dark:border-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Check every category that should stay visible to customers, then click "Keep Only Checked".
                    </p>

                    <button
                        type="submit"
                        class="primary-button"
                        onclick="return confirm('Disable every ERPNext category that is NOT checked? Checked ones stay visible. This only disables (nothing is deleted).');"
                    >
                        Keep Only Checked
                    </button>
                </div>

                <table class="min-w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                            <th class="px-4 py-3 font-semibold"></th>
                            <th class="px-4 py-3 font-semibold">Category</th>
                            <th class="px-4 py-3 font-semibold">External ID</th>
                            <th class="px-4 py-3 font-semibold">Parent (External ID)</th>
                            <th class="px-4 py-3 font-semibold">Last Synced</th>
                            <th class="px-4 py-3 font-semibold">Sync Status</th>
                            <th class="px-4 py-3 font-semibold">Local Status</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($mappings as $mapping)
                            <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                                <td class="px-4 py-3">
                                    @if ($mapping->category_id)
                                        <input
                                            type="checkbox"
                                            name="keep[]"
                                            value="{{ $mapping->category_id }}"
                                            {{ ! $mapping->is_disabled_locally ? 'checked' : '' }}
                                            class="h-4 w-4 rounded border-gray-300"
                                        />
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">
                                    {{ $mapping->category?->name ?? '— (ERPNext tree root, not a browsable category)' }}
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $mapping->external_id }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $mapping->external_parent_id ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $mapping->last_synced_at?->format('d M Y, H:i') ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        @class([
                                            'rounded-full px-2.5 py-1 text-xs font-medium',
                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $mapping->sync_status === 'synced',
                                            'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' => $mapping->sync_status === 'missing',
                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $mapping->sync_status === 'failed',
                                        ])
                                        title="{{ $mapping->sync_status === 'missing' ? 'ERPNext no longer returned this category in the last full sync - it has not been touched locally.' : '' }}"
                                    >
                                        {{ ucfirst($mapping->sync_status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($mapping->category_id)
                                        <span
                                            @class([
                                                'rounded-full px-2.5 py-1 text-xs font-medium',
                                                'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $mapping->is_disabled_locally,
                                                'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => ! $mapping->is_disabled_locally,
                                            ])
                                        >
                                            {{ $mapping->is_disabled_locally ? 'Disabled' : 'Enabled' }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($mapping->category_id)
                                        <button
                                            type="submit"
                                            formaction="{{ route('marketplace.admin.erpnext-categories.toggle-local', $mapping->id) }}"
                                            formmethod="POST"
                                            @class([
                                                'font-medium hover:underline',
                                                'text-green-600 dark:text-green-400' => $mapping->is_disabled_locally,
                                                'text-red-600 dark:text-red-400' => ! $mapping->is_disabled_locally,
                                            ])
                                        >
                                            {{ $mapping->is_disabled_locally ? 'Enable' : 'Disable' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </form>
        @endif
    </div>

    <div class="mt-4">
        {{ $mappings->links() }}
    </div>
</x-admin::layouts>
