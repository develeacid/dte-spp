<div>
    <x-page.header>
        <x-slot name="title">Mis indicadores pendientes</x-slot>
    </x-page.header>

    <x-page.container fluid>
        @if($avances->isEmpty())
            <div class="text-center py-12 text-gray-500">
                No tienes indicadores pendientes de captura.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Indicador</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha cierre</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($avances as $avance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $avance->indicador->nombre }}</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->fecha_cierre?->format('d/m/Y') }}</td>
                                <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap">
                                    @php $diasRestantes = (int) now()->diffInDays($avance->metaPeriodo->fecha_cierre, false); @endphp
                                    @if($diasRestantes <= 3)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $diasRestantes }} días</span>
                                    @elseif($diasRestantes <= 7)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $diasRestantes }} días</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $diasRestantes }} días</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('tracking.captura', $avance) }}"
                                           class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Capturar
                                        </a>
                                        <a href="{{ route('tracking.indicador.detalle', $avance->indicador) }}"
                                           class="text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                            Ver histórico
                                        </a>
                                    </div>
                                </td>
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
