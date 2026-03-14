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
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @include('livewire.mml.partials.mir-impact-warning', ['tieneMir' => $tieneMir])

        @if (!$arbol)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-amber-700">
                    Primero debes definir el problema central en la
                    <a href="{{ route('mml.etapa1', $programa) }}" class="font-semibold underline hover:text-amber-900">Etapa 1</a>.
                </p>
            </div>
        @else
            @php
                $problemaCentral = $arbol->nodos->where('tipo_nodo', \App\Enums\TipoNodo::PROBLEMA_CENTRAL)->first()
                    ?? $arbol->nodos->where('tipo_nodo.value', 'problema_central')->first();
                $efectosDirectos = $arbol->nodos->where('parent_id', $problemaCentral?->id)->where('tipo_nodo.value', 'efecto_directo')->sortBy('orden');
                $causasDirectas = $arbol->nodos->where('parent_id', $problemaCentral?->id)->where('tipo_nodo.value', 'causa_directa')->sortBy('orden');
                $totalCausas = $arbol->nodos->whereIn('tipo_nodo.value', ['causa_directa', 'causa_indirecta'])->count();
                $totalEfectos = $arbol->nodos->whereIn('tipo_nodo.value', ['efecto_directo', 'efecto_indirecto'])->count();
            @endphp

            {{-- Main content: tree + AI sidebar --}}
            <div class="lg:flex lg:gap-6">
                {{-- Left: Tree visualization --}}
                <div class="flex-1 min-w-0">
                    {{-- Node counter --}}
                    <div class="mb-4 flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-100 px-3 py-1 text-xs font-medium text-orange-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                            {{ $totalCausas }} {{ $totalCausas === 1 ? 'causa' : 'causas' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-3 py-1 text-xs font-medium text-purple-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                            {{ $totalEfectos }} {{ $totalEfectos === 1 ? 'efecto' : 'efectos' }}
                        </span>
                    </div>

                    <div class="space-y-6">
                        {{-- SECTION: EFFECTS (top) --}}
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-purple-700 uppercase tracking-wide mb-3">
                                <x-ui.help-label glossary="efecto_directo" class="text-sm font-semibold text-purple-700">
                                    Efectos
                                </x-ui.help-label>
                            </h3>

                            <div class="space-y-2 relative">
                                @foreach ($efectosDirectos as $efecto)
                                    {{-- Connector line from parent --}}
                                    <div class="pl-4 relative">
                                        {{-- Vertical connector --}}
                                        <div class="absolute left-0 top-0 bottom-0 w-px bg-purple-200"></div>
                                        {{-- Horizontal connector --}}
                                        <div class="absolute left-0 top-5 w-4 h-px bg-purple-200"></div>

                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $efecto])

                                        @foreach ($arbol->nodos->where('parent_id', $efecto->id)->where('tipo_nodo.value', 'efecto_indirecto')->sortBy('orden') as $indirecto)
                                            <div class="pl-6 mt-2 relative">
                                                <div class="absolute left-0 top-0 bottom-0 w-px bg-purple-100"></div>
                                                <div class="absolute left-0 top-5 w-6 h-px bg-purple-100"></div>
                                                @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecto])
                                            </div>
                                        @endforeach

                                        <button
                                            wire:click="agregarNodo({{ $efecto->id }}, 'efecto_indirecto')"
                                            class="ml-6 mt-1.5 inline-flex items-center gap-1 text-xs text-purple-500 hover:text-purple-700 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Efecto indirecto
                                        </button>
                                    </div>
                                @endforeach

                                @if ($problemaCentral)
                                    <button
                                        wire:click="agregarNodo({{ $problemaCentral->id }}, 'efecto_directo')"
                                        class="ml-4 inline-flex items-center gap-1.5 text-sm text-purple-600 hover:text-purple-800 font-medium transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Agregar efecto directo
                                    </button>
                                @endif
                            </div>
                        </div>

                        {{-- SECTION: CENTRAL PROBLEM (center) --}}
                        @if ($problemaCentral)
                            <div class="flex justify-center py-2">
                                <div class="w-full max-w-2xl relative">
                                    {{-- Upward connector to effects --}}
                                    @if($efectosDirectos->isNotEmpty())
                                        <div class="absolute left-1/2 -top-4 w-px h-4 bg-gray-300"></div>
                                    @endif
                                    <div class="rounded-xl border-2 border-red-300 bg-gradient-to-br from-red-50 to-red-100 p-5 text-center shadow-sm">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-200 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-red-800">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            Problema Central
                                        </span>
                                        <p class="mt-2 text-lg font-semibold text-red-900 leading-relaxed">{{ $problemaCentral->descripcion }}</p>
                                    </div>
                                    {{-- Downward connector to causes --}}
                                    @if($causasDirectas->isNotEmpty())
                                        <div class="absolute left-1/2 -bottom-4 w-px h-4 bg-gray-300"></div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- SECTION: CAUSES (bottom) --}}
                        <div>
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-orange-700 uppercase tracking-wide mb-3">
                                <x-ui.help-label glossary="causa_directa" class="text-sm font-semibold text-orange-700">
                                    Causas
                                </x-ui.help-label>
                            </h3>

                            <div class="space-y-2 relative">
                                @foreach ($causasDirectas as $causa)
                                    <div class="pl-4 relative">
                                        <div class="absolute left-0 top-0 bottom-0 w-px bg-orange-200"></div>
                                        <div class="absolute left-0 top-5 w-4 h-px bg-orange-200"></div>

                                        @include('livewire.mml.partials.nodo-card', ['nodo' => $causa])

                                        @foreach ($arbol->nodos->where('parent_id', $causa->id)->where('tipo_nodo.value', 'causa_indirecta')->sortBy('orden') as $indirecta)
                                            <div class="pl-6 mt-2 relative">
                                                <div class="absolute left-0 top-0 bottom-0 w-px bg-orange-100"></div>
                                                <div class="absolute left-0 top-5 w-6 h-px bg-orange-100"></div>
                                                @include('livewire.mml.partials.nodo-card', ['nodo' => $indirecta])
                                            </div>
                                        @endforeach

                                        <button
                                            wire:click="agregarNodo({{ $causa->id }}, 'causa_indirecta')"
                                            class="ml-6 mt-1.5 inline-flex items-center gap-1 text-xs text-orange-500 hover:text-orange-700 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            Causa indirecta
                                        </button>
                                    </div>
                                @endforeach

                                @if ($problemaCentral)
                                    <button
                                        wire:click="agregarNodo({{ $problemaCentral->id }}, 'causa_directa')"
                                        class="ml-4 inline-flex items-center gap-1.5 text-sm text-orange-600 hover:text-orange-800 font-medium transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Agregar causa directa
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: AI Suggestions Panel --}}
                <div class="mt-6 lg:mt-0 lg:w-72 xl:w-80 shrink-0">
                    <div class="sticky top-24 rounded-xl border border-purple-200 bg-purple-50/50 overflow-hidden">
                        <div class="px-4 py-3 border-b border-purple-200 bg-purple-50">
                            <h4 class="text-sm font-semibold text-purple-800 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                Asistencia de IA
                            </h4>
                        </div>

                        <div class="p-4 space-y-3">
                            <div class="flex flex-col gap-2">
                                <button
                                    wire:click="sugerirConIa('causa')"
                                    wire:loading.attr="disabled"
                                    class="w-full rounded-lg bg-white px-3 py-2 text-sm font-medium text-orange-700 ring-1 ring-orange-200 hover:bg-orange-50 hover:ring-orange-300 transition-all disabled:opacity-50 text-left flex items-center gap-2"
                                >
                                    <span wire:loading.remove wire:target="sugerirConIa('causa')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                    </span>
                                    <span wire:loading wire:target="sugerirConIa('causa')">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                    <span wire:loading.remove wire:target="sugerirConIa('causa')">Sugerir causas</span>
                                    <span wire:loading wire:target="sugerirConIa('causa')">Generando...</span>
                                </button>
                                <button
                                    wire:click="sugerirConIa('efecto')"
                                    wire:loading.attr="disabled"
                                    class="w-full rounded-lg bg-white px-3 py-2 text-sm font-medium text-purple-700 ring-1 ring-purple-200 hover:bg-purple-50 hover:ring-purple-300 transition-all disabled:opacity-50 text-left flex items-center gap-2"
                                >
                                    <span wire:loading.remove wire:target="sugerirConIa('efecto')">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                                    </span>
                                    <span wire:loading wire:target="sugerirConIa('efecto')">
                                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    </span>
                                    <span wire:loading.remove wire:target="sugerirConIa('efecto')">Sugerir efectos</span>
                                    <span wire:loading wire:target="sugerirConIa('efecto')">Generando...</span>
                                </button>
                            </div>

                            @if (!empty($sugerenciasIa))
                                <div class="space-y-2 pt-2 border-t border-purple-200">
                                    <p class="text-xs font-medium text-purple-600 uppercase tracking-wide">Sugerencias:</p>
                                    @foreach ($sugerenciasIa as $sugerencia)
                                        <div class="rounded-lg bg-white border border-gray-200 p-2.5 shadow-sm">
                                            <p class="text-sm text-gray-700 leading-relaxed">{{ $sugerencia }}</p>
                                            @if ($problemaCentral)
                                                <button
                                                    wire:click="agregarSugerencia('{{ addslashes($sugerencia) }}', {{ $problemaCentral->id }}, '{{ $tipoSugerencia }}')"
                                                    class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                                    Agregar al árbol
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: New node form --}}
            @if ($mostrarFormNuevoNodo)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50" x-data x-transition>
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl mx-4">
                        <h4 class="text-base font-semibold text-gray-900">
                            Agregar {{ str_replace('_', ' ', $tipoNuevoNodo) }}
                        </h4>
                        <textarea
                            wire:model="nuevoNodoDescripcion"
                            rows="3"
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm resize-none"
                            placeholder="Describe el nodo..."
                        ></textarea>
                        @error('nuevoNodoDescripcion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-4 flex justify-end gap-2">
                            <button wire:click="cancelarNuevoNodo" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button wire:click="guardarNuevoNodo" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Modal: Edit node form --}}
            @if ($editNodoId)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50" x-data x-transition>
                    <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl mx-4">
                        <h4 class="text-base font-semibold text-gray-900">Editar nodo</h4>
                        <textarea
                            wire:model="editNodoDescripcion"
                            rows="3"
                            class="mt-3 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm resize-none"
                        ></textarea>
                        @error('editNodoDescripcion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <div class="mt-4 flex justify-end gap-2">
                            <button wire:click="cancelarEdicion" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 transition-colors">
                                Cancelar
                            </button>
                            <button wire:click="actualizarNodo" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                                Actualizar
                            </button>
                        </div>
                    </div>
                </div>
            @endif
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
