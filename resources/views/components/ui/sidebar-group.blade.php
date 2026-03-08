@props([
    'label',
    'icon' => null,
    'active' => false,
])

<div x-data="{ expanded: {{ $active ? 'true' : 'false' }} }" class="space-y-1">
    {{-- Group header --}}
    <button @click="expanded = !expanded"
            x-show="!collapsed" x-cloak
            class="w-full flex items-center px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 hover:text-gray-600 transition-colors">
        @if($icon)
            <span class="shrink-0 w-5 h-5 mr-3">{{ $icon }}</span>
        @endif
        <span class="flex-1 text-left">{{ $label }}</span>
        <svg class="w-4 h-4 transition-transform" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Collapsed: just show icon with tooltip --}}
    <div x-show="collapsed" x-cloak class="relative group py-2 flex justify-center">
        @if($icon)
            <span class="w-5 h-5 text-gray-400 group-hover:text-gray-600">{{ $icon }}</span>
        @endif
        {{-- Tooltip popover --}}
        <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
            <div class="bg-gray-900 text-white text-xs rounded-md py-2 px-3 whitespace-nowrap shadow-lg">
                <div class="font-semibold mb-1">{{ $label }}</div>
                {{ $tooltip ?? '' }}
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div x-show="!collapsed && expanded" x-cloak
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="space-y-0.5 ml-2">
        {{ $slot }}
    </div>
</div>
