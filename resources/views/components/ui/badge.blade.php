@props(['color' => 'blue'])

@php
$colors = [
    'blue'   => 'bg-blue-100 text-blue-800',
    'green'  => 'bg-green-100 text-green-800',
    'yellow' => 'bg-yellow-100 text-yellow-800',
    'red'    => 'bg-red-100 text-red-800',
    'gray'   => 'bg-gray-100 text-gray-800',
    'purple' => 'bg-purple-100 text-purple-800',
];
$colorClass = $colors[$color] ?? $colors['blue'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {$colorClass}"]) }}>
    {{ $slot }}
</span>
