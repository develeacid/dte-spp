@props(['text' => ''])

<div x-data="{ show: false }" class="relative inline-block" @mouseenter="show = true" @mouseleave="show = false">
    {{ $slot }}
    <div x-show="show" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-full ml-2 top-1/2 -translate-y-1/2 z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded-md whitespace-nowrap shadow-lg pointer-events-none">
        {{ $text }}
    </div>
</div>
