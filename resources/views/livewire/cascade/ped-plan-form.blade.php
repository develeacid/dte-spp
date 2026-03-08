<div>
    <x-forms.section title="Datos del Plan">

        <div class="col-span-6">
            <x-label for="nombre" value="Nombre del Plan" />
            <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" />
            @error('nombre')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="periodo_inicio" value="Año Inicio" />
            <x-input id="periodo_inicio" type="number" class="mt-1 block w-full" wire:model="periodo_inicio" />
            @error('periodo_inicio')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="periodo_fin" value="Año Fin" />
            <x-input id="periodo_fin" type="number" class="mt-1 block w-full" wire:model="periodo_fin" />
            @error('periodo_fin')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-3">
            <x-label for="nivel_gobierno" value="Nivel de Gobierno" />
            <select id="nivel_gobierno"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    wire:model="nivel_gobierno">
                <option value="estatal">Estatal</option>
                <option value="municipal">Municipal</option>
            </select>
            @error('nivel_gobierno')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="col-span-6 flex items-center">
            <x-checkbox id="activo" wire:model="activo" />
            <x-label for="activo" class="ml-2" value="Marcar como plan activo" />
        </div>

    </x-forms.section>

    <div class="flex justify-end space-x-3">
        <x-ui.button.secondary href="{{ route('cascade.ped.index') }}">
            Cancelar
        </x-ui.button.secondary>
        <x-ui.button.primary wire:click="save" type="button">
            {{ $plan && $plan->exists ? 'Guardar Cambios' : 'Crear Plan' }}
        </x-ui.button.primary>
    </div>
</div>
