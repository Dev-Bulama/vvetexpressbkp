<x-admin::layouts>
    <x-slot:title>
        {{ $seller->shop_name }} - Marketplace
    </x-slot>

    <p class="mb-3">
        <a
            class="text-sm font-medium text-blue-600 hover:underline dark:text-blue-400"
            href="{{ route('marketplace.admin.sellers.index') }}"
        >
            &larr; Back to sellers
        </a>
    </p>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-100 px-4 py-3 text-sm text-green-700 dark:bg-green-900 dark:text-green-200">
            {{ session('success') }}
        </div>
    @endif

    <div class="max-w-xl rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900">
        <dl class="flex flex-col gap-3 text-sm">
            @php
                $rows = [
                    'Shop Name' => $seller->shop_name,
                    'Owner' => $seller->name,
                    'Email' => $seller->email,
                    'Phone' => $seller->phone ?? '—',
                    'Address' => $seller->address ?? '—',
                    'City' => $seller->city ?? '—',
                    'Coordinates' => ($seller->latitude ?? '—').', '.($seller->longitude ?? '—'),
                ];
            @endphp

            @foreach ($rows as $label => $value)
                <div class="flex items-center gap-4">
                    <dt class="w-36 shrink-0 font-semibold text-gray-600 dark:text-gray-300">{{ $label }}</dt>
                    <dd class="text-gray-800 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach

            <div class="flex items-center gap-4">
                <dt class="w-36 shrink-0 font-semibold text-gray-600 dark:text-gray-300">Status</dt>
                <dd>
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
                </dd>
            </div>

            <div class="flex items-center gap-4">
                <dt class="w-36 shrink-0 font-semibold text-gray-600 dark:text-gray-300">Joined</dt>
                <dd class="text-gray-800 dark:text-white">{{ $seller->created_at->format('d M Y') }}</dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-2.5">
            @if ($seller->status !== 'approved')
                <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        Approve
                    </button>
                </form>
            @endif

            @if ($seller->status !== 'suspended')
                <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="suspended">
                    <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                        Suspend
                    </button>
                </form>
            @endif

            @if ($seller->status !== 'pending')
                <form method="POST" action="{{ route('marketplace.admin.sellers.update-status', $seller->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="rounded-md bg-yellow-600 px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700">
                        Reset to Pending
                    </button>
                </form>
            @endif
        </div>
    </div>
</x-admin::layouts>
