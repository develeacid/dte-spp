@php
    $stats = $this->geobaseStats;
@endphp

@if($stats)
<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">GeoBase — Padron de Beneficiarios</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $stats['connected'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
            <span class="w-2 h-2 mr-1.5 rounded-full {{ $stats['connected'] ? 'bg-green-400' : 'bg-red-400' }}"></span>
            {{ $stats['connected'] ? 'Conectado' : 'Sin conexion' }}
        </span>
    </div>

    @if(!$stats['connected'] && $stats['last_error'])
        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded text-sm text-amber-700">
            {{ $stats['last_error'] }}
        </div>
    @endif

    <div class="grid grid-cols-2 gap-4 mb-4">
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-sm text-gray-500">Variables vinculadas</p>
            <p class="text-xl font-bold text-gray-900">
                {{ $stats['variables_vinculadas'] }} / {{ $stats['variables_total'] }}
            </p>
            @if($stats['variables_total'] > 0)
                <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ round(($stats['variables_vinculadas'] / $stats['variables_total']) * 100) }}%"></div>
                </div>
            @endif
        </div>
        <div class="bg-gray-50 rounded-lg p-3">
            <p class="text-sm text-gray-500">Total beneficiarios</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($stats['total_beneficiarios']) }}</p>
        </div>
    </div>

    @if(count($stats['programas']) > 0)
        <div class="border-t pt-3">
            <p class="text-xs font-medium text-gray-500 uppercase mb-2">Cobertura por programa</p>
            @foreach($stats['programas'] as $programa)
                <div class="flex items-center justify-between py-1.5 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                    <span class="text-sm text-gray-700">{{ $programa['nombre'] }}</span>
                    <span class="text-sm font-semibold text-gray-900">{{ number_format($programa['beneficiarios']) }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <p class="mt-3 text-xs text-gray-400">
        Ultima verificacion: {{ \Carbon\Carbon::parse($stats['last_check'])->diffForHumans() }}
    </p>
</div>
@endif
