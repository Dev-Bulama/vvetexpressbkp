@unless ($breadcrumbs->isEmpty())
    <nav aria-label="">
        <ol class="flex" v-pre>
            @foreach ($breadcrumbs as $breadcrumb)
                @if (
                    $breadcrumb->url 
                    && ! $loop->last
                )
                    <li class="flex items-center gap-x-2.5 whitespace-nowrap text-base font-medium text-slate-600">
                        <a href="{{ $breadcrumb->url }}" class="hover:text-brandNavy">
                            {{ $breadcrumb->title }}
                        </a>

                        <span class="text-2xl icon-arrow-right rtl:icon-arrow-left"></span>
                    </li>
                @else
                    <li
                        class="flex items-center gap-x-2.5 whitespace-nowrap break-all text-base font-medium text-brandGreen after:content-['/'] after:last:hidden ltr:ml-2.5 rtl:mr-0"
                        aria-current="page"
                    >
                        {{ $breadcrumb->title }}
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endunless
