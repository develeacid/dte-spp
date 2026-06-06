<div wire:key="detalle-indicador-root">
    <x-page.header>
        <x-slot name="title">{{ $indicador->nombre }}</x-slot>
        <x-slot name="actions">
            <a href="{{ route('tracking.panel') }}"
               class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                &larr; Volver al panel
            </a>
        </x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Bloque trazabilidad --}}
        <div class="flex items-center gap-3 rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
            <x-data.indicador-badge :trazabilidad="$trazabilidad" />
            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $trazabilidad->nivel() }}</span>
            <span class="text-xs text-slate-400">·</span>
            <span class="text-sm text-slate-600 dark:text-slate-300">{{ $indicador->mirNivel?->programa?->nombre ?? '—' }}</span>
        </div>

        @if ($avanceActual)
            {{-- Resumen del avance actual --}}
            <div class="mt-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex flex-wrap items-center gap-4">
                    <div>
                        <div class="text-xs text-slate-500">Periodo</div>
                        <div class="text-sm font-semibold">
                            {{ $avanceActual->metaPeriodo?->ejercicio_fiscal ?? '—' }} · T{{ $avanceActual->metaPeriodo?->periodo ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Meta</div>
                        <div class="text-sm font-semibold">
                            {{ $avanceActual->metaPeriodo?->meta_periodo !== null ? number_format((float) $avanceActual->metaPeriodo->meta_periodo, 2) : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Resultado</div>
                        <div class="text-sm font-semibold">
                            {{ $avanceActual->resultado !== null ? number_format((float) $avanceActual->resultado, 2) : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Semáforo</div>
                        <span @class([
                            'inline-block h-4 w-4 rounded-full',
                            'bg-green-500' => $avanceActual->semaforo_calculado === 'verde',
                            'bg-yellow-400' => $avanceActual->semaforo_calculado === 'amarillo',
                            'bg-red-500' => $avanceActual->semaforo_calculado === 'rojo',
                            'bg-gray-300' => ! in_array($avanceActual->semaforo_calculado, ['verde', 'amarillo', 'rojo']),
                        ])></span>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500">Estado</div>
                        @if ($avanceActual->estado)
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $avanceActual->estado->colorClass() }}">
                                {{ $avanceActual->estado->label() }}
                            </span>
                        @else
                            <span class="text-xs text-gray-400">Sin estado</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Bloque variables --}}
            @if ($avanceActual->variables->count() > 0)
                <section class="mt-6">
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Variables del avance</h3>
                    <dl class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                        @foreach ($avanceActual->variables as $variable)
                            <div class="rounded-md bg-white p-3 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:ring-gray-700">
                                <dt class="text-xs font-medium text-gray-500">
                                    {{ $variable->indicadorVariable?->nombre ?? $variable->indicadorVariable?->simbolo ?? 'Variable' }}
                                </dt>
                                <dd class="mt-1 text-sm font-semibold text-gray-900 dark:text-gray-100">
                                    {{ number_format((float) $variable->valor, 4) }}
                                    @if ($variable->valor_acumulado !== null)
                                        <span class="ml-1 text-xs font-normal text-gray-500">(Acum: {{ number_format((float) $variable->valor_acumulado, 4) }})</span>
                                    @endif
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endif

            {{-- Bloque justificación --}}
            @if ($avanceActual->justificacion_final || $avanceActual->justificacion_ia)
                <section class="mt-6">
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Justificación</h3>
                    <p class="rounded-md bg-white p-3 text-sm text-gray-600 shadow-sm ring-1 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700">
                        {{ $avanceActual->justificacion_final ?? $avanceActual->justificacion_ia }}
                    </p>
                </section>
            @endif

            {{-- Bloque evidencias --}}
            @if ($avanceActual->evidencias->count() > 0)
                <section class="mt-6">
                    <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Evidencias</h3>
                    <ul class="space-y-1">
                        @foreach ($avanceActual->evidencias as $evidencia)
                            <li class="flex items-center gap-2 text-sm">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                <a href="{{ route('tracking.evidencia.download', $evidencia) }}"
                                   class="text-indigo-600 hover:text-indigo-500 hover:underline">
                                    {{ $evidencia->nombre_documento ?? $evidencia->nombre_archivo }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- Bloque timeline --}}
            <section class="mt-6">
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Historial de observaciones</h3>
                @include('livewire.tracking.partials.timeline-observaciones', ['historial' => $avanceActual->historial_observaciones ?? []])
            </section>

            {{-- Link al flujo --}}
            <div class="mt-6">
                <a href="{{ route('tracking.flujo', $avanceActual) }}"
                   class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Ver flujo de aprobación &rarr;
                </a>
            </div>
        @else
            <div class="mt-4 rounded-md border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
                Este indicador aún no tiene avances capturados.
            </div>
        @endif

        {{-- Histórico de avances --}}
        @if ($avances->count() > 1)
            <section class="mt-8">
                <h3 class="mb-2 text-lg font-semibold text-gray-900 dark:text-gray-100">Histórico de avances</h3>
                <ul class="divide-y divide-slate-200 rounded-md border border-slate-200 bg-white dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-800">
                    @foreach ($avances as $av)
                        <li class="flex items-center justify-between px-4 py-2 text-sm">
                            <span class="text-slate-700 dark:text-slate-300">
                                {{ $av->metaPeriodo?->ejercicio_fiscal ?? '—' }} · T{{ $av->metaPeriodo?->periodo ?? '—' }}
                                @if ($av->estado)
                                    <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $av->estado->colorClass() }}">
                                        {{ $av->estado->label() }}
                                    </span>
                                @endif
                            </span>
                            <a href="{{ route('tracking.flujo', $av) }}"
                               class="text-indigo-600 hover:text-indigo-500 hover:underline">
                                Ver flujo
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </x-page.container>
</div>
