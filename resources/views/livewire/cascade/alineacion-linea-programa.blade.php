<div class="space-y-6">

    @if(session('message'))
        <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-4">
            <p class="text-sm text-green-700">{{ session('message') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-4">
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <div class="flex justify-end">
        <x-button wire:click="toggleForm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Alineación
        </x-button>
    </div>

    @if($showForm)
        <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Crear Nueva Alineación Línea ↔ Programa Derivado</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Selector Línea de Acción --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Línea de Acción PED</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchLinea"
                           placeholder="Buscar línea de acción..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->linea_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->linea_resultados as $linea)
                                <button wire:click="selectLinea({{ $linea->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedLineaId === $linea->id ? 'bg-blue-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">{{ $linea->clave_completa }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($linea->descripcion, 50) }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedLineaId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-blue-100 text-blue-800 text-sm">
                            Seleccionado: {{ App\Models\PedLineaAccion::find($selectedLineaId)->clave_completa }}
                        </div>
                    @endif
                </div>

                {{-- Selector Programa Derivado --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Objetivo de Programa Derivado</label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPrograma"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->programa_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->programa_resultados as $prog)
                                <button wire:click="selectPrograma({{ $prog->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedProgramaId === $prog->id ? 'bg-purple-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">{{ $prog->clave_completa }}</div>
                                    <div class="text-xs text-gray-500">{{ $prog->programa->nombre }} ({{ $prog->programa->tipo->label() }})</div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedProgramaId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-purple-100 text-purple-800 text-sm">
                            Seleccionado: {{ App\Models\ProgramaDerivadoObjetivo::find($selectedProgramaId)->clave_completa }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex justify-end space-x-3">
                <x-secondary-button wire:click="toggleForm">Cancelar</x-secondary-button>
                <x-button wire:click="crearAlineacion">
                    Crear Alineación
                </x-button>
            </div>
        </div>
    @endif

    <div>
        <h4 class="text-sm font-medium text-gray-900 mb-4">
            Alineaciones Existentes ({{ $this->alineaciones->count() }})
        </h4>

        @if($this->alineaciones->isEmpty())
            <div class="text-center py-8 text-gray-500">No hay alineaciones registradas. Cree la primera.</div>
        @else
            <div class="space-y-3">
                @foreach($this->alineaciones as $linea)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Línea</span>
                            <span class="text-sm font-medium text-gray-900">{{ $linea->clave_completa }}</span>
                            <button wire:click="$dispatch('verCadena', { lineaId: {{ $linea->id }} })"
                                    class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                                Ver cadena completa
                            </button>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ Str::limit($linea->descripcion, 100) }}</p>

                        <div class="space-y-2">
                            @foreach($linea->programasDerivadosObjetivos as $prog)
                                <div class="flex items-center justify-between bg-white rounded border p-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">{{ $prog->clave_completa }}</span>
                                        <span class="text-sm text-gray-700">{{ $prog->programa->nombre }}</span>
                                    </div>
                                    <button wire:click="eliminarAlineacion({{ $linea->id }}, {{ $prog->id }})"
                                            wire:confirm="¿Eliminar esta alineación?"
                                            class="text-red-600 hover:text-red-900 text-xs font-medium">
                                        Eliminar
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
