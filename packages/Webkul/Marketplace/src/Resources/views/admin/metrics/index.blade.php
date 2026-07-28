<x-admin::layouts>
    <x-slot:title>
        Metrics
    </x-slot>

    <div class="flex items-center justify-between gap-4 max-sm:flex-wrap">
        <p class="py-3 text-xl font-bold text-gray-800 dark:text-white">
            Metrics
        </p>

        <div class="flex gap-2">
            @foreach (['7d' => '7 days', '30d' => '30 days', '90d' => '90 days', 'all' => 'All time'] as $value => $label)
                <a
                    href="{{ route('marketplace.admin.metrics.index', ['period' => $value]) }}"
                    class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all
                        {{ $period === $value ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' }}"
                >
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
        Every metric that can be computed from real, already-recorded data (orders, vendors, deliveries, catalogue).
        Metrics needing tracking this system doesn't yet have (ad spend, support tickets, uptime monitoring, etc.)
        are shown as <span class="font-medium">Not yet tracked</span> rather than a made-up number.
    </p>

    {{-- North Stars --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ([$northStars->gmv, $northStars->repeat_purchase_rate, $northStars->vendor_sla_adherence] as $star)
            <div class="rounded-lg border-2 border-blue-200 bg-blue-50 p-5 dark:border-blue-900 dark:bg-blue-950/40">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">North Star &middot; {{ $star->description }}</p>
                <p class="mt-1 text-lg font-bold text-gray-800 dark:text-white">{{ $star->label }}</p>
                <p class="mt-2 text-2xl font-bold text-blue-700 dark:text-blue-300">
                    {{ $star->tracked ? $star->value : 'N/A' }}
                </p>
                @if (! $star->tracked)
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $star->note }}</p>
                @endif
            </div>
        @endforeach
    </div>

    @php
        $departmentColors = [
            'Commercial' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            'Operations & Supply' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-200',
            'Risk & Trust' => 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-200',
            'Enablement' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200',
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @foreach ($plots as $plot)
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-3 flex items-center justify-between gap-2">
                    <p class="font-semibold text-gray-800 dark:text-white">Plot {{ $plot->number }} &middot; {{ $plot->title }}</p>

                    <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium {{ $departmentColors[$plot->department] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ $plot->department }}
                    </span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($plot->metrics as $metric)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200">{{ $metric->label }}</p>
                                <p class="text-xs text-gray-400">{{ $metric->frequency }}</p>
                            </div>

                            <div class="shrink-0 text-right">
                                @if ($metric->tracked)
                                    <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $metric->value }}</p>
                                @else
                                    <p class="text-xs italic text-gray-400" title="{{ $metric->note }}">Not yet tracked</p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-admin::layouts>
