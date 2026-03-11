<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 5 — Embudo de Poblaciones"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Programas', 'url' => route('mml.programas')],
        ['label' => $programa->nombre],
        ['label' => 'Etapa 5 — Poblaciones'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="5" />

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Visualización tipo embudo --}}
        @if ($referencia_cantidad && $potencial_cantidad && $objetivo_cantidad)
            <div class="mb-6 mx-auto max-w-xl">
                @php
                    $maxW = 100;
                    $potW = $referencia_cantidad > 0 ? round(($potencial_cantidad / $referencia_cantidad) * 100) : 80;
                    $objW = $referencia_cantidad > 0 ? round(($objetivo_cantidad / $referencia_cantidad) * 100) : 60;
                @endphp

                <div class="space-y-1">
                    <div class="rounded-t-lg bg-blue-100 border border-blue-200 px-4 py-3 text-center" style="width: {{ $maxW }}%">
                        <p class="text-xs font-semibold uppercase text-blue-600">Referencia</p>
                        <p class="text-lg font-bold text-blue-800">{{ number_format($referencia_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                    <div class="bg-amber-100 border border-amber-200 px-4 py-3 text-center mx-auto" style="width: {{ $potW }}%">
                        <p class="text-xs font-semibold uppercase text-amber-600">Potencial</p>
                        <p class="text-lg font-bold text-amber-800">{{ number_format($potencial_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                    <div class="rounded-b-lg bg-green-100 border border-green-200 px-4 py-3 text-center mx-auto" style="width: {{ $objW }}%">
                        <p class="text-xs font-semibold uppercase text-green-600">Objetivo</p>
                        <p class="text-lg font-bold text-green-800">{{ number_format($objetivo_cantidad) }} {{ $unidad_medida }}</p>
                    </div>
                </div>

                <p class="mt-2 text-center text-xs text-gray-500">
                    La Población Atendida se calculará desde el Padrón de Beneficiarios durante la operación del programa.
                </p>
            </div>
        @endif

        {{-- Formulario --}}
        <x-forms.section
            title="Poblaciones del programa"
            description="Define las poblaciones de referencia, potencial y objetivo. Las cantidades deben cumplir: Objetivo ≤ Potencial ≤ Referencia."
        >
            <div class="space-y-6">
                {{-- Unidad de medida --}}
                <div>
                    <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de medida</label>
                    <input
                        type="text"
                        wire:model="unidad_medida"
                        id="unidad_medida"
                        placeholder="Ej: Niños, Familias, MIPYMES"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    >
                    @error('unidad_medida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Población de Referencia --}}
                <div class="rounded-md border border-blue-200 bg-blue-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-blue-800">Población de Referencia</h4>
                    <p class="text-xs text-blue-600">Población total del ámbito geográfico del programa.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="referencia_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="referencia_cantidad" id="referencia_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('referencia_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="referencia_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                            <input type="text" wire:model="referencia_fuente" id="referencia_fuente"
                                placeholder="Ej: INEGI Censo 2020"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Población Potencial --}}
                <div class="rounded-md border border-amber-200 bg-amber-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-amber-800">Población Potencial</h4>
                    <p class="text-xs text-amber-600">Población que presenta el problema o necesidad que el programa busca atender.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="potencial_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="potencial_cantidad" id="potencial_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('potencial_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="potencial_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                            <input type="text" wire:model="potencial_fuente" id="potencial_fuente"
                                placeholder="Ej: CONEVAL 2023"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Población Objetivo --}}
                <div class="rounded-md border border-green-200 bg-green-50 p-4 space-y-3">
                    <h4 class="text-sm font-semibold text-green-800">Población Objetivo</h4>
                    <p class="text-xs text-green-600">Subconjunto de la población potencial que el programa atenderá en este ejercicio fiscal (limitado por capacidad y presupuesto).</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="objetivo_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                            <input type="number" wire:model="objetivo_cantidad" id="objetivo_cantidad" min="1"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('objetivo_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="objetivo_justificacion" class="block text-sm font-medium text-gray-700">Justificación (opcional)</label>
                            <input type="text" wire:model="objetivo_justificacion" id="objetivo_justificacion"
                                placeholder="¿Por qué este recorte respecto a la potencial?"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button
                        wire:click="guardar"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="guardar">Guardar poblaciones</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </x-forms.section>

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa4', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa6', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Alineación Estratégica →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
