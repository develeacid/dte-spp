@props(['kpis' => []])

@php
    $total = (int) ($kpis['total'] ?? 0);
    $calidad = $kpis['calidad'] ?? null;
    $genero = $kpis['por_genero'] ?? [];
    $femenino = (int) ($genero['femenino'] ?? 0);
    $masculino = (int) ($genero['masculino'] ?? 0);
    $otro = (int) ($genero['otro'] ?? 0);
    $indigenas = (int) (($kpis['por_indigena'] ?? [])['indigena'] ?? 0);
    $conDiscapacidad = (int) (($kpis['por_discapacidad'] ?? [])['con_discapacidad'] ?? 0);
    $pctIndigena = $total > 0 ? round($indigenas / $total * 100, 1) : 0;
    $pctDiscapacidad = $total > 0 ? round($conDiscapacidad / $total * 100, 1) : 0;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white p-4 rounded-lg border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Total atendidos</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($total) }}</p>
    </div>

    <div class="bg-white p-4 rounded-lg border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Mujeres / Hombres</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">
            {{ number_format($femenino) }}
            <span class="text-gray-400">/</span>
            {{ number_format($masculino) }}
        </p>
        @if ($otro > 0)
            <p class="text-xs text-gray-500">Otro: {{ number_format($otro) }}</p>
        @endif
    </div>

    <div class="bg-white p-4 rounded-lg border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Indígenas</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">
            {{ number_format($indigenas) }}
            @if ($total > 0)
                <span class="text-sm font-normal text-gray-500">({{ $pctIndigena }}%)</span>
            @endif
        </p>
    </div>

    <div class="bg-white p-4 rounded-lg border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Con discapacidad</p>
        <p class="mt-1 text-2xl font-bold text-gray-900">
            {{ number_format($conDiscapacidad) }}
            @if ($total > 0)
                <span class="text-sm font-normal text-gray-500">({{ $pctDiscapacidad }}%)</span>
            @endif
        </p>
    </div>
</div>

{{-- Calidad del padrón (M07 req 20) — solo en modo vivo --}}
@if ($calidad !== null)
    <div class="mt-4 bg-white p-4 rounded-lg border border-gray-200">
        <p class="text-xs text-gray-500 uppercase tracking-wide">Calidad del padrón</p>
        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <p class="text-2xl font-bold text-indigo-600">{{ $calidad['completos_pct'] }}%</p>
                <p class="text-xs text-gray-500">
                    Registros completos ({{ number_format($calidad['completos']) }}/{{ number_format($calidad['total_beneficiarios']) }})
                </p>
            </div>
            <div>
                <p class="text-2xl font-bold text-green-600">{{ $calidad['verificados_pct'] }}%</p>
                <p class="text-xs text-gray-500">
                    Registros verificados ({{ number_format($calidad['verificados']) }}/{{ number_format($calidad['total_enrollments']) }})
                </p>
            </div>
        </div>
    </div>
@endif
