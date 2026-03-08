@props([
    'href',
    'icon' => null,
    'active' => false,
    'badge' => null,
    'collapsed' => false,
])

@php
$activeClass = $active
    ? 'bg-brand-light text-brand-dark border-l-2 border-brand'
    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 border-l-2 border-transparent';
@endphp

<a href="{{ $href }}"
   {{ $attributes->merge(['class' => "group flex items-center px-3 py-2 text-sm font-medium rounded-r-md transition-colors {$activeClass}"]) }}>
    @if($icon)
        <span class="shrink-0 w-5 h-5" :class="collapsed ? 'mx-auto' : 'mr-3'">
            {{ $icon }}
        </span>
    @endif

    <span x-show="!collapsed" x-cloak class="flex-1 truncate">{{ $slot }}</span>

    @if($badge)
        <span x-show="!collapsed" x-cloak class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-brand-light text-brand-dark">
            {{ $badge }}
        </span>
    @endif
</a>
