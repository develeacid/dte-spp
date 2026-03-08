<x-page.container title="Importaciones">
    <x-slot name="actions">
        <a href="{{ route('mml.importar.nuevo') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Nueva importación
        </a>
    </x-slot>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if ($reportes->isEmpty())
            <div class="p-6 text-center text-gray-500">
                No hay importaciones registradas. Comienza subiendo un archivo MIR.
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archivo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Formato</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Creador</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($reportes as $reporte)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $reporte->archivo_original }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 uppercase">{{ $reporte->formato }}</td>
                            <td class="px-4 py-3 text-sm">
                                @php
                                    $badgeClass = match ($reporte->estado) {
                                        'pendiente' => 'bg-yellow-100 text-yellow-800',
                                        'procesado' => 'bg-green-100 text-green-800',
                                        'descartado' => 'bg-gray-100 text-gray-800',
                                        default => 'bg-gray-100 text-gray-600',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                    {{ ucfirst($reporte->estado) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $reporte->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $reporte->creador?->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-right">
                                @php
                                    $url = $this->urlRetomar($reporte);
                                @endphp
                                @if ($url)
                                    <a href="{{ $url }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        {{ $reporte->estado === 'procesado' ? 'Ver programa' : 'Retomar' }}
                                    </a>
                                @elseif ($reporte->estado === 'descartado')
                                    <span class="text-gray-400 text-sm">Descartado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-page.container>
