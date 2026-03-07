<div x-data
     x-on:edit-nodo.window="$wire.editByParams($event.detail.tipo, $event.detail.nodoId)"
     x-on:create-nodo.window="$wire.createByParams($event.detail.tipo, $event.detail.parentId)">

    <x-dialog-modal wire:model="showModal" maxWidth="md">
        <x-slot name="title">
            {{ $mode === 'create' ? 'Crear ' . $this->getTipoLabel() : 'Editar ' . $this->getTipoLabel() }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                @if(in_array($tipo, ['eje', 'tema']))
                    <div>
                        <x-label for="numero" value="Número" />
                        <x-input id="numero"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="numero"
                                 placeholder="Ej: 1, 2, 1.1" />
                        @error('numero')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-label for="nombre" value="Nombre" />
                        <x-input id="nombre"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="nombre" />
                        @error('nombre')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <div>
                        <x-label for="clave" value="Clave" />
                        <x-input id="clave"
                                 type="text"
                                 class="mt-1 block w-full"
                                 wire:model="clave"
                                 placeholder="Ej: 1, 2, 1.1" />
                        @error('clave')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <x-label for="descripcion" value="Descripción" />
                    <textarea id="descripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="descripcion"
                              maxlength="500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ strlen($descripcion) }}/500 caracteres
                    </p>
                    @error('descripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Advertencia de dependientes --}}
                @php($dependientes = $this->getDependientes())
                @if(!empty($dependientes) && $mode === 'edit')
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Advertencia:</strong> Este elemento tiene dependientes:
                                </p>
                                <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                                    @foreach($dependientes as $tipoKey => $cantidad)
                                        <li>{{ $cantidad }} {{ $tipoKey }}</li>
                                    @endforeach
                                </ul>
                                <p class="mt-1 text-sm text-yellow-700">
                                    Al eliminar este elemento, todos sus dependientes serán eliminados también.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>

            @if($mode === 'edit')
                <x-danger-button wire:click="delete" class="mr-3">
                    Eliminar
                </x-danger-button>
            @endif

            <x-button wire:click="save" class="bg-indigo-600 text-white">
                {{ $mode === 'create' ? 'Crear' : 'Guardar' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>
</div>
