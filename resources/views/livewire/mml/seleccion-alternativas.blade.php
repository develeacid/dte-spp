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

        @if (!$arbolObjetivosId)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes completar el árbol de objetivos en la
                    <a href="{{ route('mml.etapa3', $programa) }}" class="font-medium underline">Etapa 3</a>.
                </p>
            </div>
        @else
            <div class="space-y-6">
                {{-- Medios disponibles --}}
                <x-forms.section
                    title="Medios del Árbol de Objetivos"
                    description="Estos son los medios identificados. Agrúpalos en alternativas para evaluar cuál es la mejor estrategia."
                >
                    <div class="space-y-2">
                        @forelse ($medios as $medio)
                            @php
                                $tipoEnum = $medio->tipo_nodo instanceof \App\Enums\TipoNodo
                                    ? $medio->tipo_nodo
                                    : \App\Enums\TipoNodo::tryFrom($medio->tipo_nodo);
                                $esPodado = $alternativaSeleccionada && !$alternativaSeleccionada->nodos->contains('id', $medio->id);
                            @endphp
                            <div class="flex items-center gap-3 rounded-md border p-3 {{ $esPodado ? 'bg-gray-50 border-gray-200 opacity-50' : 'bg-teal-50 border-teal-200' }}">
                                <span class="text-xs font-semibold uppercase {{ $esPodado ? 'text-gray-400' : 'text-teal-600' }}">
                                    {{ $tipoEnum?->label() ?? '' }}
                                </span>
                                <p class="text-sm {{ $esPodado ? 'text-gray-400 line-through' : 'text-teal-800' }}">
                                    {{ $medio->descripcion }}
                                </p>
                                @if ($esPodado)
                                    <span class="ml-auto text-xs text-gray-400">(podado)</span>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">No hay medios en el árbol de objetivos.</p>
                        @endforelse
                    </div>
                </x-forms.section>

                {{-- Crear alternativa --}}
                <x-forms.section
                    title="Alternativas"
                    description="Crea alternativas y asígnales medios. Luego evalúa su viabilidad."
                >
                    <div class="flex items-end gap-3 mb-4">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700">Nombre de la alternativa</label>
                            <input
                                wire:model="nuevaAlternativaNombre"
                                type="text"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ej: Alternativa A — Becas y capacitación"
                            />
                            @error('nuevaAlternativaNombre')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            wire:click="crearAlternativa"
                            class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-500"
                        >
                            Crear
                        </button>
                    </div>

                    {{-- Lista de alternativas --}}
                    @foreach ($alternativas as $alternativa)
                        <div class="mb-4 rounded-lg border {{ $alternativa->seleccionada ? 'border-green-400 bg-green-50' : 'border-gray-200 bg-white' }} p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-semibold {{ $alternativa->seleccionada ? 'text-green-800' : 'text-gray-900' }}">
                                    {{ $alternativa->nombre }}
                                    @if ($alternativa->seleccionada)
                                        <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                            Seleccionada
                                        </span>
                                    @endif
                                </h4>
                                <div class="flex gap-2">
                                    <button
                                        wire:click="evaluarConIa({{ $alternativa->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs text-purple-600 hover:text-purple-800 disabled:opacity-50"
                                    >
                                        Evaluar con IA
                                    </button>
                                    <button
                                        wire:click="eliminarAlternativa({{ $alternativa->id }})"
                                        wire:confirm="¿Eliminar esta alternativa?"
                                        class="text-xs text-red-600 hover:text-red-800"
                                    >
                                        Eliminar
                                    </button>
                                </div>
                            </div>

                            {{-- Checkboxes de medios --}}
                            <div class="mt-3 space-y-1">
                                @foreach ($medios as $medio)
                                    <label class="flex items-center gap-2 text-sm text-gray-700">
                                        <input
                                            type="checkbox"
                                            wire:click="toggleNodo({{ $alternativa->id }}, {{ $medio->id }})"
                                            {{ $alternativa->nodos->contains('id', $medio->id) ? 'checked' : '' }}
                                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        {{ $medio->descripcion }}
                                    </label>
                                @endforeach
                            </div>

                            {{-- Botón seleccionar --}}
                            @if (!$alternativa->seleccionada)
                                <div class="mt-3 border-t pt-3">
                                    <label class="block text-sm font-medium text-gray-700">Justificación de selección</label>
                                    <textarea
                                        wire:model="justificacionSeleccion"
                                        rows="2"
                                        class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                        placeholder="¿Por qué esta alternativa es la mejor opción?"
                                    ></textarea>
                                    @error('justificacionSeleccion')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                    <button
                                        wire:click="seleccionarAlternativa({{ $alternativa->id }})"
                                        class="mt-2 rounded-md bg-green-600 px-3 py-1.5 text-sm text-white hover:bg-green-500"
                                    >
                                        Seleccionar esta alternativa
                                    </button>
                                </div>
                            @else
                                <div class="mt-3 border-t pt-3">
                                    <p class="text-sm text-green-700">
                                        <strong>Justificación:</strong> {{ $alternativa->justificacion_seleccion }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    {{-- Evaluación IA --}}
                    @if (!empty($evaluacionIa))
                        <div class="rounded-md bg-purple-50 border border-purple-200 p-4 mt-4">
                            <h4 class="text-sm font-semibold text-purple-800">Evaluación de Viabilidad (IA)</h4>
                            <div class="mt-3 grid grid-cols-3 gap-4">
                                @foreach (['viabilidad_tecnica' => 'Técnica', 'viabilidad_institucional' => 'Institucional', 'viabilidad_presupuestal' => 'Presupuestal'] as $key => $label)
                                    @if (isset($evaluacionIa[$key]))
                                        @php
                                            $cal = $evaluacionIa[$key]['calificacion'] ?? 'N/A';
                                            $calColor = match($cal) {
                                                'alta' => 'text-green-700 bg-green-100',
                                                'media' => 'text-yellow-700 bg-yellow-100',
                                                'baja' => 'text-red-700 bg-red-100',
                                                default => 'text-gray-700 bg-gray-100',
                                            };
                                        @endphp
                                        <div class="rounded-md bg-white p-3 border border-gray-100">
                                            <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                                            <span class="mt-1 inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $calColor }}">
                                                {{ ucfirst($cal) }}
                                            </span>
                                            <p class="mt-1 text-xs text-gray-600">{{ $evaluacionIa[$key]['justificacion'] ?? '' }}</p>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                            @if (isset($evaluacionIa['recomendacion']))
                                <p class="mt-3 text-sm text-purple-700"><strong>Recomendación:</strong> {{ $evaluacionIa['recomendacion'] }}</p>
                            @endif
                        </div>
                    @endif
                </x-forms.section>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa3', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
        </x-slot:footer>
    </x-page.container>
</div>
