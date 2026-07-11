@php
    $tipoEnum = $nivel->tipo_nivel instanceof \App\Enums\TipoNivelMir
        ? $nivel->tipo_nivel
        : \App\Enums\TipoNivelMir::tryFrom($nivel->tipo_nivel);
    $deletable = $deletable ?? false;
    $editando = $editando ?? false;
@endphp

<tr class="{{ $colorClass }}" wire:key="nivel-{{ $nivel->id }}">
    {{-- Nivel --}}
    <td class="px-3 py-3 align-top">
        <x-ui.tooltip :text="config('glosario.' . $tipoEnum?->value, '')" position="right">
            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $tipoEnum?->colorClass() }} cursor-help">
                <span class="font-mono">{{ $nivel->codigoMir() }}</span>
                {{ $tipoEnum?->label() }}
            </span>
        </x-ui.tooltip>
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
        @if(!$editando)
        {{-- READ MODE --}}
        <div class="flex items-start justify-between gap-2">
            <p class="text-sm text-gray-800 leading-relaxed">{{ $nivel->resumen_narrativo ?: '-' }}</p>
            <button
                wire:click="toggleEditarNivel({{ $nivel->id }})"
                class="shrink-0 inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 hover:bg-gray-200"
            >
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Editar
            </button>
        </div>

        {{-- Read-mode alignment badges --}}
        @php
            $alineacionActual = null;
            if (in_array($tipoEnum, [\App\Enums\TipoNivelMir::FIN, \App\Enums\TipoNivelMir::PROPOSITO])) {
                $alineacionActual = $nivel->pedObjetivoEstrategico;
            } else {
                $alineacionActual = $nivel->pedLineaAccion;
            }
        @endphp
        @if ($alineacionActual)
            <span class="mt-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">
                {{ Str::limit($alineacionActual->descripcion ?? $alineacionActual->nombre ?? '', 60) }}
            </span>
        @endif

        {{-- Syntax validation badge --}}
        @if ($nivel->sintaxis_validada_at)
            @if ($nivel->sintaxis_valida)
                <span class="mt-1 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Sintaxis OK</span>
            @else
                <span class="mt-1 inline-flex items-center rounded-full bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Sintaxis: revisar</span>
            @endif
        @endif
        @else
        {{-- EDIT MODE --}}
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500">Editando</span>
                <button
                    wire:click="toggleEditarNivel(null)"
                    class="inline-flex items-center gap-1 rounded bg-indigo-600 px-2 py-1 text-xs text-white hover:bg-indigo-700"
                >
                    Guardar y cerrar
                </button>
            </div>
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
        </div>
        @endif
    </td>

    {{-- Indicadores --}}
    <td class="px-3 py-3 align-top">
        @if(!$editando)
        {{-- READ MODE: compact indicator table --}}
        @if($nivel->indicadores->count() > 0)
        <div class="space-y-1.5">
            @foreach ($nivel->indicadores as $indicador)
                <div class="text-xs" wire:key="indicador-read-{{ $indicador->id }}">
                    <p class="font-medium text-gray-800">{{ $indicador->nombre ?: '(sin nombre)' }}</p>
                    <div class="flex flex-wrap gap-1 mt-0.5">
                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600">{{ $indicador->tipo?->label() ?? '-' }}</span>
                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600">{{ $indicador->dimension?->label() ?? '-' }}</span>
                        <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] text-gray-600">{{ $indicador->frecuencia?->label() ?? '-' }}</span>
                    </div>
                    @if($indicador->formula_texto)
                        <p class="text-[10px] text-gray-500 mt-0.5">F: {{ $indicador->formula_texto }}</p>
                    @endif
                    @if($indicador->mediosVerificacion->count() > 0)
                        <p class="text-[10px] text-gray-400 mt-0.5">MV: {{ $indicador->mediosVerificacion->pluck('nombre')->filter()->implode(', ') }}</p>
                    @endif
                    @if($indicador->cremaaValidacion)
                        @php
                            $cremaa = $indicador->cremaaValidacion;
                            $cremaaLetters = collect(['claro','relevante','economico','monitoreable','adecuado','aportante']);
                        @endphp
                        <div class="flex gap-0.5 mt-0.5">
                            @foreach(['C'=>'claro','R'=>'relevante','E'=>'economico','M'=>'monitoreable','A'=>'adecuado','A'=>'aportante'] as $l => $campo)
                                <span class="inline-flex h-4 w-4 items-center justify-center rounded text-[9px] font-bold {{ $cremaa->{$campo} ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">{{ $l }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
        @else
            <span class="text-xs text-gray-400 italic">Sin indicadores</span>
        @endif
        @else
        {{-- EDIT MODE: full interface --}}
        <div class="space-y-3">
            @foreach ($nivel->indicadores as $indicador)
                <div class="rounded-md border border-gray-200 bg-white p-2 space-y-2" wire:key="indicador-{{ $indicador->id }}"
                     x-data="{
                        ind: {
                            nombre: @js($indicador->nombre ?? ''),
                            tipo: @js($indicador->tipo?->value ?? $reglas['tipo_default']),
                            dimension: @js($indicador->dimension?->value ?? $reglas['dimensiones'][0]),
                            frecuencia: @js($indicador->frecuencia?->value ?? $reglas['frecuencias'][0]),
                            sentido: @js($indicador->sentido?->value ?? 'ascendente'),
                        },
                        guardar() {
                            $wire.guardarIndicador({{ $indicador->id }}, { ...this.ind });
                        }
                     }">
                    <input
                        type="text"
                        x-model="ind.nombre"
                        @change="guardar()"
                        class="w-full rounded border-gray-300 text-sm"
                        placeholder="Nombre del indicador"
                    />

                    {{-- Definición del indicador (M05 #11, máx. 240 ch) --}}
                    <textarea
                        wire:change="guardarDefinicion({{ $indicador->id }}, $event.target.value)"
                        rows="2"
                        maxlength="240"
                        class="w-full rounded border-gray-300 text-xs"
                        placeholder="Definición del indicador (máx. 240 caracteres)"
                    >{{ $indicador->definicion }}</textarea>
                    @error('definicion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                    <div class="grid grid-cols-2 gap-1">
                        {{-- Tipo --}}
                        @if ($reglas['tipo_fijo'])
                            <span class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600">
                                {{ \App\Enums\TipoIndicador::tryFrom($reglas['tipo_default'])?->label() }}
                            </span>
                        @else
                            <select x-model="ind.tipo" @change="guardar()" class="rounded border-gray-300 text-xs">
                                @foreach ($reglas['tipos'] as $tipo)
                                    <option value="{{ $tipo }}">
                                        {{ \App\Enums\TipoIndicador::tryFrom($tipo)?->label() }}
                                    </option>
                                @endforeach
                            </select>
                        @endif

                        {{-- Dimensión --}}
                        <select x-model="ind.dimension" @change="guardar()" class="rounded border-gray-300 text-xs">
                            @foreach ($reglas['dimensiones'] as $dim)
                                <option value="{{ $dim }}">
                                    {{ \App\Enums\DimensionIndicador::tryFrom($dim)?->label() }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Frecuencia --}}
                        <select x-model="ind.frecuencia" @change="guardar()" class="rounded border-gray-300 text-xs">
                            @foreach ($reglas['frecuencias'] as $freq)
                                <option value="{{ $freq }}">
                                    {{ \App\Enums\FrecuenciaMedicion::tryFrom($freq)?->label() }}
                                </option>
                            @endforeach
                        </select>

                        {{-- Sentido (M05 #15/#12) --}}
                        <select x-model="ind.sentido" @change="guardar()" class="rounded border-gray-300 text-xs">
                            @foreach (\App\Enums\SentidoIndicador::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Fórmula y Variables --}}
                    <div class="border-t border-gray-100 pt-1">
                        <x-ui.help-label glossary="formula_indicador" class="text-xs font-medium text-gray-500">
                            Fórmula
                        </x-ui.help-label>
                        <div class="flex items-center gap-1 mt-1">
                            <input
                                type="text"
                                value="{{ $indicador->formula_texto }}"
                                wire:change="guardarFormulaTexto({{ $indicador->id }}, $event.target.value)"
                                class="flex-1 rounded border-gray-300 text-xs"
                                placeholder="Ej: (A / B) x 100"
                            />
                            <button
                                wire:click="sugerirFormula({{ $indicador->id }})"
                                wire:loading.attr="disabled"
                                wire:target="sugerirFormula({{ $indicador->id }})"
                                class="shrink-0 inline-flex items-center gap-1 rounded bg-purple-50 px-2 py-1 text-xs text-purple-600 hover:bg-purple-100"
                                title="Sugerir fórmula con IA"
                            >
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                <span wire:loading.remove wire:target="sugerirFormula({{ $indicador->id }})">Sugerir</span>
                                <span wire:loading wire:target="sugerirFormula({{ $indicador->id }})">...</span>
                            </button>
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

                        {{-- Año de línea base (V2-A5) --}}
                        <div class="mt-1 flex items-center gap-2">
                            <label class="text-xs font-medium text-gray-500">Año de línea base</label>
                            <input
                                type="number"
                                min="1900"
                                max="2999"
                                value="{{ $indicador->linea_base_anio }}"
                                wire:change="guardarLineaBaseAnio({{ $indicador->id }}, $event.target.value)"
                                class="w-24 rounded border-gray-300 text-xs"
                                placeholder="Ej: 2026"
                            />
                        </div>

                        {{-- Valor de línea base (M05 #8/#13) --}}
                        <div class="mt-1 flex items-center gap-2">
                            <label class="text-xs font-medium text-gray-500">Valor de línea base</label>
                            <input
                                type="number"
                                step="0.0001"
                                value="{{ $indicador->linea_base }}"
                                wire:change="guardarLineaBase({{ $indicador->id }}, $event.target.value)"
                                class="w-32 rounded border-gray-300 text-xs"
                                placeholder="Ej: 42.5"
                            />
                        </div>

                        {{-- Meta anual (C-146) — captura con justificación obligatoria al cambiar --}}
                        {{-- wire:key depende de la meta persistida: al guardar un cambio justificado,
                             la meta del modelo cambia y Livewire fuerza re-init del x-data, refrescando
                             metaInicial al nuevo baseline. Sin esto, metaInicial queda stale y el
                             siguiente cambio se compararía contra el valor viejo (solo client-side;
                             el server es autoritativo igual). --}}
                        <div class="mt-2 border-t border-gray-100 pt-1"
                            wire:key="meta-{{ $indicador->id }}-{{ $indicador->meta }}"
                            x-data="{
                                metaInicial: @js($indicador->meta !== null ? (float) $indicador->meta : null),
                                meta: @js($indicador->meta !== null ? (float) $indicador->meta : ''),
                                justificacion: '',
                                get requiereJustificacion() {
                                    return this.metaInicial !== null
                                        && this.meta !== '' && this.meta !== null
                                        && Number(this.meta) !== Number(this.metaInicial);
                                },
                                guardarMeta() {
                                    $wire.guardarMeta({{ $indicador->id }}, this.meta === '' ? null : this.meta, this.justificacion || null);
                                }
                            }">
                            <label class="text-xs font-medium text-gray-500">Meta anual</label>
                            <input
                                type="number"
                                step="0.01"
                                x-model="meta"
                                @change="if (!requiereJustificacion) guardarMeta()"
                                class="mt-1 w-32 rounded border-gray-300 text-xs"
                                placeholder="Ej: 100"
                            />
                            <div x-show="requiereJustificacion" x-cloak class="mt-1 space-y-1">
                                <textarea
                                    x-model="justificacion"
                                    rows="2"
                                    class="w-full rounded border-gray-300 text-xs"
                                    placeholder="Justificación del cambio (mín. 10 caracteres)"
                                ></textarea>
                                <button
                                    type="button"
                                    @click="guardarMeta()"
                                    class="rounded bg-blue-50 px-2 py-1 text-xs text-blue-600 hover:bg-blue-100"
                                >
                                    Guardar meta
                                </button>
                            </div>
                            @error('meta_'.$indicador->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            @if (!empty($metaWarnings[$indicador->id]))
                                <p class="mt-1 text-xs text-amber-600">{{ $metaWarnings[$indicador->id] }}</p>
                            @endif
                        </div>

                        {{-- Semáforo (rangos) — semaforización 4 rangos --}}
                        <div class="mt-2 border-t border-gray-100 pt-1"
                            x-data="{
                                sem: {
                                    rango_verde_min: @js($indicador->rango_verde_min),
                                    rango_verde_max: @js($indicador->rango_verde_max),
                                    rango_amarillo_min: @js($indicador->rango_amarillo_min),
                                    rango_amarillo_max: @js($indicador->rango_amarillo_max),
                                    rango_rojo_min: @js($indicador->rango_rojo_min),
                                    rango_rojo_max: @js($indicador->rango_rojo_max),
                                    rango_rojo_alto_min: @js($indicador->rango_rojo_alto_min),
                                    rango_rojo_alto_max: @js($indicador->rango_rojo_alto_max),
                                },
                                guardarSem() { $wire.guardarSemaforo({{ $indicador->id }}, { ...this.sem }); }
                            }">
                            <span class="text-xs font-medium text-gray-500">Semáforo (rangos)</span>
                            <div class="mt-1 space-y-1">
                                @php
                                    $semFilas = [
                                        ['etiqueta' => 'Verde', 'color' => 'text-green-700', 'min' => 'rango_verde_min', 'max' => 'rango_verde_max'],
                                        ['etiqueta' => 'Amarillo', 'color' => 'text-yellow-700', 'min' => 'rango_amarillo_min', 'max' => 'rango_amarillo_max'],
                                        ['etiqueta' => 'Rojo', 'color' => 'text-red-700', 'min' => 'rango_rojo_min', 'max' => 'rango_rojo_max'],
                                        ['etiqueta' => 'Rojo alto — sobrecumplimiento', 'color' => 'text-purple-700', 'min' => 'rango_rojo_alto_min', 'max' => 'rango_rojo_alto_max'],
                                    ];
                                @endphp
                                @foreach ($semFilas as $fila)
                                    <div class="grid grid-cols-3 items-center gap-1">
                                        <span class="text-[10px] font-medium {{ $fila['color'] }}">{{ $fila['etiqueta'] }}</span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="sem.{{ $fila['min'] }}"
                                            @change="guardarSem()"
                                            class="rounded border-gray-300 text-xs"
                                            placeholder="mín"
                                        />
                                        <input
                                            type="number"
                                            step="0.01"
                                            x-model="sem.{{ $fila['max'] }}"
                                            @change="guardarSem()"
                                            class="rounded border-gray-300 text-xs"
                                            placeholder="máx"
                                        />
                                    </div>
                                @endforeach
                            </div>
                            @error('semaforo_'.$indicador->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        @if ($indicador->variables->count() > 0)
                            <div class="mt-1 space-y-1">
                                @foreach ($indicador->variables as $variable)
                                    <div class="flex items-start gap-1" wire:key="var-{{ $variable->id }}"
                                        x-data="{ varSimbolo: @js($variable->simbolo), varNombre: @js($variable->nombre ?? ''), varFuente: @js($variable->fuente ?? ''),
                                            guardarVar() { $wire.guardarVariable({{ $variable->id }}, { simbolo: this.varSimbolo, nombre: this.varNombre, fuente: this.varFuente || null }); } }">
                                        <span class="mt-1 w-6 text-center text-xs font-bold text-gray-700">{{ $variable->simbolo }}</span>
                                        <div class="flex-1 space-y-1">
                                            <input
                                                type="text"
                                                x-model="varNombre"
                                                @change="guardarVar()"
                                                class="w-full rounded border-gray-300 text-xs"
                                                placeholder="Nombre de variable"
                                            />
                                            <input
                                                type="text"
                                                x-model="varFuente"
                                                @change="guardarVar()"
                                                class="w-full rounded border-gray-300 text-xs"
                                                placeholder="Fuente (opcional)"
                                            />
                                        </div>
                                        <button wire:click="eliminarVariable({{ $variable->id }})" class="mt-1 text-red-400 hover:text-red-600">
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
                            <div class="flex items-start gap-1 mt-1" wire:key="medio-{{ $medio->id }}"
                                x-data="{ mvNombre: @js($medio->nombre ?? ''), mvOrganismo: @js($medio->organismo ?? ''), mvUrl: @js($medio->url ?? ''), mvFrecuencia: @js($medio->frecuencia ?? ''), mvTipoFuente: @js($medio->tipo_fuente ?? ''),
                                    guardarMv() { $wire.guardarMedioVerificacion({{ $medio->id }}, { nombre: this.mvNombre, organismo: this.mvOrganismo || null, url: this.mvUrl || null, frecuencia: this.mvFrecuencia || null, tipo_fuente: this.mvTipoFuente || null }); } }">
                                <div class="flex-1 space-y-1">
                                    <input
                                        type="text"
                                        x-model="mvNombre"
                                        @change="guardarMv()"
                                        class="w-full rounded border-gray-300 text-xs"
                                        placeholder="Medio de verificación"
                                    />
                                    <input
                                        type="text"
                                        x-model="mvOrganismo"
                                        @change="guardarMv()"
                                        class="w-full rounded border-gray-300 text-xs"
                                        placeholder="Organismo (opcional)"
                                    />
                                    <input
                                        type="url"
                                        x-model="mvUrl"
                                        @change="guardarMv()"
                                        class="w-full rounded border-gray-300 text-xs"
                                        placeholder="URL (opcional)"
                                    />
                                    <select
                                        x-model="mvFrecuencia"
                                        @change="guardarMv()"
                                        class="w-full rounded border-gray-300 text-xs"
                                    >
                                        <option value="">— Frecuencia —</option>
                                        @foreach (\App\Enums\FrecuenciaMedicion::cases() as $freq)
                                            <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('frecuencia_mv_'.$medio->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    @error('frecuencia') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    <select
                                        x-model="mvTipoFuente"
                                        @change="guardarMv()"
                                        class="w-full rounded border-gray-300 text-xs"
                                    >
                                        <option value="">— Tipo de fuente —</option>
                                        @foreach (\App\Enums\TipoFuenteMv::cases() as $tf)
                                            <option value="{{ $tf->value }}">{{ $tf->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('tipo_fuente_mv_'.$medio->id) <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    @error('tipo_fuente') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                                    {{-- CREMA del MV (C-072) --}}
                                    <div class="flex items-center gap-2">
                                        <button
                                            wire:click="validarCremaMv({{ $medio->id }})"
                                            wire:loading.attr="disabled"
                                            wire:target="validarCremaMv({{ $medio->id }})"
                                            class="text-xs text-purple-600 hover:text-purple-800"
                                        >
                                            <span wire:loading.remove wire:target="validarCremaMv({{ $medio->id }})">Validar CREMA</span>
                                            <span wire:loading wire:target="validarCremaMv({{ $medio->id }})">Evaluando...</span>
                                        </button>

                                        @if ($medio->cremaValidacion)
                                            @php
                                                $cremaMv = $medio->cremaValidacion;
                                                $letrasMv = [
                                                    ['letra' => 'C', 'campo' => 'confiable', 'obs' => $cremaMv->confiable_observacion],
                                                    ['letra' => 'R', 'campo' => 'relevante', 'obs' => $cremaMv->relevante_observacion],
                                                    ['letra' => 'E', 'campo' => 'economico', 'obs' => $cremaMv->economico_observacion],
                                                    ['letra' => 'M', 'campo' => 'monitoreable', 'obs' => $cremaMv->monitoreable_observacion],
                                                    ['letra' => 'A', 'campo' => 'asequible', 'obs' => $cremaMv->asequible_observacion],
                                                ];
                                            @endphp
                                            <div class="flex gap-0.5">
                                                @foreach ($letrasMv as $l)
                                                    <span
                                                        class="inline-flex h-5 w-5 items-center justify-center rounded text-xs font-bold {{ $cremaMv->{$l['campo']} ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}"
                                                        title="{{ $l['obs'] ?: 'Cumple' }}"
                                                    >{{ $l['letra'] }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div x-data="{ cremaOpen: false,
                                        cremaMv: { confiable: @js((bool) ($medio->cremaValidacion?->confiable)), relevante: @js((bool) ($medio->cremaValidacion?->relevante)), economico: @js((bool) ($medio->cremaValidacion?->economico)), monitoreable: @js((bool) ($medio->cremaValidacion?->monitoreable)), asequible: @js((bool) ($medio->cremaValidacion?->asequible)) },
                                        guardarCrema() { $wire.guardarCremaMv({{ $medio->id }}, this.cremaMv); } }">
                                        <button @click="cremaOpen = !cremaOpen" type="button" class="text-xs text-gray-500 hover:text-gray-700">
                                            <span x-show="!cremaOpen">Editar CREMA manualmente</span>
                                            <span x-show="cremaOpen">Ocultar CREMA</span>
                                        </button>
                                        <div x-show="cremaOpen" x-cloak class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                                            @foreach ([['confiable', 'Confiable'], ['relevante', 'Relevante'], ['economico', 'Económico'], ['monitoreable', 'Monitoreable'], ['asequible', 'Asequible']] as [$campo, $etiqueta])
                                                <label class="inline-flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                                                    <input type="checkbox" x-model="cremaMv.{{ $campo }}" @change="guardarCrema()" class="h-3.5 w-3.5 rounded border-gray-300 text-purple-600" />
                                                    {{ $etiqueta }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <button wire:click="eliminarMedioVerificacion({{ $medio->id }})" class="mt-1 text-red-400 hover:text-red-600">
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        @endforeach
                        <button wire:click="agregarMedioVerificacion({{ $indicador->id }})" class="mt-1 text-xs text-blue-500 hover:text-blue-700">+ Medio</button>
                    </div>

                    {{-- CREMAA --}}
                    <div class="border-t border-gray-100 pt-1">
                        <div class="flex items-center gap-2">
                            <x-ui.tooltip :text="config('glosario.cremaa')" position="top">
                                <span class="text-xs text-gray-400 cursor-help">?</span>
                            </x-ui.tooltip>
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

                    {{-- Anexos Transversales --}}
                    @if (isset($anexosTransversales) && $anexosTransversales->count() > 0)
                        <div class="border-t border-gray-100 pt-1" x-data="{
                            selected: @js($indicador->anexosTransversales->pluck('id')->toArray()),
                            toggle(id) {
                                const idx = this.selected.indexOf(id);
                                if (idx === -1) { this.selected.push(id); } else { this.selected.splice(idx, 1); }
                                $wire.syncAnexosTransversales({{ $indicador->id }}, this.selected);
                            }
                        }">
                            <span class="text-xs font-medium text-gray-500">Anexos Transversales:</span>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach ($anexosTransversales as $anexo)
                                    <label class="inline-flex items-center gap-1 text-xs cursor-pointer">
                                        <input
                                            type="checkbox"
                                            :checked="selected.includes({{ $anexo->id }})"
                                            @change="toggle({{ $anexo->id }})"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 h-3.5 w-3.5"
                                        />
                                        <span class="text-gray-700">{{ $anexo->nombre }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Badges de anexos transversales asignados --}}
                    @if ($indicador->anexosTransversales->count() > 0)
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach ($indicador->anexosTransversales as $anexo)
                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ $anexo->nombre }}
                                </span>
                            @endforeach
                        </div>
                    @endif

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
        @endif
    </td>

    {{-- Medios de Verificación (column kept for layout, actual medios are inside indicators) --}}
    <td class="px-3 py-3 align-top text-xs text-gray-400">
        <span class="italic">Ver dentro de cada indicador</span>
    </td>

    {{-- Supuestos --}}
    <td class="px-3 py-3 align-top">
        @if(!$editando)
        {{-- READ MODE --}}
        @if ($nivel->supuestosEstructurados->isEmpty())
            <p class="text-sm text-gray-800">-</p>
        @else
            <ul class="space-y-1">
                @foreach ($nivel->supuestosEstructurados as $supuesto)
                    <li class="text-sm text-gray-800 flex items-start gap-1.5" wire:key="supuesto-read-{{ $supuesto->id }}">
                        <span>{{ $supuesto->descripcion ?: '—' }}</span>
                        @if ($supuesto->esValido())
                            <span class="mt-0.5 inline-flex shrink-0 items-center rounded-full bg-green-100 px-1.5 py-0.5 text-[10px] font-medium text-green-700" title="Externo, relevante y razonablemente probable">Válido</span>
                        @else
                            <span class="mt-0.5 inline-flex shrink-0 items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700" title="Falta marcar externo/relevante/probable">Incompleto</span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
        @if ($nivel->team)
            <span class="mt-1 inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-xs font-medium text-sky-700">
                UR: {{ $nivel->team->name }}
            </span>
        @endif
        @else
        {{-- EDIT MODE --}}
        <div class="space-y-2">
            @foreach ($nivel->supuestosEstructurados as $supuesto)
                <div class="rounded border border-gray-200 p-1.5" wire:key="supuesto-{{ $supuesto->id }}"
                    x-data="{ supDesc: @js($supuesto->descripcion ?? ''), supExt: @js((bool) $supuesto->es_externo), supRel: @js((bool) $supuesto->es_relevante), supProb: @js((bool) $supuesto->probabilidad_razonable),
                        guardarSup() { $wire.guardarSupuesto({{ $supuesto->id }}, { descripcion: this.supDesc, es_externo: this.supExt, es_relevante: this.supRel, probabilidad_razonable: this.supProb }); } }">
                    <div class="flex items-start gap-1">
                        <textarea
                            x-model="supDesc"
                            @change="guardarSup()"
                            class="w-full rounded border-gray-300 text-xs"
                            rows="2"
                            placeholder="Supuesto..."
                        ></textarea>
                        <button wire:click="eliminarSupuesto({{ $supuesto->id }})" class="mt-1 text-red-400 hover:text-red-600">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5">
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="supExt" @change="guardarSup()" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600" />
                            Externo
                        </label>
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="supRel" @change="guardarSup()" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600" />
                            Relevante
                        </label>
                        <label class="inline-flex items-center gap-1 text-xs text-gray-600 cursor-pointer">
                            <input type="checkbox" x-model="supProb" @change="guardarSup()" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600" />
                            Probable
                        </label>
                    </div>
                    @error('descripcion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
            <button wire:click="agregarSupuesto({{ $nivel->id }})" class="text-xs text-blue-500 hover:text-blue-700">+ Supuesto</button>
        </div>

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
        @endif
    </td>
</tr>
