{!! view_render_event('bagisto.shop.layout.header.before') !!}

<div class="hidden bg-brandNavy px-[60px] py-2 text-xs text-white/90 lg:flex lg:items-center lg:justify-between max-1180:px-8">
    <div class="flex items-center gap-5">
        <a href="{{ route('marketplace.seller.register.index') }}" class="hover:text-white">
            @lang('shop::app.components.layouts.header.utility.sell')
        </a>

        <a href="{{ route('shop.cms.page', 'help') }}" class="hover:text-white">
            @lang('shop::app.components.layouts.header.utility.help')
        </a>

        <a href="{{ route('shop.customers.account.orders.index') }}" class="hover:text-white">
            @lang('shop::app.components.layouts.header.utility.track-order')
        </a>
    </div>

    <button type="button" onclick="marketplaceOpenLocationModal()" class="flex items-center gap-1.5 hover:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>

        {{--
            Always starts as the static default and is hydrated by JS
            (location-modal.blade.php's updateLabels(), fetched via the
            uncached /customer-location endpoint) right after load - never
            rendered from session() directly here, since this page can be
            served from Bagisto's full-page cache (RESPONSE_CACHE_ENABLED),
            which is keyed by URL only, not by session. Baking a specific
            customer's location into the cached HTML would freeze it there
            for every other visitor of that same cached page.
        --}}
        <span id="marketplace-location-label-utility">
            @lang('shop::app.components.layouts.header.utility.deliver-to')
            <strong class="font-semibold" id="marketplace-location-label-utility-value">Set location</strong>
        </span>
    </button>
</div>

<button type="button" onclick="marketplaceOpenLocationModal()" class="flex items-center gap-1 border-b border-slate-100 px-4 py-1.5 text-xs text-slate-600 lg:hidden">
    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21c-4.5-4.5-7-8.25-7-11.5A7 7 0 0119 9.5c0 3.25-2.5 7-7 11.5z" /><circle cx="12" cy="9.5" r="2.25" /></svg>

    <span id="marketplace-location-label-mobile" class="truncate">
        Deliver to: <strong class="font-semibold text-brandNavy" id="marketplace-location-label-mobile-value">Set location</strong>
    </span>
</button>

@include('shop::components.layouts.header.location-modal')

<style>
    /* Thin, brand-colored scrollbar for the horizontally-scrolling category
       icon rows (desktop and mobile), instead of the default chunky OS
       scrollbar. Firefox + WebKit only - browsers without support just keep
       their native scrollbar, which is a fine fallback. */
    .marketplace-category-scroll {
        scrollbar-width: thin;
        scrollbar-color: #2FCB6E transparent;
    }

    .marketplace-category-scroll::-webkit-scrollbar {
        height: 5px;
    }

    .marketplace-category-scroll::-webkit-scrollbar-track {
        background: transparent;
    }

    .marketplace-category-scroll::-webkit-scrollbar-thumb {
        background-color: #2FCB6E;
        border-radius: 999px;
    }
</style>

<script>
    // A brief, one-time nudge-right-then-back on the horizontally-scrolling
    // category icon rows, so it's obvious there's more to scroll to without
    // permanently auto-cycling a navigation menu (which would fight the
    // user trying to actually pick a category with the mouse/finger).
    window.marketplacePeekScroll = function (el) {
        if (! el || el.scrollWidth <= el.clientWidth + 8) {
            return;
        }

        if (el.dataset.peeked) {
            return;
        }

        el.dataset.peeked = '1';

        setTimeout(function () {
            el.scrollTo({ left: 56, behavior: 'smooth' });

            setTimeout(function () {
                el.scrollTo({ left: 0, behavior: 'smooth' });
            }, 700);
        }, 600);
    };
</script>

@if(core()->getCurrentChannel()->locales()->count() > 1 || core()->getCurrentChannel()->currencies()->count() > 1 )
    <div class="max-lg:hidden">
        <x-shop::layouts.header.desktop.top />
    </div>
@endif

<header class="shadow-gray sticky top-0 z-10 bg-white shadow-sm max-lg:shadow-none">
    <v-header-switcher>
        <!-- Desktop Header Shimmer -->
        <div class="flex flex-wrap max-lg:hidden">
            <div class="flex min-h-[78px] w-full justify-between border border-b border-l-0 border-r-0 border-t-0 px-[60px] max-1180:px-8">
                <!-- Left Navigation Section -->
                <div class="flex items-center gap-x-10 max-[1180px]:gap-x-5">
                    <!-- Logo Shimmer -->
                    <span
                        class="shimmer block h-[29px] w-[131px] rounded"
                        role="presentation"
                    >
                    </span>

                    <!-- Categories Shimmer -->
                    <div class="flex items-center gap-5">
                        <span
                            class="shimmer h-6 w-20 rounded"
                            role="presentation"
                        >
                        </span>

                        <span
                            class="shimmer h-6 w-20 rounded"
                            role="presentation"
                        >
                        </span>

                        <span
                            class="shimmer h-6 w-20 rounded"
                            role="presentation"
                        >
                        </span>
                    </div>
                </div>

                <!-- Right Navigation Section -->
                <div class="flex items-center gap-x-9 max-[1100px]:gap-x-6 max-lg:gap-x-8">
                    <!-- Search Bar Shimmer -->
                    <div class="relative w-full max-w-[445px]">
                        <span
                            class="shimmer block h-[42px] w-[250px] rounded-lg px-11 py-3"
                            role="presentation"
                        >
                        </span>
                    </div>

                    <!-- Right Navigation Icons Shimmer -->
                    <div class="mt-1.5 flex gap-x-8 max-[1100px]:gap-x-6 max-lg:gap-x-8">
                        <!-- Compare Icon Shimmer -->
                        <span
                            class="shimmer h-6 w-6 rounded"
                            role="presentation"
                        >
                        </span>

                        <!-- Cart Icon Shimmer -->
                        <span
                            class="shimmer h-6 w-6 rounded"
                            role="presentation"
                        >
                        </span>

                        <!-- Profile Icon Shimmer -->
                        <span
                            class="shimmer h-6 w-6 rounded"
                            role="presentation"
                        >
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Header Shimmer -->
        <div class="flex flex-wrap gap-4 px-4 pb-4 pt-6 shadow-sm lg:hidden">
            <div class="flex w-full items-center justify-between">
                <!-- Left Navigation -->
                <div class="flex items-center gap-x-1.5">
                    <!-- Hamburger Menu Shimmer -->
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <!-- Logo Shimmer -->
                    <span 
                        class="shimmer block h-[29px] w-[131px] rounded" 
                        role="presentation"
                    >
                    </span>
                </div>

                <!-- Right Navigation Icons -->
                <div class="flex items-center gap-x-5 max-md:gap-x-4">
                    <!-- Compare Icon Shimmer -->
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <!-- Cart Icon Shimmer -->
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                    
                    <!-- Profile Icon Shimmer -->
                    <span 
                        class="shimmer block h-6 w-6 rounded" 
                        role="presentation"
                    >
                    </span>
                </div>
            </div>

            <!-- Search Bar Shimmer -->
            <div class="flex w-full items-center">
                <div class="relative w-full">
                    <span
                        class="shimmer block h-[42px] w-full rounded-xl px-11 py-3.5 max-md:rounded-lg"
                        role="presentation"
                    >
                    </span>
                </div>
            </div>
        </div>
    </v-header-switcher>
</header>

{!! view_render_event('bagisto.shop.layout.header.after') !!}

@pushOnce('scripts')
    <script 
        type="text/x-template" 
        id="v-header-switcher-template"
    >
        <v-desktop-header v-if="isDesktop"></v-desktop-header>
        
        <v-mobile-header v-else></v-mobile-header>
    </script>

    <script type="module">
        app.component('v-header-switcher', {
            template: '#v-header-switcher-template',

            data() {
                return {
                    isDesktop: window.innerWidth >= 1024
                }
            },

            mounted() {
                this.media = window.matchMedia('(min-width: 1024px)');

                this.media.addEventListener('change', this.handleMedia);
            },

            beforeUnmount() {
                this.media.removeEventListener('change', this.handleMedia);
            },

            methods: {
                handleMedia(e) {
                    this.isDesktop = e.matches;
                }
            }
        });

        app.component('v-desktop-header', {
            template: '#v-desktop-header-template'
        });

        app.component('v-mobile-header', {
            template: '#v-mobile-header-template'
        });
    </script>

    <script 
        type="text/x-template" 
        id="v-desktop-header-template"
    >
        <x-shop::layouts.header.desktop />
    </script>

    <script 
        type="text/x-template" 
        id="v-mobile-header-template"
    >
        <x-shop::layouts.header.mobile />
    </script>
@endPushOnce
