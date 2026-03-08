@props([
    'label' => '',
    'value' => '',
    'change' => null,
    'changeUp' => true,
])

<div {{ $attributes->merge(['class' => 'text-center']) }}>
    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ $label }}</p>
    <p class="mt-1 text-3xl font-bold text-gray-900">{{ $value }}</p>
    @if($change)
        <p class="mt-1 text-sm font-medium {{ $changeUp ? 'text-brand' : 'text-red-600' }}">
            {{ $changeUp ? '+' : '' }}{{ $change }}
        </p>
    @endif
</div>
