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

    {{-- Botón para mostrar formulario --}}
    <div class="flex justify-end">
        <x-button wire:click="toggleForm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Nueva Alineación
        </x-button>
    </div>

    {{-- Formulario de creación --}}
    @if($showForm)
        <div class="bg-gray-50 rounded-lg p-6 border-2 border-dashed border-gray-300">
            <h4 class="text-sm font-medium text-gray-900 mb-4">Crear Nueva Alineación PED ↔ PND</h4>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Selector PED --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo Estratégico PED
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPed"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->ped_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->ped_resultados as $ped)
                                <button wire:click="selectPed({{ $ped->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedPedId === $ped->id ? 'bg-indigo-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">{{ $ped->clave_completa }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($ped->descripcion, 50) }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedPedId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-indigo-100 text-indigo-800 text-sm">
                            Seleccionado: {{ App\Models\PedObjetivoEstrategico::find($selectedPedId)->clave_completa }}
                        </div>
                    @endif
                </div>

                {{-- Selector PND --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Objetivo PND
                    </label>
                    <input type="text"
                           wire:model.live.debounce.300ms="searchPnd"
                           placeholder="Buscar objetivo..."
                           class="w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">

                    @if($this->pnd_resultados->isNotEmpty())
                        <div class="mt-2 bg-white border rounded-md shadow-sm max-h-48 overflow-y-auto">
                            @foreach($this->pnd_resultados as $pnd)
                                <button wire:click="selectPnd({{ $pnd->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-gray-100 {{ $selectedPndId === $pnd->id ? 'bg-green-50' : '' }}">
                                    <div class="text-sm font-medium text-gray-900">{{ $pnd->clave }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($pnd->descripcion, 50) }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($selectedPndId)
                        <div class="mt-2 inline-flex items-center px-2 py-1 rounded bg-green-100 text-green-800 text-sm">
                            Seleccionado: {{ App\Models\PndObjetivo::find($selectedPndId)->clave }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-4 flex justify-end space-x-3">
                <x-secondary-button wire:click="toggleForm">
                    Cancelar
                </x-secondary-button>

                <x-button wire:click="crearAlineacion">
                    Crear Alineación
                </x-button>
            </div>
        </div>
    @endif

    {{-- Lista de alineaciones existentes --}}
    <div>
        <h4 class="text-sm font-medium text-gray-900 mb-4">
            Alineaciones Existentes ({{ $this->alineaciones->count() }})
        </h4>

        @if($this->alineaciones->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No hay alineaciones registradas. Cree la primera.
            </div>
        @else
            <div class="space-y-3">
                @foreach($this->alineaciones as $ped)
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center space-x-2 mb-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">PED</span>
                            <span class="text-sm font-medium text-gray-900">{{ $ped->clave_completa }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-3">{{ Str::limit($ped->descripcion, 100) }}</p>

                        <div class="space-y-2">
                            @foreach($ped->pndObjetivos as $pnd)
                                <div class="flex items-center justify-between bg-white rounded border p-2">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ $pnd->clave }}</span>
                                        <span class="text-sm text-gray-700">{{ Str::limit($pnd->descripcion, 50) }}</span>
                                    </div>
                                    <button wire:click="eliminarAlineacion({{ $ped->id }}, {{ $pnd->id }})"
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
