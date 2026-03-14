@props(['programa', 'ejercicio' => null])

@php
    $ejercicio = $ejercicio ?? config('presupuesto.ejercicio_default');
    $service = app(\App\Services\EstadoConsolidadoService::class);
    $resumen = $service->resumen($programa->id, $ejercicio);

    $colorEstado = fn (string $estado) => match($estado) {
        'mir_completa', 'mir_validada', 'costeado', 'calendarizado', 'validado'
            => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'mir_borrador', 'parcial', 'en_revision'
            => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'no_implementado'
            => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
        default
            => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    };

    $iconEstado = fn (string $estado) => match($estado) {
        'mir_completa', 'mir_validada', 'costeado', 'calendarizado', 'validado' => '&#10003;',
        'no_implementado' => '—',
        'mir_borrador', 'parcial', 'en_revision' => '&#9679;',
        default => '!',
    };

    $consolidadoColor = match($resumen['consolidado']) {
        'completo' => 'text-green-600 dark:text-green-400',
        'parcial' => 'text-yellow-600 dark:text-yellow-400',
        default => 'text-red-600 dark:text-red-400',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2 flex-wrap']) }}>
    {{-- Planeación --}}
    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorEstado($resumen['areas']['planeacion']['estado']) }}" title="Planeación: {{ $resumen['areas']['planeacion']['label'] }}">
        <span>{!! $iconEstado($resumen['areas']['planeacion']['estado']) !!}</span>
        MIR
    </span>

    {{-- Jurídico --}}
    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorEstado($resumen['areas']['juridico']['estado']) }}" title="Jurídico: {{ $resumen['areas']['juridico']['label'] }}">
        <span>{!! $iconEstado($resumen['areas']['juridico']['estado']) !!}</span>
        Legal
    </span>

    {{-- Financiero --}}
    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium {{ $colorEstado($resumen['areas']['financiero']['estado']) }}" title="Financiero: {{ $resumen['areas']['financiero']['label'] }}">
        <span>{!! $iconEstado($resumen['areas']['financiero']['estado']) !!}</span>
        Costeo
    </span>

    {{-- Consolidado --}}
    <span class="text-xs font-semibold {{ $consolidadoColor }}">
        {{ $resumen['completas'] }}/{{ $resumen['total'] }}
    </span>
</div>
