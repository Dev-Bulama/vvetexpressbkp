@php
    $icon = $icon ?? 'box';
    $class = $class ?? 'h-6 w-6';

    $d = \Webkul\Marketplace\Helpers\CategoryIcon::$paths[$icon] ?? \Webkul\Marketplace\Helpers\CategoryIcon::$paths['box'];
@endphp

<svg xmlns="http://www.w3.org/2000/svg" class="{{ $class }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="{{ $d }}" />
</svg>
