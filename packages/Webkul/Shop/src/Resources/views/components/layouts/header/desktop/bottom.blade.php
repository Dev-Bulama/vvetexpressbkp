{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.before') !!}

@php
    $headerCategories = \Webkul\Category\Repositories\CategoryRepository::class;
    $topLevelCategories = app($headerCategories)->getVisibleCategoryTree(core()->getCurrentChannel()->root_category_id);
@endphp

<div class="flex min-h-[78px] w-full items-center justify-between gap-6 border border-b-0 border-l-0 border-r-0 border-t-0 px-[60px] max-1180:gap-4 max-1180:px-8">
    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.before') !!}

    <a
        href="{{ route('shop.home.index') }}"
        aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.bagisto')"
        class="shrink-0"
    >
        <img
            src="{{ core()->getCurrentChannel()->logo_url ?? asset('vetexpress/logo.svg') }}"
            width="140"
            height="32"
            alt="{{ config('app.name') }}"
        >
    </a>

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.logo.after') !!}

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.before') !!}

    <!-- Mega Search Bar -->
    <div class="hidden min-w-0 flex-1 md:block">
        <form
            action="{{ route('shop.search.index') }}"
            method="GET"
            class="relative flex w-full items-stretch overflow-hidden rounded-lg border border-slate-200 focus-within:border-brandGreen"
            role="search"
            toolname="search_products"
            tooldescription="{{ trans('shop::app.components.layouts.webmcp.search-products') }}"
            toolautosubmit
        >
            <label for="header-category-select" class="sr-only">
                @lang('shop::app.components.layouts.header.desktop.bottom.category-select')
            </label>

            <select
                id="header-category-select"
                name="category_id"
                class="shrink-0 border-0 border-r border-slate-200 bg-slate-50 px-3 text-xs font-medium text-slate-600 focus:outline-none focus:ring-0 max-w-[160px]"
            >
                <option value="">@lang('shop::app.components.layouts.header.desktop.bottom.all-categories')</option>

                @foreach ($topLevelCategories as $category)
                    <option value="{{ $category->id }}" {{ (int) request('category_id') === $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <div class="relative min-w-0 flex-1">
                <label for="organic-search" class="sr-only">
                    @lang('shop::app.components.layouts.header.desktop.bottom.search')
                </label>

                <input
                    type="text"
                    id="organic-search"
                    name="query"
                    value="{{ request('query') }}"
                    toolparamdescription="{{ trans('shop::app.components.layouts.webmcp.search-products-query') }}"
                    class="block w-full border-0 px-4 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-0"
                    minlength="{{ core()->getConfigData('catalog.products.search.min_query_length') }}"
                    maxlength="{{ core()->getConfigData('catalog.products.search.max_query_length') }}"
                    placeholder="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.search-text')"
                    aria-required="true"
                    pattern="[^\\]+"
                    required
                >

                @if (core()->getConfigData('catalog.products.settings.image_search'))
                    @include('shop::search.images.index')
                @endif
            </div>

            <button
                type="submit"
                class="flex shrink-0 items-center gap-1.5 bg-brandGreen px-5 text-sm font-semibold text-white transition hover:opacity-90"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.submit')"
            >
                <span class="icon-search text-lg" aria-hidden="true"></span>

                <span class="max-1180:hidden">@lang('shop::app.components.layouts.header.desktop.bottom.search-button')</span>
            </button>
        </form>
    </div>

    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.search_bar.after') !!}

    <!-- Right Navigation Links -->
    <div class="flex shrink-0 items-center gap-x-6 max-[1100px]:gap-x-4 max-lg:gap-x-5">

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.wishlist.before') !!}

        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
            <a
                href="{{ auth()->guard('customer')->check() ? route('shop.customers.account.wishlist.index') : route('shop.customer.session.index') }}"
                class="relative flex flex-col items-center text-slate-600 hover:text-brandNavy"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.wishlist')"
            >
                <span class="icon-heart text-2xl" aria-hidden="true"></span>

                @auth('customer')
                    @php $wishlistCount = auth()->guard('customer')->user()->wishlist_items->count(); @endphp

                    @if ($wishlistCount)
                        <span class="absolute -right-2 -top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-brandGreen px-1 text-[10px] font-semibold text-white">
                            {{ $wishlistCount }}
                        </span>
                    @endif
                @endauth
            </a>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.wishlist.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.before') !!}

        <!-- Compare -->
        @if(core()->getConfigData('catalog.products.settings.compare_option'))
            <a
                href="{{ route('shop.compare.index') }}"
                aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.compare')"
            >
                <span
                    class="inline-block text-2xl cursor-pointer icon-compare"
                    role="presentation"
                ></span>
            </a>
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.compare.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.before') !!}

        <!-- Mini cart -->
        @if(core()->getConfigData('sales.checkout.shopping_cart.cart_page'))
            @include('shop::checkout.cart.mini-cart')
        @endif

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.mini_cart.after') !!}

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.before') !!}

        <!-- user profile -->
        <x-shop::dropdown position="bottom-{{ core()->getCurrentLocale()->direction === 'ltr' ? 'right' : 'left' }}">
            <x-slot:toggle>
                <span
                    class="inline-block text-2xl cursor-pointer icon-users"
                    role="button"
                    aria-label="@lang('shop::app.components.layouts.header.desktop.bottom.profile')"
                    tabindex="0"
                ></span>
            </x-slot>

            <!-- Guest Dropdown -->
            @guest('customer')
                <x-slot:content>
                    <div class="grid gap-2.5">
                        <p class="text-xl font-dmserif">
                            @lang('shop::app.components.layouts.header.desktop.bottom.welcome-guest')
                        </p>

                        <p class="text-sm">
                            @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                        </p>
                    </div>

                    <p class="w-full mt-3 border border-zinc-200"></p>

                    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.before') !!}

                    <div class="flex gap-4 mt-6">
                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_in_button.before') !!}

                        <a
                            href="{{ route('shop.customer.session.create') }}"
                            class="block m-0 mx-auto text-base text-center primary-button w-max rounded-2xl px-7 max-md:rounded-lg ltr:ml-0 rtl:mr-0"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.sign-in')
                        </a>

                        <a
                            href="{{ route('shop.customers.register.index') }}"
                            class="block m-0 mx-auto text-base text-center border-2 secondary-button w-max rounded-2xl px-7 max-md:rounded-lg max-md:py-3 ltr:ml-0 rtl:mr-0"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.sign-up')
                        </a>

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.sign_up_button.after') !!}
                    </div>

                    @if (core()->getConfigData('sales.eu_withdrawal.general.enabled', core()->getCurrentChannelCode()))
                        <a
                            href="{{ route('shop.eu-withdrawal.guest.lookup') }}"
                            class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-navyBlue hover:underline"
                        >
                            @lang('shop::app.eu_withdrawal.guest_dropdown.link')
                        </a>
                    @endif

                    {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.customers_action.after') !!}
                </x-slot>
            @endguest

            <!-- Customers Dropdown -->
            @auth('customer')
                <x-slot:content class="!p-0">
                    <div class="grid gap-2.5 p-5 pb-0">
                        <p class="text-xl font-dmserif" v-pre>
                            @lang('shop::app.components.layouts.header.desktop.bottom.welcome')’
                            {{ auth()->guard('customer')->user()->first_name }}
                        </p>

                        <p class="text-sm">
                            @lang('shop::app.components.layouts.header.desktop.bottom.dropdown-text')
                        </p>
                    </div>

                    <p class="w-full mt-3 border border-zinc-200"></p>

                    <div class="mt-2.5 grid gap-1 pb-2.5">
                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.before') !!}

                        <a
                            class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                            href="{{ route('shop.customers.account.profile.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.profile')
                        </a>

                        <a
                            class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                            href="{{ route('shop.customers.account.orders.index') }}"
                        >
                            @lang('shop::app.components.layouts.header.desktop.bottom.orders')
                        </a>

                        @if (core()->getConfigData('customer.settings.wishlist.wishlist_option'))
                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customers.account.wishlist.index') }}"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.wishlist')
                            </a>
                        @endif

                        <!--Customers logout-->
                        @auth('customer')
                            <x-shop::form
                                method="DELETE"
                                action="{{ route('shop.customer.session.destroy') }}"
                                id="customerLogout"
                            />

                            <a
                                class="px-5 py-2 text-base cursor-pointer hover:bg-gray-100"
                                href="{{ route('shop.customer.session.destroy') }}"
                                onclick="event.preventDefault(); document.getElementById('customerLogout').submit();"
                            >
                                @lang('shop::app.components.layouts.header.desktop.bottom.logout')
                            </a>
                        @endauth

                        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile_dropdown.links.after') !!}
                    </div>
                </x-slot>
            @endauth
        </x-shop::dropdown>

        {!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.profile.after') !!}
    </div>
</div>

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.before') !!}

<!-- Category Navigation Row -->
<div class="w-full border border-b border-l-0 border-r-0 border-t-0 bg-white px-[60px] max-1180:px-8">
    <v-desktop-category>
        <div class="flex items-center gap-5 py-3">
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
        </div>
    </v-desktop-category>
</div>

{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.category.after') !!}

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-desktop-category-template"
    >
        <!-- Loading State -->
        <div
            class="flex items-center gap-5 py-3"
            v-if="isLoading"
        >
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
            <span class="w-16 h-10 rounded shimmer" role="presentation"></span>
        </div>

        <!-- Icon category nav row -->
        <div class="marketplace-category-scroll flex items-stretch gap-1 overflow-x-auto" v-else ref="scrollRow">
            <div
                class="group relative flex shrink-0 flex-col items-center justify-center gap-1 border-b-2 border-transparent px-3 py-2 text-center hover:border-brandGreen hover:text-brandNavy"
                v-for="category in categories.slice(0, 12)"
            >
                <a :href="category.url" class="flex flex-col items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path :d="iconPath(category.name)" />
                    </svg>

                    <span class="whitespace-nowrap text-xs font-medium">@{{ category.name }}</span>
                </a>

                <div
                    class="pointer-events-none absolute top-[62px] z-[1] max-h-[580px] w-max max-w-[1260px] translate-y-1 overflow-auto overflow-x-auto border border-b-0 border-l-0 border-r-0 border-t border-[#F3F3F3] bg-white p-9 opacity-0 shadow-[0_6px_6px_1px_rgba(0,0,0,.3)] transition duration-300 ease-out group-hover:pointer-events-auto group-hover:translate-y-0 group-hover:opacity-100 group-hover:duration-200 group-hover:ease-in ltr:-left-9 rtl:-right-9"
                    v-if="category.children && category.children.length"
                >
                    <div class="flex justify-between gap-x-[70px]">
                        <div
                            class="grid w-full min-w-max max-w-[150px] flex-auto grid-cols-[1fr] content-start gap-5"
                            v-for="pairCategoryChildren in pairCategoryChildren(category)"
                        >
                            <template v-for="secondLevelCategory in pairCategoryChildren">
                                <p class="font-medium text-navyBlue">
                                    <a :href="secondLevelCategory.url">
                                        @{{ secondLevelCategory.name }}
                                    </a>
                                </p>

                                <ul
                                    class="grid grid-cols-[1fr] gap-3"
                                    v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                >
                                    <li
                                        class="text-sm font-medium text-zinc-500"
                                        v-for="thirdLevelCategory in secondLevelCategory.children"
                                    >
                                        <a :href="thirdLevelCategory.url">
                                            @{{ thirdLevelCategory.name }}
                                        </a>
                                    </li>
                                </ul>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- "More" trigger for everything beyond the first 12 -->
            <div
                class="flex shrink-0 cursor-pointer flex-col items-center justify-center gap-1 border-b-2 border-transparent px-3 py-2 text-center text-slate-500 hover:border-brandGreen hover:text-brandNavy"
                v-if="categories.length > 12"
                @click="toggleCategoryDrawer"
            >
                <span class="text-xl icon-hamburger"></span>
                <span class="whitespace-nowrap text-xs font-medium">
                    @lang('shop::app.components.layouts.header.desktop.bottom.more')
                </span>
            </div>

            <!-- Bagisto Drawer Integration -->
            <x-shop::drawer
                position="left"
                width="400px"
                ::is-active="isDrawerActive"
                @toggle="onDrawerToggle"
                @close="onDrawerClose"
            >
                <x-slot:toggle></x-slot>

                <x-slot:header class="border-b border-gray-200">
                    <div class="flex items-center justify-between w-full">
                        <p class="text-xl font-medium">
                            @lang('shop::app.components.layouts.header.desktop.bottom.categories')
                        </p>
                    </div>
                </x-slot>

                <x-slot:content class="!px-0">
                    <!-- Wrapper with transition effects -->
                    <div class="relative h-full overflow-hidden">
                        <!-- Sliding container -->
                        <div
                            class="flex h-full transition-transform duration-300"
                            :class="{
                                'ltr:translate-x-0 rtl:translate-x-0': currentViewLevel !== 'third',
                                'ltr:-translate-x-full rtl:translate-x-full': currentViewLevel === 'third'
                            }"
                        >
                            <!-- First level view -->
                            <div class="h-[calc(100vh-74px)] w-full flex-shrink-0 overflow-auto">
                                <div class="py-4">
                                    <div
                                        v-for="category in categories"
                                        :key="category.id"
                                        :class="{'mb-2': category.children && category.children.length}"
                                    >
                                        <div class="flex items-center justify-between px-6 py-2 transition-colors duration-200 cursor-pointer hover:bg-gray-100">
                                            <a
                                                :href="category.url"
                                                class="flex items-center gap-3 text-base font-medium text-black"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                                    <path :d="iconPath(category.name)" />
                                                </svg>

                                                @{{ category.name }}
                                            </a>
                                        </div>

                                        <!-- Second Level Categories -->
                                        <div v-if="category.children && category.children.length" >
                                            <div
                                                v-for="secondLevelCategory in category.children"
                                                :key="secondLevelCategory.id"
                                            >
                                                <div
                                                    class="flex items-center justify-between px-6 py-2 transition-colors duration-200 cursor-pointer hover:bg-gray-100"
                                                    @click="showThirdLevel(secondLevelCategory, category, $event)"
                                                >
                                                    <a
                                                        :href="secondLevelCategory.url"
                                                        class="text-sm font-normal"
                                                    >
                                                        @{{ secondLevelCategory.name }}
                                                    </a>

                                                    <span
                                                        v-if="secondLevelCategory.children && secondLevelCategory.children.length"
                                                        class="icon-arrow-right rtl:icon-arrow-left"
                                                    ></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Third level view -->
                            <div
                                class="flex-shrink-0 w-full h-full"
                                v-if="currentViewLevel === 'third'"
                            >
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <button
                                        @click="goBackToMainView"
                                        class="flex items-center justify-center gap-2 focus:outline-none"
                                        aria-label="Go back"
                                    >
                                        <span class="text-lg icon-arrow-left rtl:icon-arrow-right"></span>

                                        <p class="text-base font-medium text-black">
                                            @lang('shop::app.components.layouts.header.desktop.bottom.back-button')
                                        </p>
                                    </button>
                                </div>

                                <!-- Third Level Content -->
                                <div class="py-4">
                                    <div
                                        v-for="thirdLevelCategory in currentSecondLevelCategory?.children"
                                        :key="thirdLevelCategory.id"
                                        class="mb-2"
                                    >
                                        <a
                                            :href="thirdLevelCategory.url"
                                            class="block px-6 py-2 text-sm transition-colors duration-200 hover:bg-gray-100"
                                        >
                                            @{{ thirdLevelCategory.name }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-slot>
            </x-shop::drawer>
        </div>
    </script>

    <script type="module">
        const categoryIconKeywords = @json(\Webkul\Marketplace\Helpers\CategoryIcon::$keywords);
        const categoryIconPaths = @json(\Webkul\Marketplace\Helpers\CategoryIcon::$paths);

        app.component('v-desktop-category', {
            template: '#v-desktop-category-template',

            data() {
                return {
                    isLoading: true,
                    categories: [],
                    isDrawerActive: false,
                    currentViewLevel: 'main',
                    currentSecondLevelCategory: null,
                    currentParentCategory: null
                }
            },

            mounted() {
                this.initCategories();
            },

            methods: {
                initCategories() {
                    try {
                        const stored = localStorage.getItem('categories');

                        if (stored) {
                            this.categories = JSON.parse(stored);
                            this.isLoading = false;
                            this.$nextTick(() => window.marketplacePeekScroll?.(this.$refs.scrollRow));

                            return;
                        }

                    } catch (e) {}

                    this.getCategories();
                },

                getCategories() {
                    this.$axios.get("{{ route('shop.api.categories.tree') }}")
                        .then(response => {
                            this.isLoading = false;
                            this.categories = response.data.data;
                            localStorage.setItem('categories', JSON.stringify(this.categories));
                            this.$nextTick(() => window.marketplacePeekScroll?.(this.$refs.scrollRow));
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                iconPath(name) {
                    const lower = (name || '').toLowerCase();

                    for (const keyword in categoryIconKeywords) {
                        if (lower.includes(keyword)) {
                            return categoryIconPaths[categoryIconKeywords[keyword]] || categoryIconPaths['box'];
                        }
                    }

                    return categoryIconPaths['box'];
                },

                pairCategoryChildren(category) {
                    if (! category.children) return [];

                    return category.children.reduce((result, value, index, array) => {
                        if (index % 2 === 0) {
                            result.push(array.slice(index, index + 2));
                        }
                        return result;
                    }, []);
                },

                toggleCategoryDrawer() {
                    this.isDrawerActive = !this.isDrawerActive;
                    if (this.isDrawerActive) {
                        this.currentViewLevel = 'main';
                    }
                },

                onDrawerToggle(event) {
                    this.isDrawerActive = event.isActive;
                },

                onDrawerClose(event) {
                    this.isDrawerActive = false;
                },

                showThirdLevel(secondLevelCategory, parentCategory, event) {
                    if (secondLevelCategory.children && secondLevelCategory.children.length) {
                        this.currentSecondLevelCategory = secondLevelCategory;
                        this.currentParentCategory = parentCategory;
                        this.currentViewLevel = 'third';

                        if (event) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                    }
                },

                goBackToMainView() {
                    this.currentViewLevel = 'main';
                }
            },
        });
    </script>
@endPushOnce
{!! view_render_event('bagisto.shop.components.layouts.header.desktop.bottom.after') !!}
