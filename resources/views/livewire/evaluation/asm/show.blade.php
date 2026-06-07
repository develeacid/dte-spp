<div>
    <x-page.header title="Detalle ASM" :subtitle="'#' . $asm->id">
        @can('gestionar_asm')
            <x-ui.button.primary href="{{ route('evaluation.asms.edit', $asm) }}">Editar</x-ui.button.primary>
        @endcan
    </x-page.header>

    <x-page.container :breadcrumbs="[
        ['label' => 'ASM', 'url' => route('evaluation.asms.index')],
        ['label' => '#' . $asm->id],
    ]">
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-3 text-sm">
                <div><strong>Programa:</strong> {{ $asm->programa->clave }} — {{ $asm->programa->nombre }}</div>
                <div><strong>Descripción del aspecto:</strong><p class="mt-1 whitespace-pre-line">{{ $asm->descripcion_aspecto }}</p></div>
                <div><strong>Acción de mejora:</strong><p class="mt-1 whitespace-pre-line">{{ $asm->accion_mejora }}</p></div>
                <div><strong>Tipo de plazo:</strong> {{ $asm->tipo_plazo->label() }}</div>
                <div><strong>Tipo de acción:</strong> {{ $asm->tipo_accion->label() }}</div>
                <div><strong>Responsable:</strong> {{ $asm->responsable->name }} — {{ $asm->area_responsable }}</div>
                <div><strong>Fecha compromiso:</strong> {{ $asm->fecha_compromiso->format('d/m/Y') }}</div>
                <div><strong>% avance:</strong> {{ $asm->porcentaje_avance }}%</div>
                <div><strong>Status:</strong> {{ $asm->status->label() }}</div>
                <div><strong>Semáforo:</strong> @include('livewire.evaluation.asm._semaforo-badge', ['semaforo' => $asm->semaforo])</div>
                @if($asm->evidencia_url)
                    <div><strong>Evidencia:</strong> <a class="text-indigo-600" href="{{ $asm->evidencia_url }}" target="_blank">{{ $asm->evidencia_url }}</a></div>
                @endif

                @if($asm->recomendacion)
                    @php($recomendacion = $asm->recomendacion)
                    @php($hallazgo = $recomendacion->hallazgo)
                    @php($externa = $hallazgo?->informe?->evaluacionExterna)
                    <div class="mt-4 rounded border border-gray-200 bg-gray-50 p-3">
                        <h3 class="text-sm font-semibold mb-2">Origen</h3>
                        @if($hallazgo)
                            <div class="text-xs text-gray-600">
                                <span class="font-medium">Hallazgo:</span> {{ \Illuminate\Support\Str::limit($hallazgo->descripcion, 120) }}
                            </div>
                            <div class="text-xs text-gray-400 my-1">↓</div>
                        @endif
                        <div class="text-xs">
                            <span class="font-medium">Recomendación:</span> {{ $recomendacion->descripcion }}
                        </div>
                        @if($externa)
                            @can('ver_evaluacion_externa')
                                <div class="mt-2">
                                    <a class="text-indigo-600 text-xs" href="{{ route('evaluation.externas.show', $externa) }}">Ver evaluación externa</a>
                                </div>
                            @endcan
                        @endif
                    </div>
                @endif
            </div>

            <div>
                <h3 class="text-sm font-semibold mb-2">Historial (activity log)</h3>
                <ul class="divide-y text-xs">
                    @forelse($activities as $a)
                        <li class="py-2">
                            <span class="font-medium">{{ $a->event }}</span>
                            <span class="text-gray-500">{{ $a->created_at?->format('d/m/Y H:i') }}</span>
                            @if($a->causer)<span>por {{ $a->causer->name }}</span>@endif
                        </li>
                    @empty
                        <li class="py-2 text-gray-400">Sin actividad.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </x-page.container>
</div>
