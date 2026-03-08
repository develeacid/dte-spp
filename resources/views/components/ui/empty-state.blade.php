@props([
    'title' => 'Sin datos',
    'description' => '',
    'icon' => null,
    'actionUrl' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    @if($icon)
        <div class="mx-auto w-12 h-12 text-gray-300">
            {{ $icon }}
        </div>
    @else
        <svg class="mx-auto w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/>
        </svg>
    @endif

    <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ $title }}</h3>

    @if($description)
        <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
    @endif

    @if($actionUrl && $actionLabel)
        <div class="mt-6">
            <x-ui.button.primary :href="$actionUrl">
                {{ $actionLabel }}
            </x-ui.button.primary>
        </div>
    @endif

    @if($slot->isNotEmpty())
        <div class="mt-6">
            {{ $slot }}
        </div>
    @endif
</div>
