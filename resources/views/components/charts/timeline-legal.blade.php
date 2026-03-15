@props([
    'items' => [],
])

@php
    $nodeSpacing = 60;
    $startY = 30;
    $lineX = 20;
    $totalHeight = count($items) > 0 ? $startY + (count($items) - 1) * $nodeSpacing + 30 : 60;

    $colors = [
        'vigente'  => '#22C55E',
        'pendiente' => '#9CA3AF',
        'vencido'  => '#EF4444',
    ];
@endphp

<div {{ $attributes->merge(['class' => '']) }}>
    <svg width="100%" height="{{ $totalHeight }}" viewBox="0 0 500 {{ $totalHeight }}" xmlns="http://www.w3.org/2000/svg">
        {{-- Vertical connecting line --}}
        @if(count($items) > 1)
            <line
                x1="{{ $lineX }}"
                y1="{{ $startY }}"
                x2="{{ $lineX }}"
                y2="{{ $startY + (count($items) - 1) * $nodeSpacing }}"
                stroke="#E5E7EB"
                stroke-width="2"
            />
        @endif

        @foreach($items as $index => $item)
            @php
                $cy = $startY + $index * $nodeSpacing;
                $estado = $item['estado'] ?? 'pendiente';
                $nodeColor = $colors[$estado] ?? $colors['pendiente'];
                $nombre = $item['nombre'] ?? '';
                $articulo = $item['articulo'] ?? '';
                $descripcion = $item['descripcion'] ?? '';
                if (mb_strlen($descripcion) > 80) {
                    $descripcion = mb_substr($descripcion, 0, 80) . '...';
                }
                $isFilled = $estado === 'vigente';
            @endphp

            {{-- Node circle --}}
            <circle
                cx="{{ $lineX }}"
                cy="{{ $cy }}"
                r="8"
                fill="{{ $isFilled ? $nodeColor : 'white' }}"
                stroke="{{ $nodeColor }}"
                stroke-width="2"
            />

            {{-- Nombre + Articulo (bold, 14px) --}}
            <text
                x="40"
                y="{{ $cy + 1 }}"
                font-size="14"
                font-weight="bold"
                fill="#1F2937"
                dominant-baseline="middle"
            >{{ $nombre }}@if($articulo) &mdash; {{ $articulo }}@endif</text>

            {{-- Descripcion (gray, 12px) --}}
            @if($descripcion)
                <text
                    x="40"
                    y="{{ $cy + 18 }}"
                    font-size="12"
                    fill="#6B7280"
                    dominant-baseline="middle"
                >{{ $descripcion }}</text>
            @endif
        @endforeach
    </svg>
</div>
