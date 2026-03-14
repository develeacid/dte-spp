@php
    $isExpanded = in_array($nivel->id, $expandedNiveles);
    $indicadores = $nivel->indicadores ?? collect();
    $indicadorCount = $indicadores->count();
    $colorMap = [
        'blue' => ['bg' => 'bg-blue-50', 'border' => 'border-l-blue-400', 'badge' => 'bg-blue-100 text-blue-800'],
        'emerald' => ['bg' => 'bg-emerald-50', 'border' => 'border-l-emerald-400', 'badge' => 'bg-emerald-100 text-emerald-800'],
        'amber' => ['bg' => 'bg-amber-50', 'border' => 'border-l-amber-400', 'badge' => 'bg-amber-100 text-amber-800'],
        'violet' => ['bg' => 'bg-violet-50', 'border' => 'border-l-violet-400', 'badge' => 'bg-violet-100 text-violet-800'],
    ];
    $colors = $colorMap[$color] ?? $colorMap['blue'];
    $paddingLeft = $depth > 0 ? 'ml-6' : '';
@endphp

<div class="{{ $paddingLeft }}">
    {{-- Nivel header row --}}
    <div class="rounded-lg border border-gray-200 {{ $colors['bg'] }} border-l-4 {{ $colors['border'] }} overflow-hidden">
        <button
            wire:click="toggleNivel({{ $nivel->id }})"
            class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-white/30 transition-colors"
        >
            {{-- Chevron --}}
            <svg class="w-4 h-4 text-gray-500 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>

            {{-- Type badge --}}
            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $colors['badge'] }}">
                {{ $label }}
            </span>

            {{-- Resumen narrativo --}}
            <span class="flex-1 text-sm text-gray-800 truncate">
                {{ $nivel->resumen_narrativo ?? 'Sin resumen narrativo' }}
            </span>

            {{-- Indicator count --}}
            @if($indicadorCount > 0)
                <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    {{ $indicadorCount }}
                </span>
            @endif

            {{-- UR badge for componentes/actividades --}}
            @if($nivel->team)
                <span class="shrink-0 text-xs text-gray-400">{{ $nivel->team->name }}</span>
            @endif
        </button>

        {{-- Expanded content: indicators --}}
        @if($isExpanded && $indicadorCount > 0)
            <div class="border-t border-gray-200 bg-white p-4 space-y-3">
                @foreach($indicadores as $indicador)
                    <div class="rounded-lg border border-gray-100 p-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-800">{{ $indicador->nombre ?: 'Sin nombre' }}</p>
                                <div class="mt-1 flex items-center gap-2 flex-wrap text-xs text-gray-500">
                                    @if($indicador->tipo)
                                        <span class="px-1.5 py-0.5 bg-gray-50 rounded">{{ $indicador->tipo->label() }}</span>
                                    @endif
                                    @if($indicador->dimension)
                                        <span class="px-1.5 py-0.5 bg-gray-50 rounded">{{ $indicador->dimension->label() }}</span>
                                    @endif
                                    @if($indicador->frecuencia)
                                        <span class="px-1.5 py-0.5 bg-gray-50 rounded">{{ $indicador->frecuencia->label() }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Overall progress --}}
                            @php
                                $metas = $indicador->metasPeriodo;
                                $totalMeta = $metas->sum('meta_periodo');
                                $totalAvance = $metas->sum(fn($m) => $m->avance?->valor_real ?? 0);
                                $pct = $totalMeta > 0 ? min(round(($totalAvance / $totalMeta) * 100), 100) : 0;

                                // Semaphore based on ranges or simple threshold
                                if ($indicador->rango_verde_min !== null) {
                                    $semaphore = ($pct >= $indicador->rango_verde_min) ? 'green'
                                        : (($pct >= ($indicador->rango_amarillo_min ?? 0)) ? 'yellow' : 'red');
                                } else {
                                    $semaphore = $pct >= 80 ? 'green' : ($pct >= 50 ? 'yellow' : 'red');
                                }
                                $semColors = ['green' => 'bg-green-500', 'yellow' => 'bg-yellow-400', 'red' => 'bg-red-500'];
                            @endphp

                            <div class="shrink-0 flex items-center gap-2">
                                <div class="w-24 bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full {{ $semColors[$semaphore] }}" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-xs font-semibold text-gray-600 w-10 text-right">{{ $pct }}%</span>
                                <span class="w-2.5 h-2.5 rounded-full {{ $semColors[$semaphore] }}"></span>
                            </div>

                            <button wire:click="verDetalle({{ $indicador->id }})"
                                class="shrink-0 text-xs text-indigo-600 hover:text-indigo-800 underline">
                                {{ $indicadorDetalleId === $indicador->id ? 'Ocultar' : 'Detalle' }}
                            </button>
                        </div>

                        {{-- Expanded detail: temporal breakdown --}}
                        @if($indicadorDetalleId === $indicador->id)
                            <div class="mt-3 border-t border-gray-100 pt-3">
                                @if($indicador->formula_texto)
                                    <p class="text-xs text-gray-500 mb-2">
                                        <span class="font-medium">Fórmula:</span> {{ $indicador->formula_texto }}
                                    </p>
                                @endif

                                @if($metas->isNotEmpty())
                                    <table class="min-w-full text-xs">
                                        <thead>
                                            <tr class="text-gray-500">
                                                <th class="text-left py-1 pr-3">Periodo</th>
                                                <th class="text-right py-1 px-2">Meta</th>
                                                <th class="text-right py-1 px-2">Avance</th>
                                                <th class="text-right py-1 px-2">%</th>
                                                <th class="py-1 pl-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($metas as $meta)
                                                @php
                                                    $avReal = $meta->avance?->valor_real ?? 0;
                                                    $metaPct = $meta->meta_periodo > 0
                                                        ? min(round(($avReal / $meta->meta_periodo) * 100), 100)
                                                        : 0;
                                                    $metaSem = $metaPct >= 80 ? 'green' : ($metaPct >= 50 ? 'yellow' : 'red');
                                                @endphp
                                                <tr class="border-t border-gray-50">
                                                    <td class="py-1 pr-3 font-medium text-gray-700">T{{ $meta->periodo }}</td>
                                                    <td class="py-1 px-2 text-right text-gray-600">{{ number_format($meta->meta_periodo, 2) }}</td>
                                                    <td class="py-1 px-2 text-right text-gray-800 font-medium">{{ number_format($avReal, 2) }}</td>
                                                    <td class="py-1 px-2 text-right font-semibold text-gray-700">{{ $metaPct }}%</td>
                                                    <td class="py-1 pl-2"><span class="inline-block w-2 h-2 rounded-full {{ $semColors[$metaSem] }}"></span></td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    <p class="text-xs text-gray-400 italic">Sin metas por periodo configuradas.</p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @elseif($isExpanded)
            <div class="border-t border-gray-200 bg-white p-4">
                <p class="text-xs text-gray-400 italic">Sin indicadores configurados.</p>
            </div>
        @endif
    </div>
</div>
