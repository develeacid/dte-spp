@php
    $tipoEnum = $nivel->tipo_nivel instanceof \App\Enums\TipoNivelMir
        ? $nivel->tipo_nivel
        : \App\Enums\TipoNivelMir::tryFrom($nivel->tipo_nivel);
    $deletable = $deletable ?? false;
@endphp

<tr class="{{ $colorClass }}" wire:key="nivel-{{ $nivel->id }}">
    {{-- Nivel --}}
    <td class="px-3 py-3 align-top">
        <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $tipoEnum?->colorClass() }}">
            {{ $tipoEnum?->label() }}
        </span>
        @if ($deletable)
            <button
                wire:click="eliminarNivel({{ $nivel->id }})"
                wire:confirm="¿Eliminar este nivel y todos sus datos?"
                class="mt-1 block text-xs text-red-500 hover:text-red-700"
            >
                Eliminar
            </button>
        @endif
    </td>

    {{-- Resumen Narrativo --}}
    <td class="px-3 py-3 align-top">
        <textarea
            wire:change="guardarNivel({{ $nivel->id }}, 'resumen_narrativo', $event.target.value)"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            rows="3"
            placeholder="Resumen narrativo..."
        >{{ $nivel->resumen_narrativo }}</textarea>

        {{-- Validación sintáctica SHCP --}}
        <div class="mt-2 space-y-1">
            <button
                wire:click="validarSintaxis({{ $nivel->id }})"
                wire:loading.attr="disabled"
                wire:target="validarSintaxis({{ $nivel->id }})"
                class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200"
            >
                <span wire:loading.remove wire:target="validarSintaxis({{ $nivel->id }})">Validar sintaxis</span>
                <span wire:loading wire:target="validarSintaxis({{ $nivel->id }})">Validando...</span>
            </button>

            @if ($nivel->sintaxis_validada_at)
                <div class="flex items-center gap-1">
                    @if ($nivel->sintaxis_valida)
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Cumple</span>
                    @else
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">No cumple</span>
                    @endif
                </div>

                @if ($nivel->sintaxis_observacion)
                    <p class="text-xs text-gray-500">{{ $nivel->sintaxis_observacion }}</p>
                @endif

                @if ($nivel->sintaxis_sugerencia && !$nivel->sintaxis_valida)
                    <div x-data="{ open: false }" class="text-xs">
                        <button @click="open = !open" class="text-indigo-600 hover:text-indigo-800">
                            <span x-show="!open">Ver sugerencia</span>
                            <span x-show="open">Ocultar sugerencia</span>
                        </button>
                        <div x-show="open" x-cloak class="mt-1 rounded border border-indigo-200 bg-indigo-50 p-2">
                            <p class="text-gray-700">{{ $nivel->sintaxis_sugerencia }}</p>
                            <button
                                wire:click="aceptarSugerencia({{ $nivel->id }})"
                                class="mt-1 rounded bg-indigo-600 px-2 py-0.5 text-white hover:bg-indigo-700"
                            >
                                Aceptar sugerencia
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Alineación PED --}}
        <div class="mt-2 space-y-1">
            @php
                $alineacionActual = null;
                if (in_array($tipoEnum, [\App\Enums\TipoNivelMir::FIN, \App\Enums\TipoNivelMir::PROPOSITO])) {
                    $alineacionActual = $nivel->pedObjetivoEstrategico;
                } else {
                    $alineacionActual = $nivel->pedLineaAccion;
                }
            @endphp

            @if ($alineacionActual)
                <div class="rounded border border-green-200 bg-green-50 p-1.5 text-xs" x-data="{ showChain: false }">
                    <span class="font-medium text-green-700">Alineado:</span>
                    <span class="text-green-800">{{ $alineacionActual->descripcion ?? $alineacionActual->nombre ?? '' }}</span>
                    <button @click="showChain = !showChain" class="ml-1 text-green-600 hover:text-green-800 underline">
                        <span x-show="!showChain">Ver cadena</span>
                        <span x-show="showChain">Ocultar</span>
                    </button>
                    <div x-show="showChain" x-cloak class="mt-1 space-y-0.5 text-xs text-gray-600">
                        @if ($alineacionActual instanceof \App\Models\PedObjetivoEstrategico)
                            <p>Tema: {{ $alineacionActual->tema?->descripcion ?? '' }}</p>
                            <p>Eje: {{ $alineacionActual->tema?->eje?->nombre ?? '' }}</p>
                            @foreach ($alineacionActual->pndObjetivos as $pnd)
                                <p>PND: {{ $pnd->descripcion ?? $pnd->nombre ?? '' }}</p>
                            @endforeach
                        @elseif ($alineacionActual instanceof \App\Models\PedLineaAccion)
                            <p>Estrategia: {{ $alineacionActual->estrategia?->descripcion ?? '' }}</p>
                            <p>Obj. Estratégico: {{ $alineacionActual->estrategia?->objetivoEstrategico?->descripcion ?? '' }}</p>
                        @endif
                    </div>
                </div>
            @endif

            <button
                wire:click="buscarAlineacion({{ $nivel->id }})"
                wire:loading.attr="disabled"
                wire:target="buscarAlineacion({{ $nivel->id }})"
                class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200"
            >
                <span wire:loading.remove wire:target="buscarAlineacion({{ $nivel->id }})">Buscar alineación</span>
                <span wire:loading wire:target="buscarAlineacion({{ $nivel->id }})">Buscando...</span>
            </button>

            @if ($nivelAlineacionActivo === $nivel->id && count($sugerenciasAlineacion) > 0)
                <div class="mt-1 rounded border border-indigo-200 bg-indigo-50 p-2 space-y-1">
                    <p class="text-xs font-medium text-indigo-700">Sugerencias de alineación:</p>
                    @foreach ($sugerenciasAlineacion as $sug)
                        <div class="flex items-center justify-between gap-2 rounded bg-white p-1.5 text-xs">
                            <div>
                                <span class="font-medium">{{ $sug['descripcion'] }}</span>
                                <span class="ml-1 text-gray-400">({{ $sug['score'] }}%)</span>
                            </div>
                            <button
                                wire:click="seleccionarAlineacion({{ $nivel->id }}, '{{ $sug['tipo'] }}', {{ $sug['id'] }})"
                                class="shrink-0 rounded bg-indigo-600 px-2 py-0.5 text-white hover:bg-indigo-700"
                            >
                                Seleccionar
                            </button>
                        </div>
                    @endforeach
                </div>
            @elseif ($nivelAlineacionActivo === $nivel->id && count($sugerenciasAlineacion) === 0)
                <p class="mt-1 text-xs text-gray-500">No se encontraron coincidencias.</p>
            @endif
        </div>
    </td>

    {{-- Indicadores --}}
    <td class="px-3 py-3 align-top">
        <div class="space-y-3">
            @foreach ($nivel->indicadores as $indicador)
                <div class="rounded-md border border-gray-200 bg-white p-2 space-y-2" wire:key="indicador-{{ $indicador->id }}"
                     x-data="{ reglas: @js($reglas) }">
                    <input
                        type="text"
                        value="{{ $indicador->nombre }}"
                        wire:change="guardarIndicador({{ $indicador->id }}, {
                            nombre: $event.target.value,
                            tipo: $event.target.closest('[x-data]').querySelector('[name=tipo]')?.value || '{{ $indicador->tipo?->value ?? $reglas['tipo_default'] }}',
                            dimension: $event.target.closest('[x-data]').querySelector('[name=dimension]')?.value || '{{ $indicador->dimension?->value ?? $reglas['dimensiones'][0] }}',
                            frecuencia: $event.target.closest('[x-data]').querySelector('[name=frecuencia]')?.value || '{{ $indicador->frecuencia?->value ?? $reglas['frecuencias'][0] }}'
                        })"
                        class="w-full rounded border-gray-300 text-sm"
                        placeholder="Nombre del indicador"
                    />

                    <div class="grid grid-cols-3 gap-1">
                        {{-- Tipo --}}
                        @if ($reglas['tipo_fijo'])
                            <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">
                                {{ \App\Enums\TipoIndicador::tryFrom($reglas['tipo_default'])?->label() }}
                            </span>
                            <input type="hidden" name="tipo" value="{{ $reglas['tipo_default'] }}" />
                        @else
                            <select name="tipo" class="rounded border-gray-300 text-xs">
                                @foreach ($reglas['tipos'] as $tipo)
                                    <option value="{{ $tipo }}" @selected(($indicador->tipo?->value ?? '') === $tipo)>
                                        {{ \App\Enums\TipoIndicador::tryFrom($tipo)?->label() }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Dimensión --}}
                        <select name="dimension" class="rounded border-gray-300 text-xs">
                            @foreach ($reglas['dimensiones'] as $dim)
                                <option value="{{ $dim }}" @selected(($indicador->dimension?->value ?? '') === $dim)>
                                    {{ \App\Enums\DimensionIndicador::tryFrom($dim)?->label() }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Frecuencia --}}
                        <select name="frecuencia" class="rounded border-gray-300 text-xs">
                            @foreach ($reglas['frecuencias'] as $freq)
                                <option value="{{ $freq }}" @selected(($indicador->frecuencia?->value ?? '') === $freq)>
                                    {{ \App\Enums\FrecuenciaMedicion::tryFrom($freq)?->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Fórmula y Variables --}}
                    <div class="border-t border-gray-100 pt-1">
                        <span class="text-xs font-medium text-gray-500">Fórmula:</span>
                        <div class="flex items-center gap-1 mt-1">
                            <input
                                type="text"
                                value="{{ $indicador->formula_texto }}"
                                wire:change="guardarFormulaTexto({{ $indicador->id }}, $event.target.value)"
                                class="flex-1 rounded border-gray-300 text-xs"
                                placeholder="Ej: (A / B) x 100"
                            />
                            <button
                                wire:click="extraerVariables({{ $indicador->id }})"
                                wire:loading.attr="disabled"
                                wire:target="extraerVariables({{ $indicador->id }})"
                                class="shrink-0 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200"
                            >
                                <span wire:loading.remove wire:target="extraerVariables({{ $indicador->id }})">Extraer variables</span>
                                <span wire:loading wire:target="extraerVariables({{ $indicador->id }})">Extrayendo...</span>
                            </button>
                        </div>

                        @if ($indicador->variables->count() > 0)
                            <div class="mt-1 space-y-1">
                                @foreach ($indicador->variables as $variable)
                                    <div class="flex items-center gap-1" wire:key="var-{{ $variable->id }}">
                                        <span class="w-6 text-center text-xs font-bold text-gray-700">{{ $variable->simbolo }}</span>
                                        <input
                                            type="text"
                                            value="{{ $variable->nombre }}"
                                            wire:change="guardarVariable({{ $variable->id }}, { simbolo: '{{ $variable->simbolo }}', nombre: $event.target.value })"
                                            class="flex-1 rounded border-gray-300 text-xs"
                                            placeholder="Nombre de variable"
                                        />
                                        <button wire:click="eliminarVariable({{ $variable->id }})" class="text-red-400 hover:text-red-600">
                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <button wire:click="agregarVariable({{ $indicador->id }})" class="mt-1 text-xs text-blue-500 hover:text-blue-700">+ Variable</button>
                    </div>

                    {{-- Medios de Verificación inline --}}
                    <div class="border-t border-gray-100 pt-1">
                        <span class="text-xs font-medium text-gray-500">Medios:</span>
                        @foreach ($indicador->mediosVerificacion as $medio)
                            <div class="flex items-center gap-1 mt-1" wire:key="medio-{{ $medio->id }}">
                                <input
                                    type="text"
                                    value="{{ $medio->nombre }}"
                                    wire:change="guardarMedioVerificacion({{ $medio->id }}, $event.target.value)"
                                    class="flex-1 rounded border-gray-300 text-xs"
                                    placeholder="Medio de verificación"
                                />
                                <button wire:click="eliminarMedioVerificacion({{ $medio->id }})" class="text-red-400 hover:text-red-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        @endforeach
                        <button wire:click="agregarMedioVerificacion({{ $indicador->id }})" class="mt-1 text-xs text-blue-500 hover:text-blue-700">+ Medio</button>
                    </div>

                    {{-- CREMAA --}}
                    <div class="border-t border-gray-100 pt-1">
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="validarCremaa({{ $indicador->id }})"
                                wire:loading.attr="disabled"
                                wire:target="validarCremaa({{ $indicador->id }})"
                                class="text-xs text-purple-600 hover:text-purple-800"
                            >
                                <span wire:loading.remove wire:target="validarCremaa({{ $indicador->id }})">Validar CREMAA</span>
                                <span wire:loading wire:target="validarCremaa({{ $indicador->id }})">Evaluando...</span>
                            </button>

                            @if ($indicador->cremaaValidacion)
                                @php
                                    $cremaa = $indicador->cremaaValidacion;
                                    $letras = [
                                        ['letra' => 'C', 'campo' => 'claro', 'obs' => $cremaa->claro_observacion],
                                        ['letra' => 'R', 'campo' => 'relevante', 'obs' => $cremaa->relevante_observacion],
                                        ['letra' => 'E', 'campo' => 'economico', 'obs' => $cremaa->economico_observacion],
                                        ['letra' => 'M', 'campo' => 'monitoreable', 'obs' => $cremaa->monitoreable_observacion],
                                        ['letra' => 'A', 'campo' => 'adecuado', 'obs' => $cremaa->adecuado_observacion],
                                        ['letra' => 'A', 'campo' => 'aportante', 'obs' => $cremaa->aportante_observacion],
                                    ];
                                @endphp
                                <div class="flex gap-0.5">
                                    @foreach ($letras as $l)
                                        <span
                                            class="inline-flex h-5 w-5 items-center justify-center rounded text-xs font-bold {{ $cremaa->{$l['campo']} ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}"
                                            title="{{ $l['obs'] ?: 'Cumple' }}"
                                        >{{ $l['letra'] }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        @if ($indicador->cremaaValidacion)
                            <div x-data="{ open: false }" class="mt-1">
                                <button @click="open = !open" class="text-xs text-gray-500 hover:text-gray-700">
                                    <span x-show="!open">Ver detalles CREMAA</span>
                                    <span x-show="open">Ocultar detalles</span>
                                </button>
                                <div x-show="open" x-cloak class="mt-1 space-y-1 text-xs">
                                    @foreach ($letras as $l)
                                        @if (!$cremaa->{$l['campo']} && $l['obs'])
                                            <p class="text-red-600"><strong>{{ $l['letra'] }}</strong>: {{ $l['obs'] }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <button wire:click="eliminarIndicador({{ $indicador->id }})" class="text-xs text-red-400 hover:text-red-600">Eliminar indicador</button>
                </div>
            @endforeach

            <button
                wire:click="agregarIndicador({{ $nivel->id }})"
                class="inline-flex items-center gap-1 text-xs text-indigo-600 hover:text-indigo-800"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Indicador
            </button>
        </div>
    </td>

    {{-- Medios de Verificación (column kept for layout, actual medios are inside indicators) --}}
    <td class="px-3 py-3 align-top text-xs text-gray-400">
        <span class="italic">Ver dentro de cada indicador</span>
    </td>

    {{-- Supuestos --}}
    <td class="px-3 py-3 align-top">
        <textarea
            wire:change="guardarNivel({{ $nivel->id }}, 'supuestos', $event.target.value)"
            class="w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            rows="3"
            placeholder="Supuestos..."
        >{{ $nivel->supuestos }}</textarea>

        {{-- UR Coadyuvante --}}
        @if (in_array($tipoEnum, [\App\Enums\TipoNivelMir::COMPONENTE, \App\Enums\TipoNivelMir::ACTIVIDAD]))
            <div class="mt-2">
                <label class="text-xs font-medium text-gray-500">UR Coadyuvante:</label>
                <select
                    wire:change="asignarUrCoadyuvante({{ $nivel->id }}, $event.target.value || null)"
                    class="mt-0.5 w-full rounded border-gray-300 text-xs"
                >
                    <option value="">— UR Coordinadora —</option>
                    @foreach ($teams ?? [] as $team)
                        <option value="{{ $team->id }}" @selected($nivel->team_id === $team->id)>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
                @if ($nivel->team)
                    <span class="mt-0.5 inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">
                        {{ $nivel->team->name }}
                    </span>
                @endif
            </div>
        @endif
    </td>
</tr>
