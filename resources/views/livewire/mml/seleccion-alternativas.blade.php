<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 4 — Selección de Alternativas"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 4: Alternativas'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="4" />

        @if (session('success'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-3">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @include('livewire.mml.partials.mir-impact-warning', ['tieneMir' => $tieneMir])

        @if (!$arbolObjetivosId)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-amber-700">
                    Primero debes completar el árbol de objetivos en la
                    <a href="{{ route('mml.etapa3', $programa) }}" class="font-semibold underline hover:text-amber-900">Etapa 3</a>.
                </p>
            </div>
        @else
            {{-- Info banner --}}
            <div class="mb-6 rounded-xl bg-blue-50 border border-blue-200 p-4 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <div>
                    <p class="text-sm font-medium text-blue-800">Evalúa y selecciona la mejor alternativa</p>
                    <p class="text-xs text-blue-600 mt-0.5">Agrupa medios del árbol de objetivos en alternativas, evalúa su viabilidad, y selecciona la que se implementará. La alternativa seleccionada define los medios que formarán la MIR.</p>
                </div>
            </div>

            <div class="space-y-6">
                {{-- Available means --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                        Medios del Árbol de Objetivos
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @forelse ($medios as $medio)
                            @php
                                $tipoEnum = $medio->tipo_nodo instanceof \App\Enums\TipoNodo
                                    ? $medio->tipo_nodo
                                    : \App\Enums\TipoNodo::tryFrom($medio->tipo_nodo);
                                $esPodado = $alternativaSeleccionada && !$alternativaSeleccionada->nodos->contains('id', $medio->id);
                            @endphp
                            <div class="flex items-start gap-2.5 rounded-lg border p-3 transition-colors {{ $esPodado ? 'bg-gray-50 border-gray-200 opacity-50' : 'bg-white border-teal-200' }}">
                                <span class="mt-0.5 shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $esPodado ? 'bg-gray-100 text-gray-400' : 'bg-teal-100 text-teal-700' }}">
                                    {{ $tipoEnum?->label() ?? '' }}
                                </span>
                                <p class="text-sm {{ $esPodado ? 'text-gray-400 line-through' : 'text-gray-800' }} leading-relaxed">
                                    {{ $medio->descripcion }}
                                </p>
                                @if ($esPodado)
                                    <span class="ml-auto shrink-0 text-[10px] text-gray-400 font-medium">(podado)</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500 col-span-2">No hay medios en el árbol de objetivos.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Create alternative --}}
                <div>
                    <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        Alternativas
                    </h3>

                    <div class="flex items-end gap-3 mb-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Nombre de la alternativa</label>
                            <input
                                wire:model="nuevaAlternativaNombre"
                                type="text"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: Alternativa A — Becas y capacitación"
                            />
                            @error('nuevaAlternativaNombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            wire:click="crearAlternativa"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors"
                        >
                            Crear
                        </button>
                    </div>

                    {{-- Alternatives as cards --}}
                    <div class="grid grid-cols-1 gap-4">
                        @foreach ($alternativas as $alternativa)
                            <div class="rounded-xl border-2 transition-all {{ $alternativa->seleccionada ? 'border-green-400 bg-green-50/50 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300' }} p-5">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-base font-semibold {{ $alternativa->seleccionada ? 'text-green-800' : 'text-gray-900' }}">
                                            {{ $alternativa->nombre }}
                                        </h4>
                                        @if ($alternativa->seleccionada)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 ring-1 ring-inset ring-green-200">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Seleccionada
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/></svg>
                                                Enlace MIR
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button
                                            wire:click="evaluarConIa({{ $alternativa->id }})"
                                            wire:loading.attr="disabled"
                                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-50 transition-colors disabled:opacity-50"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                            Evaluar IA
                                        </button>
                                        <button
                                            wire:click="eliminarAlternativa({{ $alternativa->id }})"
                                            wire:confirm="¿Eliminar esta alternativa?"
                                            class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- Checkboxes for means --}}
                                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                    @foreach ($medios as $medio)
                                        <label class="flex items-start gap-2 text-sm text-gray-700 p-1.5 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                                            <input
                                                type="checkbox"
                                                wire:click="toggleNodo({{ $alternativa->id }}, {{ $medio->id }})"
                                                {{ $alternativa->nodos->contains('id', $medio->id) ? 'checked' : '' }}
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span class="leading-relaxed">{{ $medio->descripcion }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Select button or justification --}}
                                @if (!$alternativa->seleccionada)
                                    <div class="mt-4 border-t border-gray-100 pt-4">
                                        <label class="block text-sm font-medium text-gray-700">Justificación de selección</label>
                                        <textarea
                                            wire:model="justificacionSeleccion"
                                            rows="2"
                                            class="mt-1 block w-full rounded-lg border-gray-300 text-sm resize-none focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="¿Por qué esta alternativa es la mejor opción?"
                                        ></textarea>
                                        @error('justificacionSeleccion')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                        <button
                                            wire:click="seleccionarAlternativa({{ $alternativa->id }})"
                                            class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-500 transition-colors"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Seleccionar esta alternativa
                                        </button>
                                    </div>
                                @else
                                    <div class="mt-4 border-t border-green-100 pt-3">
                                        <p class="text-sm text-green-700">
                                            <span class="font-semibold">Justificación:</span> {{ $alternativa->justificacion_seleccion }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- AI Evaluation Results --}}
                    @if (!empty($evaluacionIa))
                        <div class="mt-6 rounded-xl bg-purple-50/50 border border-purple-200 overflow-hidden">
                            <div class="px-4 py-3 border-b border-purple-200 bg-purple-50">
                                <h4 class="text-sm font-semibold text-purple-800 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                    Evaluación de Viabilidad (IA)
                                </h4>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    @foreach (['viabilidad_tecnica' => 'Técnica', 'viabilidad_institucional' => 'Institucional', 'viabilidad_presupuestal' => 'Presupuestal'] as $key => $label)
                                        @if (isset($evaluacionIa[$key]))
                                            @php
                                                $cal = $evaluacionIa[$key]['calificacion'] ?? 'N/A';
                                                $calConfig = match($cal) {
                                                    'alta' => ['text-green-700', 'bg-green-100', 'ring-green-200', 'bg-green-50'],
                                                    'media' => ['text-amber-700', 'bg-amber-100', 'ring-amber-200', 'bg-amber-50'],
                                                    'baja' => ['text-red-700', 'bg-red-100', 'ring-red-200', 'bg-red-50'],
                                                    default => ['text-gray-700', 'bg-gray-100', 'ring-gray-200', 'bg-gray-50'],
                                                };
                                            @endphp
                                            <div class="rounded-xl {{ $calConfig[3] }} border border-gray-100 p-4">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $label }}</p>
                                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $calConfig[0] }} {{ $calConfig[1] }} ring-1 ring-inset {{ $calConfig[2] }}">
                                                        {{ ucfirst($cal) }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-600 leading-relaxed">{{ $evaluacionIa[$key]['justificacion'] ?? '' }}</p>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                @if (isset($evaluacionIa['recomendacion']))
                                    <div class="mt-3 rounded-lg bg-white border border-purple-100 p-3">
                                        <p class="text-sm text-purple-700"><span class="font-semibold">Recomendación:</span> {{ $evaluacionIa['recomendacion'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa3', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa5', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Poblaciones →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
