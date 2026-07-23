<x-admin::layouts>
    <x-slot:title>
        Marketplace Sellers
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            Sellers
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        @php
            $tabs = [
                null => 'All',
                'pending' => 'Pending',
                'approved' => 'Approved',
                'suspended' => 'Suspended',
            ];
        @endphp

        @foreach ($tabs as $value => $label)
            <a
                href="{{ route('marketplace.admin.sellers.index', $value ? ['status' => $value] : []) }}"
                class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all
                    {{ $status === $value ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
        @if ($sellers->isEmpty())
            <p class="p-8 text-center text-gray-500 dark:text-gray-400">No sellers found.</p>
        @else
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-gray-600 dark:border-gray-800 dark:text-gray-300">
                        <th class="px-4 py-3 font-semibold">Shop</th>
                        <th class="px-4 py-3 font-semibold">Owner</th>
                        <th class="px-4 py-3 font-semibold">Email</th>
                        <th class="px-4 py-3 font-semibold">City</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($sellers as $seller)
                        <tr class="border-b border-gray-100 text-gray-700 last:border-0 dark:border-gray-800 dark:text-gray-300">
                            <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">{{ $seller->shop_name }}</td>
                            <td class="px-4 py-3">{{ $seller->name }}</td>
                            <td class="px-4 py-3">{{ $seller->email }}</td>
                            <td class="px-4 py-3">{{ $seller->city }}</td>
                            <td class="px-4 py-3">
                                <span
                                    @class([
                                        'rounded-full px-2.5 py-1 text-xs font-medium',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' => $seller->status === 'pending',
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' => $seller->status === 'approved',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' => $seller->status === 'suspended',
                                    ])
                                >
                                    {{ ucfirst($seller->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    class="font-medium text-blue-600 hover:underline dark:text-blue-400"
                                    href="{{ route('marketplace.admin.sellers.edit', $seller->id) }}"
                                >
                                    Manage
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">
        {{ $sellers->links() }}
    </div>
</x-admin::layouts>
