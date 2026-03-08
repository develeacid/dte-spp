@props([
    'title' => '',
    'value' => '',
    'subtitle' => '',
    'icon' => null,
    'trend' => null,
    'trendUp' => true,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-lg border border-gray-200 p-5']) }}>
    <div class="flex items-center justify-between">
        <div class="min-w-0">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider truncate">{{ $title }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $value }}</p>
            @if($subtitle)
                <p class="mt-1 text-xs text-gray-500">{{ $subtitle }}</p>
            @endif
            @if($trend)
                <p class="mt-1 text-xs font-medium {{ $trendUp ? 'text-brand' : 'text-red-600' }}">
                    {{ $trendUp ? '↑' : '↓' }} {{ $trend }}
                </p>
            @endif
        </div>
        @if($icon)
            <div class="shrink-0 ml-4 w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
                {{ $icon }}
            </div>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="mt-4 pt-4 border-t border-gray-100">
            {{ $slot }}
        </div>
    @endif
</div>
