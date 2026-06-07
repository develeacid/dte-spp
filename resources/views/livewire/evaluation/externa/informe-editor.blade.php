@php
    $tipo = $evaluacionExterna->tipo;
    $estado = $evaluacionExterna->estado;
    $programaClave = $evaluacionExterna->programa->clave ?? '—';

    $severidadBadge = fn ($sev) => match ($sev) {
        \App\Enums\SeveridadHallazgo::ALTA => 'bg-red-100 text-red-800',
        \App\Enums\SeveridadHallazgo::MEDIA => 'bg-amber-100 text-amber-800',
        default => 'bg-gray-100 text-gray-700',
    };
@endphp

<div>
    <x-page.header
        title="Informe — {{ $programaClave }} · {{ $tipo->label() }}"
        subtitle="Evaluador: {{ $evaluacionExterna->evaluador_externo }}"
    >
        @can('gestionar_evaluacion_externa')
            <x-ui.button.secondary href="{{ route('evaluation.externas.edit', $evaluacionExterna) }}">Editar datos</x-ui.button.secondary>
        @endcan
        <x-ui.button.secondary href="{{ route('evaluation.externas.index') }}">Volver</x-ui.button.secondary>
    </x-page.header>

    <x-page.container fluid :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Evaluación', 'url' => '#'],
        ['label' => 'Evaluaciones externas', 'url' => route('evaluation.externas.index')],
        ['label' => 'Informe'],
    ]">
        {{-- Resumen de la evaluación + badges --}}
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                {{ $tipo->label() }}
            </span>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                {{ $estado === \App\Enums\EstadoEvaluacionExterna::CONCLUIDA ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                {{ $estado->label() }}
            </span>
            <span class="text-xs text-gray-500">
                Ejercicio {{ $evaluacionExterna->ejercicio_fiscal }} ·
                {{ optional($evaluacionExterna->fecha_inicio)->format('d/m/Y') ?? '—' }}
                &ndash;
                {{ optional($evaluacionExterna->fecha_fin)->format('d/m/Y') ?? '—' }}
            </span>
        </div>

        @if(session('status'))
            <div class="mb-4 rounded-md bg-green-50 px-4 py-2 text-sm text-green-800">{{ session('status') }}</div>
        @endif

        @php
            $secciones = [
                'resumen_ejecutivo' => 'Resumen ejecutivo',
                'metodologia' => 'Metodología',
            ];
        @endphp

        {{-- Secciones 1 y 2: Resumen ejecutivo + Metodología --}}
        @foreach($secciones as $campo => $titulo)
            <x-forms.section :title="$titulo">
                @include('livewire.evaluation.externa.partials.seccion-texto', [
                    'campo' => $campo,
                    'valor' => $informe->{$campo},
                    'puedeGestionar' => $puedeGestionar,
                ])
            </x-forms.section>
        @endforeach

        {{-- Sección 3: Hallazgos (con recomendaciones anidadas) --}}
        <x-forms.section :title="'Hallazgos (' . $hallazgos->count() . ')'">
            <div class="col-span-6">
            @forelse($hallazgos as $hallazgo)
                <div wire:key="hallazgo-{{ $hallazgo->id }}" class="mb-4 rounded-lg border border-gray-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $severidadBadge($hallazgo->severidad) }}">
                                {{ $hallazgo->severidad->label() }}
                            </span>
                            <p class="mt-1 text-sm text-gray-800">{{ $hallazgo->descripcion }}</p>
                            @if($hallazgo->evidencia_url)
                                <a href="{{ $hallazgo->evidencia_url }}" target="_blank" rel="noopener"
                                   class="mt-1 inline-block text-xs text-indigo-600 hover:underline">Evidencia</a>
                            @endif
                        </div>
                        @if($puedeGestionar)
                            @php
                                // Suma de ASMs derivados de todas las recomendaciones del hallazgo
                                // (asms_count viene del withCount en render()). Si > 0, el confirm
                                // avisa que esos ASM quedarán desvinculados (nullOnDelete), no borrados.
                                $asmsDerivados = $hallazgo->recomendaciones->sum('asms_count');
                                $confirmEliminar = $asmsDerivados > 0
                                    ? "¿Eliminar este hallazgo y todas sus recomendaciones? Los {$asmsDerivados} ASM derivados quedarán desvinculados."
                                    : '¿Eliminar este hallazgo y todas sus recomendaciones?';
                            @endphp
                            <button
                                wire:click="eliminarHallazgo({{ $hallazgo->id }})"
                                wire:confirm="{{ $confirmEliminar }}"
                                class="shrink-0 text-xs text-red-500 hover:text-red-700">
                                Eliminar
                            </button>
                        @endif
                    </div>

                    {{-- Recomendaciones anidadas --}}
                    <div class="mt-3 border-t border-gray-100 pt-3">
                        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            Recomendaciones ({{ $hallazgo->recomendaciones->count() }})
                        </h4>
                        @foreach($hallazgo->recomendaciones as $reco)
                            <div wire:key="reco-{{ $reco->id }}" class="mb-2 flex items-start justify-between gap-3 rounded bg-gray-50 px-3 py-2">
                                <div class="min-w-0">
                                    <p class="text-sm text-gray-800">{{ $reco->descripcion }}</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="text-xs text-gray-500">Prioridad: {{ $reco->prioridad->label() }}</span>
                                        @if($reco->asms_count > 0)
                                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-800">
                                                {{ $reco->asms_count }} ASM derivados
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @if($puedeGestionar)
                                    <button
                                        wire:click="eliminarRecomendacion({{ $reco->id }})"
                                        wire:confirm="¿Eliminar esta recomendación?"
                                        class="shrink-0 text-xs text-red-500 hover:text-red-700">
                                        Eliminar
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        {{-- Form inline agregar recomendación --}}
                        @if($puedeGestionar)
                            <div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-6">
                                <div class="md:col-span-4">
                                    <textarea wire:model="nuevaRecomendacion.{{ $hallazgo->id }}.descripcion" rows="2"
                                        class="block w-full rounded border-gray-300 text-sm"
                                        placeholder="Nueva recomendación (mín. 10 caracteres)"></textarea>
                                    @error("nuevaRecomendacion.{$hallazgo->id}.descripcion")
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <select wire:model="nuevaRecomendacion.{{ $hallazgo->id }}.prioridad"
                                        class="block w-full rounded border-gray-300 text-sm">
                                        <option value="">Prioridad…</option>
                                        @foreach($prioridades as $p)
                                            <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error("nuevaRecomendacion.{$hallazgo->id}.prioridad")
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                <div>
                                    <button wire:click="agregarRecomendacion({{ $hallazgo->id }})"
                                        class="w-full rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        Agregar
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">Sin hallazgos registrados.</p>
            @endforelse

            {{-- Form inline agregar hallazgo --}}
            @if($puedeGestionar)
                <div class="mt-4 rounded-lg border border-dashed border-gray-300 p-4">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700">Nuevo hallazgo</h4>
                    <div class="grid grid-cols-1 gap-2 md:grid-cols-6">
                        <div class="md:col-span-3">
                            <textarea wire:model="nuevoHallazgo.descripcion" rows="2"
                                class="block w-full rounded border-gray-300 text-sm"
                                placeholder="Descripción del hallazgo (mín. 10 caracteres)"></textarea>
                            @error('nuevoHallazgo.descripcion')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <select wire:model="nuevoHallazgo.severidad" class="block w-full rounded border-gray-300 text-sm">
                                @foreach($severidades as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                            @error('nuevoHallazgo.severidad')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <input type="url" wire:model="nuevoHallazgo.evidencia_url"
                                class="block w-full rounded border-gray-300 text-sm" placeholder="URL evidencia (opcional)">
                            @error('nuevoHallazgo.evidencia_url')<span class="text-xs text-red-600">{{ $message }}</span>@enderror
                        </div>
                        <div>
                            <button wire:click="agregarHallazgo"
                                class="w-full rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                Agregar
                            </button>
                        </div>
                    </div>
                </div>
            @endif
            </div>
        </x-forms.section>

        {{-- Sección 4: Conclusiones --}}
        <x-forms.section title="Conclusiones">
            @include('livewire.evaluation.externa.partials.seccion-texto', [
                'campo' => 'conclusiones',
                'valor' => $informe->conclusiones,
                'puedeGestionar' => $puedeGestionar,
            ])
        </x-forms.section>

        {{-- Sección 5: Recomendaciones (resumen / contador — edición es inline bajo cada hallazgo) --}}
        <x-forms.section :title="'Recomendaciones (' . $totalRecomendaciones . ')'">
            <p class="col-span-6 text-sm text-gray-500">
                Las recomendaciones se gestionan bajo cada hallazgo en la sección Hallazgos.
                Total registradas: <strong>{{ $totalRecomendaciones }}</strong>.
            </p>
        </x-forms.section>

        {{-- Sección 6: Fichas técnicas --}}
        <x-forms.section title="Fichas técnicas">
            @include('livewire.evaluation.externa.partials.seccion-texto', [
                'campo' => 'fichas',
                'valor' => $informe->fichas,
                'puedeGestionar' => $puedeGestionar,
            ])
        </x-forms.section>
    </x-page.container>
</div>
