<x-page.container :title="'Crear entrega de ' . $dataset->dataset_clave" :subtitle="$dataset->nombre">
    <form wire:submit.prevent="save">
        <x-forms.section title="Periodo de la entrega">
            <p class="text-sm text-gray-600 mb-3">
                Crea una entrega concreta de la plantilla para un periodo específico. La entrega se inicializa
                en estado <strong>borrador</strong> con los metadatos de la plantilla; podrás editarla antes
                de enviarla a revisión.
            </p>
            <div>
                <label for="periodo" class="block text-sm font-medium text-gray-700">Periodo *</label>
                <input type="text" id="periodo" wire:model="periodo" placeholder="2026 o 2026-Q1"
                       class="mt-1 block w-full rounded border-gray-300" />
                <p class="text-xs text-gray-500 mt-1">Formato: <code>YYYY</code> (anual) o <code>YYYY-Q1..Q4</code> (trimestral).</p>
                @error('periodo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
            </div>
        </x-forms.section>

        <div class="mt-4 flex gap-2">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                Crear entrega
            </button>
            <a href="{{ route('transparencia.datos-abiertos.show', $dataset) }}"
               class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                Cancelar
            </a>
        </div>
    </form>
</x-page.container>
