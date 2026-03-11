<x-page.container title="Programas Presupuestarios">
    <x-slot name="actions">
        <button wire:click="toggleFormulario" type="button"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Nuevo programa
        </button>
        <a href="{{ route('mml.importar.nuevo') }}"
           class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Importar programa
        </a>
    </x-slot>

    @if ($mostrarFormulario)
        <div class="mb-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
            <h3 class="text-sm font-semibold text-indigo-800 mb-3">Crear nuevo programa</h3>
            <form wire:submit="crearPrograma" class="flex flex-col sm:flex-row items-start sm:items-end gap-3">
                <div class="flex-1 w-full">
                    <label for="nuevoClave" class="block text-xs font-medium text-gray-700">Clave</label>
                    <input type="text" wire:model="nuevoClave" id="nuevoClave" placeholder="Ej: E-015"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('nuevoClave') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex-[3] w-full">
                    <label for="nuevoNombre" class="block text-xs font-medium text-gray-700">Nombre del programa</label>
                    <input type="text" wire:model="nuevoNombre" id="nuevoNombre" placeholder="Ej: Programa de Fomento a las MYPES"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    @error('nuevoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="flex gap-2 shrink-0">
                    <button type="submit" wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                        <span wire:loading.remove wire:target="crearPrograma">Crear e iniciar MML</span>
                        <span wire:loading wire:target="crearPrograma">Creando...</span>
                    </button>
                    <button type="button" wire:click="toggleFormulario"
                        class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-xs text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        @if ($programas->isEmpty())
            <div class="p-8 text-center">
                <p class="text-gray-500 mb-4">No hay programas registrados para esta unidad responsable.</p>
                <div class="flex items-center justify-center gap-4">
                    <button wire:click="toggleFormulario" class="text-indigo-600 hover:text-indigo-800 font-medium text-sm">
                        Crear un programa nuevo
                    </button>
                    <span class="text-gray-300">|</span>
                    <a href="{{ route('mml.importar.nuevo') }}" class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                        Importar un programa existente
                    </a>
                </div>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clave</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Ejercicio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden md:table-cell">Origen</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($programas as $programa)
                        <tr>
                            <td class="px-4 py-3 text-sm font-mono text-gray-900">{{ $programa->clave }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $programa->nombre }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 hidden md:table-cell">{{ $programa->ejercicio_fiscal ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm hidden md:table-cell">
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
                            <td class="px-4 py-3 text-sm text-right">
                                <div class="flex flex-col sm:flex-row sm:justify-end gap-1 sm:space-x-2">
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
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-page.container>
