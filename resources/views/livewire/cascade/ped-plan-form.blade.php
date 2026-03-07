<div>
    <button wire:click="create"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Nuevo Plan
    </button>

    <x-dialog-modal wire:model="showModal" maxWidth="lg">
        <x-slot name="title">
            {{ $mode === 'create' ? 'Crear Nuevo Plan' : 'Editar Plan' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="nombre" value="Nombre del Plan" />
                    <x-input id="nombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="nombre" />
                    @error('nombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-label for="periodo_inicio" value="Año Inicio" />
                        <x-input id="periodo_inicio"
                                 type="number"
                                 class="mt-1 block w-full"
                                 wire:model="periodo_inicio" />
                        @error('periodo_inicio')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="periodo_fin" value="Año Fin" />
                        <x-input id="periodo_fin"
                                 type="number"
                                 class="mt-1 block w-full"
                                 wire:model="periodo_fin" />
                        @error('periodo_fin')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <x-label for="nivel_gobierno" value="Nivel de Gobierno" />
                    <select id="nivel_gobierno"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            wire:model="nivel_gobierno">
                        <option value="estatal">Estatal</option>
                        <option value="municipal">Municipal</option>
                    </select>
                    @error('nivel_gobierno')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center">
                    <x-checkbox id="activo" wire:model="activo" />
                    <x-label for="activo" class="ml-2" value="Marcar como plan activo" />
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>

            @if($mode === 'edit')
                <x-danger-button wire:click="delete" wire:confirm="¿Está seguro de eliminar este plan? Esta acción no se puede deshacer." class="mr-3">
                    Eliminar
                </x-danger-button>
            @endif

            <x-button wire:click="save" class="bg-indigo-600 text-white">
                {{ $mode === 'create' ? 'Crear Plan' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
