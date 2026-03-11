<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 2 — Árbol del Problema"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 2: Árbol del Problema'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="2" />

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @include('livewire.mml.partials.mir-impact-warning', ['tieneMir' => $tieneMir])

        @if (!$arbol)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes definir el problema central en la
                    <a href="{{ route('mml.etapa1', $programa) }}" class="font-medium underline">Etapa 1</a>.
                </p>
            </div>
        @else
            @php
                $problemaCentral = $arbol->nodos->where('tipo_nodo', \App\Enums\TipoNodo::PROBLEMA_CENTRAL)->first()
                    ?? $arbol->nodos->where('tipo_nodo.value', 'problema_central')->first();
                $efectosDirectos = $arbol->nodos->where('parent_id', $problemaCentral?->id)->where('tipo_nodo.value', 'efecto_directo')->sortBy('orden');
                $causasDirectas = $arbol->nodos->where('parent_id', $problemaCentral?->id)->where('tipo_nodo.value', 'causa_directa')->sortBy('orden');
            @endphp

            <div class="space-y-8">
                {{-- SECCIÓN: EFECTOS (arriba) --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        <x-ui.help-label glossary="efecto_directo" class="text-lg font-semibold text-gray-900">
                            Efectos
                        </x-ui.help-label>
                    </h3>
                    <div class="space-y-3">
                        @foreach ($efectosDirectos as $efecto)
                            <div class="ml-4">
                                @include('livewire.mml.partials.nodo-card', ['nodo' => $efecto])

                                @foreach ($arbol->nodos->where('parent_id', $efecto->id)->where('tipo_nodo.value', 'efecto_indirecto')->sortBy('orden') as $indirecto)
                                    <div class="ml-8 mt-2">
                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecto])
                                    </div>
                                @endforeach

                                <button
                                    wire:click="agregarNodo({{ $efecto->id }}, 'efecto_indirecto')"
                                    class="ml-8 mt-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    + Efecto indirecto
                                </button>
                            </div>
                        @endforeach

                        @if ($problemaCentral)
                            <button
                                wire:click="agregarNodo({{ $problemaCentral->id }}, 'efecto_directo')"
                                class="ml-4 text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                + Agregar efecto directo
                            </button>
                        @endif
                    </div>
                </div>

                {{-- SECCIÓN: PROBLEMA CENTRAL (centro) --}}
                @if ($problemaCentral)
                    <div class="flex justify-center">
                        <div class="w-full max-w-2xl rounded-lg border-2 border-red-300 bg-red-50 p-4 text-center">
                            <span class="text-xs font-semibold uppercase tracking-wide text-red-600">Problema Central</span>
                            <p class="mt-1 text-lg font-medium text-red-900">{{ $problemaCentral->descripcion }}</p>
                        </div>
                    </div>
                @endif

                {{-- SECCIÓN: CAUSAS (abajo) --}}
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">
                        <x-ui.help-label glossary="causa_directa" class="text-lg font-semibold text-gray-900">
                            Causas
                        </x-ui.help-label>
                    </h3>
                    <div class="space-y-3">
                        @foreach ($causasDirectas as $causa)
                            <div class="ml-4">
                                @include('livewire.mml.partials.nodo-card', ['nodo' => $causa])

                                @foreach ($arbol->nodos->where('parent_id', $causa->id)->where('tipo_nodo.value', 'causa_indirecta')->sortBy('orden') as $indirecta)
                                    <div class="ml-8 mt-2">
                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecta])
                                    </div>
                                @endforeach

                                <button
                                    wire:click="agregarNodo({{ $causa->id }}, 'causa_indirecta')"
                                    class="ml-8 mt-1 text-xs text-gray-500 hover:text-gray-700"
                                >
                                    + Causa indirecta
                                </button>
                            </div>
                        @endforeach

                        @if ($problemaCentral)
                            <button
                                wire:click="agregarNodo({{ $problemaCentral->id }}, 'causa_directa')"
                                class="ml-4 text-sm text-indigo-600 hover:text-indigo-800"
                            >
                                + Agregar causa directa
                            </button>
                        @endif
                    </div>
                </div>

                {{-- FORMULARIO INLINE para nuevo nodo --}}
                @if ($mostrarFormNuevoNodo)
                    <div class="fixed inset-0 z-10 flex items-center justify-center bg-gray-900/50">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h4 class="text-sm font-semibold text-gray-900">
                                Agregar {{ str_replace('_', ' ', $tipoNuevoNodo) }}
                            </h4>
                            <textarea
                                wire:model="nuevoNodoDescripcion"
                                rows="3"
                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Describe el nodo..."
                            ></textarea>
                            @error('nuevoNodoDescripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex justify-end gap-2">
                                <button wire:click="cancelarNuevoNodo" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">
                                    Cancelar
                                </button>
                                <button wire:click="guardarNuevoNodo" class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- FORMULARIO de edición --}}
                @if ($editNodoId)
                    <div class="fixed inset-0 z-10 flex items-center justify-center bg-gray-900/50">
                        <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                            <h4 class="text-sm font-semibold text-gray-900">Editar nodo</h4>
                            <textarea
                                wire:model="editNodoDescripcion"
                                rows="3"
                                class="mt-2 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                            @error('editNodoDescripcion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-3 flex justify-end gap-2">
                                <button wire:click="cancelarEdicion" class="rounded-md bg-gray-100 px-3 py-2 text-sm text-gray-700 hover:bg-gray-200">
                                    Cancelar
                                </button>
                                <button wire:click="actualizarNodo" class="rounded-md bg-indigo-600 px-3 py-2 text-sm text-white hover:bg-indigo-500">
                                    Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Botón de sugerencias IA --}}
                <div class="border-t pt-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Asistencia de IA</h4>
                    <div class="flex gap-2">
                        <button
                            wire:click="sugerirConIa('causa')"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-purple-50 px-3 py-2 text-sm text-purple-700 ring-1 ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sugerirConIa('causa')">Sugerir causas</span>
                            <span wire:loading wire:target="sugerirConIa('causa')">Generando...</span>
                        </button>
                        <button
                            wire:click="sugerirConIa('efecto')"
                            wire:loading.attr="disabled"
                            class="rounded-md bg-purple-50 px-3 py-2 text-sm text-purple-700 ring-1 ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="sugerirConIa('efecto')">Sugerir efectos</span>
                            <span wire:loading wire:target="sugerirConIa('efecto')">Generando...</span>
                        </button>
                    </div>

                    @if (!empty($sugerenciasIa))
                        <div class="mt-3 rounded-md bg-purple-50 p-4 border border-purple-200">
                            <p class="text-sm font-medium text-purple-800">Sugerencias de IA:</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($sugerenciasIa as $sugerencia)
                                    <li class="flex items-center justify-between text-sm text-purple-700">
                                        <span>{{ $sugerencia }}</span>
                                        @if ($problemaCentral)
                                            <button
                                                wire:click="agregarSugerencia('{{ addslashes($sugerencia) }}', {{ $problemaCentral->id }}, 'causa_directa')"
                                                class="ml-2 text-xs text-indigo-600 hover:text-indigo-800 font-medium"
                                            >
                                                + Agregar
                                            </button>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa1', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa3', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Árbol de Objetivos →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
