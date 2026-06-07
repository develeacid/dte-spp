@props([
    'color' => 'gris',
    'label' => null,
    'size' => 'sm',
])

@php
    $dotColors = [
        'verde'     => 'bg-green-500',
        'amarillo'  => 'bg-yellow-500',
        'rojo'      => 'bg-red-500',
        'rojo_alto' => 'bg-purple-500',
        'gris'      => 'bg-gray-400',
    ];

    $dotSizes = [
        'sm' => 'w-2 h-2',
        'md' => 'w-3 h-3',
        'lg' => 'w-4 h-4',
    ];

    $textSizes = [
        'sm' => 'text-xs',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    $dotColor = $dotColors[$color] ?? $dotColors['gris'];
    $dotSize = $dotSizes[$size] ?? $dotSizes['sm'];
    $textSize = $textSizes[$size] ?? $textSizes['sm'];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5']) }}>
    <span class="rounded-full shrink-0 {{ $dotColor }} {{ $dotSize }}"></span>
    @if($label)
        <span class="{{ $textSize }} text-gray-700">{{ $label }}</span>
    @endif
</span>
