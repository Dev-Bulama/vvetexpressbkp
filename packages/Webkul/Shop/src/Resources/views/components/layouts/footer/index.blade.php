{!! view_render_event('bagisto.shop.layout.footer.before') !!}

@php
    $channel = core()->getCurrentChannel();

    $supportEmail = core()->getConfigData('general.content.footer.support_email');
    $supportPhone = core()->getConfigData('general.content.footer.support_phone');
    $businessAddress = core()->getConfigData('general.content.footer.address');
    $facebookUrl = core()->getConfigData('general.content.footer.facebook_url');
    $instagramUrl = core()->getConfigData('general.content.footer.instagram_url');
    $xUrl = core()->getConfigData('general.content.footer.x_url');

    $topCategories = app(\Webkul\Category\Repositories\CategoryRepository::class)
        ->getVisibleCategoryTree($channel->root_category_id)
        ->take(6);

    $paymentMethods = collect([
        ['code' => 'cashondelivery', 'label' => 'Pay on Delivery', 'image' => 'cash-on-delivery'],
        ['code' => 'moneytransfer', 'label' => 'Bank Transfer', 'image' => null],
        ['code' => 'stripe', 'label' => 'Card (Stripe)', 'image' => 'stripe'],
        ['code' => 'razorpay', 'label' => 'Razorpay', 'image' => 'razorpay'],
        ['code' => 'payu', 'label' => 'PayU', 'image' => 'payu'],
        ['code' => 'phonepe', 'label' => 'PhonePe', 'image' => 'phonepe'],
        ['code' => 'paypal_smart_button', 'label' => 'PayPal', 'image' => 'paypal'],
        ['code' => 'paypal_standard', 'label' => 'PayPal', 'image' => 'paypal'],
    ])->filter(fn ($method) => core()->getConfigData("sales.payment_methods.{$method['code']}.active"))
        ->unique('label')
        ->values();

    $marketplaceLinks = collect([
        ['title' => 'About Us', 'url' => route('shop.cms.page', ['slug' => 'about-us'])],
        ['title' => 'Contact Us', 'url' => route('shop.home.contact_us')],
        ['title' => 'Terms & Conditions', 'url' => route('shop.cms.page', ['slug' => 'terms-conditions'])],
        ['title' => 'Privacy Policy', 'url' => route('shop.cms.page', ['slug' => 'privacy-policy'])],
    ]);

    $customerServiceLinks = collect([
        ['title' => 'Help Centre', 'url' => route('shop.cms.page', ['slug' => 'customer-service'])],
        ['title' => 'Track Your Order', 'url' => route('shop.customers.account.orders.index')],
        ['title' => 'Returns and Refunds', 'url' => route('shop.cms.page', ['slug' => 'return-policy'])],
        ['title' => 'Delivery Information', 'url' => route('shop.cms.page', ['slug' => 'shipping-policy'])],
    ]);

    $sellLinks = collect([
        ['title' => 'Become a Vendor', 'url' => route('marketplace.seller.register.index')],
        ['title' => 'Vendor Login', 'url' => route('marketplace.seller.session.index')],
    ]);
@endphp

<footer class="mt-9 bg-brandNavy max-sm:mt-10">
    {{-- Newsletter strip --}}
    {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.before') !!}

    @if (core()->getConfigData('customer.settings.newsletter.subscription'))
        <div class="border-b border-white/10 bg-[#0B3547]">
            <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-between gap-6 px-[60px] py-9 max-1180:px-8 max-md:flex-col max-md:text-center max-sm:px-4 max-sm:py-6">
                <div class="grid gap-1.5">
                    <p class="text-2xl font-semibold text-white max-sm:text-xl">
                        Get the Latest Deals
                    </p>

                    <p class="text-sm text-white/70">
                        Subscribe for exclusive offers, new arrivals and vet-approved tips.
                    </p>
                </div>

                <x-shop::form
                    :action="route('shop.subscription.store')"
                    class="w-full max-w-[440px]"
                    toolname="subscribe_to_newsletter"
                    tooldescription="{{ trans('shop::app.components.layouts.webmcp.subscribe-newsletter') }}"
                    toolautosubmit
                >
                    <div class="relative w-full">
                        <x-shop::form.control-group.control
                            type="email"
                            class="block w-full rounded-xl border border-white/20 bg-white/10 px-5 py-3.5 text-sm text-white placeholder:text-white/50 focus:border-brandGreen max-sm:p-3"
                            name="email"
                            rules="required|email"
                            label="Email"
                            :aria-label="trans('shop::app.components.layouts.footer.email')"
                            placeholder="email@example.com"
                            toolparamdescription="{{ trans('shop::app.components.layouts.webmcp.subscribe-newsletter-email') }}"
                        />

                        <x-shop::form.control-group.error control-name="email" class="!text-amber-300" />

                        <button
                            type="submit"
                            class="absolute top-1.5 flex w-max items-center rounded-lg bg-brandGreen px-6 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 ltr:right-1.5 rtl:left-1.5 max-sm:px-4 max-sm:text-xs"
                        >
                            @lang('shop::app.components.layouts.footer.subscribe')
                        </button>
                    </div>
                </x-shop::form>
            </div>
        </div>
    @endif

    {!! view_render_event('bagisto.shop.layout.footer.newsletter_subscription.after') !!}

    {{-- Main footer columns: desktop --}}
    <div class="mx-auto max-w-[1200px] px-[60px] py-12 max-1180:px-8 max-1060:hidden max-sm:px-4">
        <div class="grid grid-cols-5 gap-8 max-lg:grid-cols-3">
            <div class="col-span-1 grid gap-4 max-lg:col-span-3">
                <a href="{{ route('shop.home.index') }}" class="inline-block w-max">
                    <img
                        src="{{ $channel->logo_url ?? asset('vetexpress/logo.svg') }}"
                        width="140"
                        height="32"
                        alt="{{ config('app.name') }}"
                        class="brightness-0 invert"
                    >
                </a>

                <p class="max-w-[240px] text-sm text-white/60">
                    Nigeria's multi-vendor marketplace for pet, farm and veterinary supplies.
                </p>

                @if ($facebookUrl || $instagramUrl || $xUrl)
                    <div class="flex items-center gap-3">
                        @if ($facebookUrl)
                            <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white hover:bg-brandGreen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 9H15V6.5h-1.5C11.6 6.5 10 8.1 10 10.2V12H8v2.5h2V21h2.5v-6.5H15l.5-2.5h-3v-1.8c0-.7.4-1.2 1-1.2z"/></svg>
                            </a>
                        @endif

                        @if ($instagramUrl)
                            <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white hover:bg-brandGreen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r="0.8" fill="currentColor" stroke="none"/></svg>
                            </a>
                        @endif

                        @if ($xUrl)
                            <a href="{{ $xUrl }}" target="_blank" rel="noopener noreferrer" aria-label="X" class="flex h-8 w-8 items-center justify-center rounded-full bg-white/10 text-white hover:bg-brandGreen">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H21l-6.9 7.9L22 21h-6.7l-5-6-5.8 6H2l7.5-8.5L2.4 3H9.3l4.5 5.5L18.9 3Zm-1.2 16h1.7L7 5h-1.8l12.5 14Z"/></svg>
                            </a>
                        @endif
                    </div>
                @endif
            </div>

            <div class="grid gap-3.5 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/50">Marketplace</p>

                @foreach ($marketplaceLinks as $link)
                    <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                @endforeach
            </div>

            <div class="grid gap-3.5 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/50">Customer Service</p>

                @foreach ($customerServiceLinks as $link)
                    <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                @endforeach
            </div>

            <div class="grid gap-3.5 text-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-white/50">Sell With Us</p>

                @foreach ($sellLinks as $link)
                    <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                @endforeach
            </div>

            <div class="grid gap-3.5 text-sm">
                @if ($topCategories->isNotEmpty())
                    <p class="text-xs font-semibold uppercase tracking-wide text-white/50">Categories</p>

                    @foreach ($topCategories as $category)
                        <a href="{{ $category->url }}" class="text-white/75 hover:text-brandGreen">{{ $category->name }}</a>
                    @endforeach
                @endif
            </div>
        </div>

        @if ($supportEmail || $supportPhone || $businessAddress)
            <div class="mt-10 grid grid-cols-3 gap-6 border-t border-white/10 pt-8 text-sm max-lg:grid-cols-1">
                @if ($businessAddress)
                    <div class="flex items-start gap-2.5 text-white/70">
                        <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /></svg>
                        {{ $businessAddress }}
                    </div>
                @endif

                @if ($supportEmail)
                    <a href="mailto:{{ $supportEmail }}" class="flex items-center gap-2.5 text-white/70 hover:text-brandGreen">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h16v16H4V4Zm0 0 8 8 8-8" /></svg>
                        {{ $supportEmail }}
                    </a>
                @endif

                @if ($supportPhone)
                    <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="flex items-center gap-2.5 text-white/70 hover:text-brandGreen">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-brandGreen" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.4 2.1L8 9.9a16 16 0 0 0 6 6l1.4-1.4a2 2 0 0 1 2.1-.4c.9.3 1.8.5 2.7.6a2 2 0 0 1 1.8 2Z" /></svg>
                        {{ $supportPhone }}
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Main footer columns: mobile accordions --}}
    <div class="hidden max-1060:block">
        <x-shop::accordion :is-active="false" class="!border-white/10">
            <x-slot:header class="!bg-transparent px-4 py-3.5 font-medium text-white">
                Marketplace
            </x-slot>

            <x-slot:content class="!bg-transparent !p-4 !pt-0">
                <div class="grid gap-3 text-sm">
                    @foreach ($marketplaceLinks as $link)
                        <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                    @endforeach
                </div>
            </x-slot>
        </x-shop::accordion>

        <x-shop::accordion :is-active="false" class="!border-white/10">
            <x-slot:header class="!bg-transparent px-4 py-3.5 font-medium text-white">
                Customer Service
            </x-slot>

            <x-slot:content class="!bg-transparent !p-4 !pt-0">
                <div class="grid gap-3 text-sm">
                    @foreach ($customerServiceLinks as $link)
                        <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                    @endforeach
                </div>
            </x-slot>
        </x-shop::accordion>

        <x-shop::accordion :is-active="false" class="!border-white/10">
            <x-slot:header class="!bg-transparent px-4 py-3.5 font-medium text-white">
                Sell With Us
            </x-slot>

            <x-slot:content class="!bg-transparent !p-4 !pt-0">
                <div class="grid gap-3 text-sm">
                    @foreach ($sellLinks as $link)
                        <a href="{{ $link['url'] }}" class="text-white/75 hover:text-brandGreen">{{ $link['title'] }}</a>
                    @endforeach
                </div>
            </x-slot>
        </x-shop::accordion>

        @if ($topCategories->isNotEmpty())
            <x-shop::accordion :is-active="false" class="!border-white/10">
                <x-slot:header class="!bg-transparent px-4 py-3.5 font-medium text-white">
                    Categories
                </x-slot>

                <x-slot:content class="!bg-transparent !p-4 !pt-0">
                    <div class="grid gap-3 text-sm">
                        @foreach ($topCategories as $category)
                            <a href="{{ $category->url }}" class="text-white/75 hover:text-brandGreen">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </x-slot>
            </x-shop::accordion>
        @endif

        @if ($supportEmail || $supportPhone || $businessAddress)
            <x-shop::accordion :is-active="false" class="!border-white/10">
                <x-slot:header class="!bg-transparent px-4 py-3.5 font-medium text-white">
                    Contact Us
                </x-slot>

                <x-slot:content class="!bg-transparent !p-4 !pt-0">
                    <div class="grid gap-3 text-sm text-white/75">
                        @if ($businessAddress)
                            <p>{{ $businessAddress }}</p>
                        @endif

                        @if ($supportEmail)
                            <a href="mailto:{{ $supportEmail }}" class="hover:text-brandGreen">{{ $supportEmail }}</a>
                        @endif

                        @if ($supportPhone)
                            <a href="tel:{{ preg_replace('/\s+/', '', $supportPhone) }}" class="hover:text-brandGreen">{{ $supportPhone }}</a>
                        @endif
                    </div>
                </x-slot>
            </x-shop::accordion>
        @endif

        @if ($facebookUrl || $instagramUrl || $xUrl)
            <div class="flex items-center justify-center gap-3 border-b border-white/10 px-4 py-5">
                @if ($facebookUrl)
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 9H15V6.5h-1.5C11.6 6.5 10 8.1 10 10.2V12H8v2.5h2V21h2.5v-6.5H15l.5-2.5h-3v-1.8c0-.7.4-1.2 1-1.2z"/></svg>
                    </a>
                @endif

                @if ($instagramUrl)
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3.5" y="3.5" width="17" height="17" rx="4"/><circle cx="12" cy="12" r="3.5"/><circle cx="17" cy="7" r="0.8" fill="currentColor" stroke="none"/></svg>
                    </a>
                @endif

                @if ($xUrl)
                    <a href="{{ $xUrl }}" target="_blank" rel="noopener noreferrer" aria-label="X" class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 3H21l-6.9 7.9L22 21h-6.7l-5-6-5.8 6H2l7.5-8.5L2.4 3H9.3l4.5 5.5L18.9 3Zm-1.2 16h1.7L7 5h-1.8l12.5 14Z"/></svg>
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Payment methods --}}
    @if ($paymentMethods->isNotEmpty())
        <div class="border-t border-white/10 bg-[#0B3547] px-[60px] py-5 max-1180:px-8 max-sm:px-4">
            <div class="mx-auto flex max-w-[1200px] flex-wrap items-center justify-center gap-4 max-sm:gap-3">
                @foreach ($paymentMethods as $method)
                    <span class="flex h-9 items-center rounded-lg bg-white px-3 text-xs font-semibold text-brandNavy">
                        @if ($method['image'] && ($asset = collect(glob(public_path("themes/shop/default/build/assets/{$method['image']}-*")))->first()))
                            <img src="{{ asset(str_replace(public_path().'/', '', $asset)) }}" alt="{{ $method['label'] }}" class="h-5 w-auto object-contain">
                        @else
                            {{ $method['label'] }}
                        @endif
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Bottom bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-[#082733] px-[60px] py-4 max-1180:px-8 max-md:justify-center max-sm:px-4">
        {!! view_render_event('bagisto.shop.layout.footer.footer_text.before') !!}

        <p class="text-sm text-white/60">
            @if (core()->getConfigData('general.content.footer.copyright_content'))
                {!! core()->getConfigData('general.content.footer.copyright_content') !!}
            @else
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            @endif
        </p>

        <div class="flex items-center gap-5 text-sm text-white/60">
            <a href="{{ route('shop.cms.page', ['slug' => 'terms-conditions']) }}" class="hover:text-brandGreen">Terms</a>
            <a href="{{ route('shop.cms.page', ['slug' => 'privacy-policy']) }}" class="hover:text-brandGreen">Privacy</a>
        </div>

        {!! view_render_event('bagisto.shop.layout.footer.footer_text.after') !!}
    </div>
</footer>

{!! view_render_event('bagisto.shop.layout.footer.after') !!}
