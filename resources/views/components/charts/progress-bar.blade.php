@props([
    'value' => 0,
    'max' => 100,
    'color' => 'blue',
    'reference' => null,
    'referenceLabel' => 'Meta',
    'height' => 'h-2',
    'showValue' => false,
])

@php
    $percentage = $max > 0 ? min(100, max(0, ($value / $max) * 100)) : 0;

    $colorMap = [
        'blue'   => 'bg-blue-500',
        'green'  => 'bg-green-500',
        'yellow' => 'bg-yellow-500',
        'red'    => 'bg-red-500',
    ];

    $isHex = str_starts_with($color, '#');
    $barClass = $isHex ? '' : ($colorMap[$color] ?? $colorMap['blue']);
    $barStyle = $isHex ? "width: {$percentage}%; background-color: {$color};" : "width: {$percentage}%;";
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <div class="relative">
        {{-- Track --}}
        <div class="w-full bg-gray-200 rounded-full overflow-hidden {{ $height }}">
            {{-- Fill --}}
            <div
                class="rounded-full {{ $height }} {{ $barClass }} transition-all duration-300"
                style="{{ $barStyle }}"
            ></div>
        </div>

        {{-- Reference marker --}}
        @if(!is_null($reference))
            @php
                $refPos = min(100, max(0, $reference));
            @endphp
            <div
                class="absolute top-0 {{ $height }} w-0.5 bg-gray-700"
                style="left: {{ $refPos }}%;"
            ></div>
            @if($referenceLabel)
                <div
                    class="absolute text-xs text-gray-500 -translate-x-1/2 mt-1"
                    style="left: {{ $refPos }}%; top: 100%;"
                >{{ $referenceLabel }}</div>
            @endif
        @endif
    </div>

    {{-- Value text --}}
    @if($showValue)
        <div class="text-xs text-gray-600 mt-1">{{ round($percentage, 1) }}%</div>
    @endif
</div>
