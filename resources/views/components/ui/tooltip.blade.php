@props([
    'text' => '',
    'position' => 'top',
    'maxWidth' => 'max-w-xs',
])

@php
    $positionClasses = match ($position) {
        'top'    => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left'   => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right'  => 'left-full top-1/2 -translate-y-1/2 ml-2',
        default  => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    };

    $arrowClasses = match ($position) {
        'top'    => 'top-full left-1/2 -translate-x-1/2 border-t-gray-900 border-x-transparent border-b-transparent border-4',
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 border-b-gray-900 border-x-transparent border-t-transparent border-4',
        'left'   => 'left-full top-1/2 -translate-y-1/2 border-l-gray-900 border-y-transparent border-r-transparent border-4',
        'right'  => 'right-full top-1/2 -translate-y-1/2 border-r-gray-900 border-y-transparent border-l-transparent border-4',
        default  => 'top-full left-1/2 -translate-x-1/2 border-t-gray-900 border-x-transparent border-b-transparent border-4',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:mouseenter="show = true"
    x-on:mouseleave="show = false"
    x-on:click.away="show = false"
    class="relative inline-flex"
>
    <div x-on:click="show = !show">
        {{ $slot }}
    </div>

    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 {{ $positionClasses }}"
    >
        <div class="{{ $maxWidth }} rounded-md bg-gray-900 px-3 py-2 text-sm text-white shadow-lg pointer-events-none">
            {{ $text }}
        </div>
        <div class="absolute {{ $arrowClasses }} w-0 h-0"></div>
    </div>
</div>
