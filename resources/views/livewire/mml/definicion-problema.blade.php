<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 1 — Definición del Problema"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 1: Problema'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="1" />

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

        <x-forms.section
            title="Problema Central"
            description="Describe la situación no deseada que el programa busca atender. Debe ser una condición negativa, no la ausencia de una solución."
        >
            <div class="space-y-4">
                <div>
                    <x-ui.help-label for="descripcion" glossary="problema_central" class="block text-sm font-medium text-gray-700">
                        Descripción del problema
                    </x-ui.help-label>
                    <textarea
                        wire:model="descripcion"
                        id="descripcion"
                        rows="4"
                        maxlength="1000"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Ej: Alto índice de deserción escolar en educación media superior en el estado"
                    ></textarea>
                    <div class="mt-1 flex justify-between">
                        <div>
                            @error('descripcion')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <p class="text-sm text-gray-500">
                            {{ strlen($descripcion) }}/1000 caracteres
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        wire:click="validarConIa"
                        wire:loading.attr="disabled"
                        wire:target="validarConIa"
                        type="button"
                        class="inline-flex items-center rounded-md bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 ring-1 ring-inset ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="validarConIa">Validar con IA</span>
                        <span wire:loading wire:target="validarConIa">Validando...</span>
                    </button>
                    <span class="text-xs text-gray-500">Opcional — la IA sugiere mejoras de redacción</span>
                </div>

                @if ($resultadoValidacion)
                    <div class="rounded-md {{ $resultadoValidacion['is_valid'] ? 'bg-green-50 border-green-200' : 'bg-amber-50 border-amber-200' }} border p-4">
                        <h4 class="text-sm font-medium {{ $resultadoValidacion['is_valid'] ? 'text-green-800' : 'text-amber-800' }}">
                            {{ $resultadoValidacion['is_valid'] ? 'Redacción válida' : 'Se encontraron observaciones' }}
                        </h4>

                        @if (!empty($resultadoValidacion['issues']))
                            <ul class="mt-2 list-disc list-inside text-sm text-amber-700">
                                @foreach ($resultadoValidacion['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        @endif

                        @if (!empty($sugerenciaIa))
                            <div class="mt-3 rounded bg-white p-3 border border-gray-200">
                                <p class="text-sm font-medium text-gray-700">Sugerencia:</p>
                                <p class="mt-1 text-sm text-gray-600 italic">{{ $sugerenciaIa }}</p>
                                <button
                                    wire:click="aceptarSugerencia"
                                    type="button"
                                    class="mt-2 inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-300 hover:bg-indigo-100"
                                >
                                    Usar esta sugerencia
                                </button>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </x-forms.section>

        <x-slot:footer>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                Cancelar
            </a>
            <button wire:click="guardar" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Guardar Problema
            </button>
            <a href="{{ route('mml.etapa2', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Árbol de Problemas →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
