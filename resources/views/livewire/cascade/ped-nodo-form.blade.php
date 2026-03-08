<div>
    <x-forms.section :title="($nodoId ? 'Editar ' : 'Nuevo ') . $this->getTipoLabel()">

        @if(in_array($tipo, ['eje', 'tema']))
            <div class="col-span-2">
                <x-label for="numero" value="Número" />
                <x-input id="numero" type="text" class="mt-1 block w-full"
                         wire:model="numero" placeholder="Ej: 1, 1.1" />
                @error('numero')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="col-span-4">
                <x-label for="nombre" value="Nombre" />
                <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" />
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @else
            <div class="col-span-2">
                <x-label for="clave" value="Clave" />
                <x-input id="clave" type="text" class="mt-1 block w-full"
                         wire:model="clave" placeholder="Ej: 1, 1.1" />
                @error('clave')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div class="col-span-6">
            <x-label for="descripcion" value="Descripción" />
            <textarea id="descripcion"
                      class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                      rows="3"
                      wire:model="descripcion"
                      maxlength="500"></textarea>
            <p class="mt-1 text-xs text-gray-500">{{ strlen($descripcion) }}/500</p>
            @error('descripcion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Advertencia de dependientes --}}
        @php($dependientes = $this->getDependientes())
        @if(!empty($dependientes) && $nodoId)
            <div class="col-span-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">
                    <strong>Advertencia:</strong> Este elemento tiene:
                    @foreach($dependientes as $tipoKey => $cantidad)
                        {{ $cantidad }} {{ $tipoKey }}{{ !$loop->last ? ',' : '' }}
                    @endforeach.
                    Al eliminarlo, todos sus dependientes serán eliminados también.
                </p>
            </div>
        @endif

    </x-forms.section>

    <div class="flex justify-end space-x-3 mb-4">
        <x-ui.button.secondary href="{{ route('cascade.ped.index') }}">
            Cancelar
        </x-ui.button.secondary>
        <x-ui.button.primary wire:click="save" type="button">
            {{ $nodoId ? 'Guardar Cambios' : 'Crear ' . $this->getTipoLabel() }}
        </x-ui.button.primary>
    </div>

    {{-- Botón Eliminar (solo en edición) + Modal de confirmación --}}
    @if($nodoId)
        <div class="flex justify-start mt-4" x-data>
            <x-ui.button.danger
                @click="$dispatch('open-confirm-nodo-{{ $nodoId }}')">
                Eliminar {{ $this->getTipoLabel() }}
            </x-ui.button.danger>
        </div>

        <x-modals.confirm
            id="nodo-{{ $nodoId }}"
            :title="'¿Eliminar ' . $this->getTipoLabel() . '?'"
            message="Esta acción no se puede deshacer. Se eliminarán todos los elementos dependientes."
            confirmText="Sí, eliminar"
        />

        <div x-data
             x-on:confirmed-nodo-{{ $nodoId }}.window="$wire.delete()">
        </div>
    @endif
</div>
