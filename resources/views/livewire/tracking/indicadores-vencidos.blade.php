<div>
    <x-page.header>
        <x-slot name="title">Indicadores vencidos</x-slot>
    </x-page.header>

    <x-page.container>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Operador</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha vencimiento</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($avances as $avance)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $avance->indicador->nombre }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->capturador?->name ?? 'Sin asignar' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $avance->metaPeriodo->fecha_cierre?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page.container>
</div>
