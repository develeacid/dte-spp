<x-page.container title="Programas Presupuestarios">
    <x-slot name="actions">
        <a href="{{ route('mml.importar.nuevo') }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Importar programa
        </a>
    </x-slot>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if ($programas->isEmpty())
            <div class="p-8 text-center">
                <p class="text-gray-500 mb-4">No hay programas registrados para esta unidad responsable.</p>
                <a href="{{ route('mml.importar.nuevo') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                    Importar un programa existente
                </a>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clave</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ejercicio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origen</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($programas as $programa)
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $programa->clave }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $programa->nombre }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $programa->ejercicio_fiscal ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($programa->origen)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $programa->origen === \App\Enums\OrigenPrograma::IMPORTADO ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $programa->origen->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm">
                                @if($programa->estado)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $programa->estado->colorClass() }}">
                                        {{ $programa->estado->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-right space-x-2">
                                <a href="{{ route('mml.etapa1', $programa) }}"
                                   class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                    MML
                                </a>
                                @if($programa->mirNiveles()->exists())
                                    <a href="{{ route('mml.mir', $programa) }}"
                                       class="text-emerald-600 hover:text-emerald-800 font-medium text-sm">
                                        MIR
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-page.container>
