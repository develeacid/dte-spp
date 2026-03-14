@php
    $bgColor = match($color ?? 'sin_datos') {
        'verde' => 'bg-green-500',
        'amarillo' => 'bg-yellow-500',
        'rojo' => 'bg-red-500',
        default => 'bg-gray-300 dark:bg-gray-600',
    };
@endphp
<span class="inline-block h-3 w-3 rounded-full {{ $bgColor }}" title="{{ ucfirst($color ?? 'sin datos') }}"></span>
