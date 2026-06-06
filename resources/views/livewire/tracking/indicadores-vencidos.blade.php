<div>
    <x-page.header>
        <x-slot name="title">Indicadores vencidos</x-slot>
    </x-page.header>

    <x-page.container fluid>
        {{-- Resumen visual --}}
        @if ($avances->isNotEmpty())
        <div class="mb-6" wire:ignore>
            @php
                $vencidosPorPrograma = $avances->groupBy(fn ($a) => $a->indicador->programa->clave ?? 'N/A')
                    ->map(fn ($items, $clave) => ['clave' => $clave, 'count' => $items->count()])
                    ->sortByDesc('count')
                    ->values();
            @endphp
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Vencidos por Programa</h4>
                <x-charts.bar-horizontal
                    :categories="$vencidosPorPrograma->pluck('clave')->toArray()"
                    :series="[['name' => 'Vencidos', 'data' => $vencidosPorPrograma->pluck('count')->toArray()]]"
                    :height="max(150, $vencidosPorPrograma->count() * 35)"
                />
            </div>
        </div>
        @endif

        @if($avances->isEmpty())
            <div class="text-center py-12 text-gray-500">
                No hay indicadores vencidos en este equipo.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Indicador</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operador</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha vencimiento</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($avances as $avance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $avance->indicador->nombre }}</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->capturador?->name ?? 'Sin asignar' }}</td>
                                <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->fecha_cierre?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
