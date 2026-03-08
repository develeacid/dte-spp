@props([
    'name' => '',
    'src' => null,
    'size' => 'md',
])

@php
$sizes = [
    'sm' => 'w-6 h-6 text-xs',
    'md' => 'w-8 h-8 text-sm',
    'lg' => 'w-10 h-10 text-base',
];
$sizeClass = $sizes[$size] ?? $sizes['md'];
$initials = collect(explode(' ', $name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('');
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
         {{ $attributes->merge(['class' => "rounded-full object-cover {$sizeClass}"]) }}>
@else
    <span {{ $attributes->merge(['class' => "inline-flex items-center justify-center rounded-full bg-gray-200 text-gray-600 font-semibold {$sizeClass}"]) }}>
        {{ $initials }}
    </span>
@endif
