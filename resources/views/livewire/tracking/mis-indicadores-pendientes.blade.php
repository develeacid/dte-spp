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
                                    @php $diasRestantes = now()->diffInDays($avance->metaPeriodo->fecha_cierre, false); @endphp
                                    @if($diasRestantes <= 3)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $diasRestantes }} dias</span>
                                    @elseif($diasRestantes <= 7)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $diasRestantes }} dias</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $diasRestantes }} dias</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="text-gray-400 text-xs">Captura no disponible aun</span></td>
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
