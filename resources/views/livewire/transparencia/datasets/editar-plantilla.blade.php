<x-page.container :title="'Editar plantilla ' . $dataset->dataset_clave" subtitle="Solo el RDA puede editar plantillas del catálogo.">
    <form wire:submit.prevent="save">
        <x-forms.section title="Plantilla">
            <div class="space-y-4">
                <div>
                    <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre *</label>
                    <input type="text" id="nombre" wire:model="nombre" maxlength="255"
                           class="mt-1 block w-full rounded border-gray-300" />
                    @error('nombre') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
                    <textarea id="descripcion" wire:model="descripcion" rows="4" maxlength="5000"
                              class="mt-1 block w-full rounded border-gray-300"></textarea>
                    @error('descripcion') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="dcat_metadata_json" class="block text-sm font-medium text-gray-700">DCAT Metadata (JSON)</label>
                    <textarea id="dcat_metadata_json" wire:model="dcat_metadata_json" rows="10"
                              class="mt-1 block w-full rounded border-gray-300 font-mono text-xs"></textarea>
                    @error('dcat_metadata_json') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                </div>
            </div>
        </x-forms.section>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                Guardar plantilla
            </button>
            <a href="{{ route('transparencia.datos-abiertos.show', $dataset) }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                Cancelar
            </a>
        </div>
    </form>
</x-page.container>
