<div>
    <x-page.header>
        <x-slot name="title">Evidencias del avance</x-slot>
    </x-page.header>

    <x-page.container>
        @unless($avance->estaCongelado() || ! $avance->estado->esEditable())
            <form wire:submit="guardar" class="mb-8 space-y-4 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="text-lg font-medium text-gray-900">Adjuntar evidencia</h3>

                <div>
                    <label for="archivo" class="block text-sm font-medium text-gray-700">Archivo</label>
                    <input
                        type="file"
                        id="archivo"
                        wire:model="archivo"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100"
                    />
                    @error('archivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-500">PDF, Excel, Word, JPG o PNG. Maximo 10 MB.</p>
                </div>

                <div>
                    <label for="nombre_documento" class="block text-sm font-medium text-gray-700">Nombre del documento</label>
                    <input
                        type="text"
                        id="nombre_documento"
                        wire:model="nombre_documento"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Ej. Padron de beneficiarios Q1"
                    />
                    @error('nombre_documento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="area_generadora" class="block text-sm font-medium text-gray-700">Area generadora</label>
                        <input
                            type="text"
                            id="area_generadora"
                            wire:model="area_generadora"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        @error('area_generadora') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="fecha_documento" class="block text-sm font-medium text-gray-700">Fecha del documento</label>
                        <input
                            type="date"
                            id="fecha_documento"
                            wire:model="fecha_documento"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        />
                        @error('fecha_documento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        <div wire:loading wire:target="guardar" class="mr-2">
                            <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        Subir evidencia
                    </button>
                </div>
            </form>
        @else
            <div class="mb-8 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                No se pueden adjuntar evidencias porque el avance esta
                @if($avance->estaCongelado()) congelado @else en un estado no editable @endif.
            </div>
        @endunless

        <h3 class="mb-4 text-lg font-medium text-gray-900">Evidencias adjuntas</h3>

        @if($evidencias->isEmpty())
            <div class="text-center py-8 text-gray-500">
                No hay evidencias adjuntas.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Archivo</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tamano</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subido por</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($evidencias as $evidencia)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $evidencia->nombre_documento }}</td>
                                <td class="hidden sm:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $evidencia->nombre_archivo }}</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($evidencia->tamano_bytes / 1024, 1) }} KB</td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $evidencia->subidoPor?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                                    <a
                                        href="{{ route('tracking.evidencia.download', $evidencia) }}"
                                        class="text-indigo-600 hover:text-indigo-900"
                                    >Descargar</a>

                                    @unless($avance->estaCongelado())
                                        <button
                                            wire:click="eliminar({{ $evidencia->id }})"
                                            wire:confirm="¿Eliminar esta evidencia?"
                                            class="text-red-600 hover:text-red-900"
                                        >Eliminar</button>
                                    @endunless
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page.container>
</div>
