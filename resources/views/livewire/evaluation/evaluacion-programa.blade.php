<div>
    <x-page.header>
        <x-slot name="title">Evaluacion del programa: {{ $resumen['clave'] }} - {{ $resumen['nombre'] }}</x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Section 1: Resumen Ejecutivo --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Resumen Ejecutivo</h2>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                <div>
                    <p class="text-sm text-gray-500">Programa</p>
                    <p class="font-medium text-gray-900">{{ $resumen['clave'] }} - {{ $resumen['nombre'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Ejercicio Fiscal</p>
                    <p class="font-medium text-gray-900">{{ $resumen['ejercicio'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Indice de Eficacia</p>
                    <p class="text-4xl font-bold text-indigo-600">{{ number_format((float) $resumen['indice'], 2) }}%</p>
                </div>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <p class="text-sm text-gray-500">Indicadores evaluados</p>
                    <p class="font-medium text-gray-900">{{ $resumen['indicadores_evaluados'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Indicadores sin evaluar</p>
                    <p class="font-medium text-gray-900">{{ $resumen['indicadores_no_evaluados'] }}</p>
                </div>
            </div>

            @if($resumen['ped_objetivo'] || $resumen['ped_linea'])
                <div class="mt-4 rounded-md bg-gray-50 p-4">
                    <p class="mb-1 text-sm font-medium text-gray-700">Alineacion PED</p>
                    @if($resumen['ped_objetivo'])
                        <p class="text-sm text-gray-600"><span class="font-medium">Objetivo Estrategico:</span> {{ $resumen['ped_objetivo'] }}</p>
                    @endif
                    @if($resumen['ped_linea'])
                        <p class="text-sm text-gray-600"><span class="font-medium">Linea de Accion:</span> {{ $resumen['ped_linea'] }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Section 2: Tablero de Semaforos --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Tablero de Semáforos</h3>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- Donut de semáforos --}}
                <div wire:ignore>
                    <x-charts.donut
                        :labels="['Verde', 'Amarillo', 'Rojo', 'Sin dato']"
                        :series="[$tablero['conteo']['verde'] ?? 0, $tablero['conteo']['amarillo'] ?? 0, $tablero['conteo']['rojo'] ?? 0, $tablero['conteo']['sin_dato'] ?? 0]"
                        :colors="['#22c55e', '#eab308', '#ef4444', '#9ca3af']"
                        :height="250"
                        centerText="{{ ($tablero['conteo']['verde'] ?? 0) + ($tablero['conteo']['amarillo'] ?? 0) + ($tablero['conteo']['rojo'] ?? 0) + ($tablero['conteo']['sin_dato'] ?? 0) }}"
                        centerSubtext="indicadores"
                    />
                </div>

                {{-- Bullet charts por nivel --}}
                <div wire:ignore>
                    @if (!empty($tablero['desglose']))
                        <x-charts.bullet
                            :data="collect($tablero['desglose'])->map(fn ($d, $nivel) => [
                                'nombre' => ucfirst($nivel),
                                'resultado' => $d['promedio'] ?? 0,
                                'meta' => 100,
                                'rango_verde_min' => 75, 'rango_verde_max' => 150,
                                'rango_amarillo_min' => 50, 'rango_amarillo_max' => 75,
                                'rango_rojo_min' => 0, 'rango_rojo_max' => 50,
                            ])->values()->toArray()"
                            :height="max(200, count($tablero['desglose']) * 60)"
                            :showLabels="true"
                        />
                    @endif
                </div>
            </div>

            {{-- Tabla de desglose por nivel --}}
            @if(!empty($tablero['desglose']))
                <div class="mt-4 overflow-x-auto">
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nivel</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Peso</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Promedio</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Evaluados</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Sin evaluar</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($tablero['desglose'] as $nivel => $datos)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900 capitalize">{{ $nivel }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600">{{ ($datos['peso'] ?? 0) * 100 }}%</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                                            {{ $datos['promedio'] !== null ? number_format($datos['promedio'], 2) . '%' : '--' }}
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $datos['indicadores_evaluados'] ?? 0 }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600">{{ $datos['indicadores_no_evaluados'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        {{-- Section 3: Comparativa vs Ejercicio Anterior --}}
        @if($comparativa['disponible'])
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Comparativa vs Ejercicio Anterior</h2>

                <div class="mb-4 flex items-center gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Indice anterior</p>
                        <p class="text-xl font-bold text-gray-700">{{ number_format((float) $comparativa['indice_anterior'], 2) }}%</p>
                    </div>
                    <div class="text-2xl text-gray-400">&rarr;</div>
                    <div>
                        <p class="text-sm text-gray-500">Indice actual</p>
                        <p class="text-xl font-bold text-indigo-600">{{ number_format((float) $comparativa['indice_actual'], 2) }}%</p>
                    </div>
                </div>

                @if (count($comparativa['filas']) > 0)
                <div class="mb-4" wire:ignore>
                    @php
                        $compFilas = collect($comparativa['filas'])->take(10);
                    @endphp
                    <x-charts.bar-grouped
                        :categories="$compFilas->pluck('indicador')->map(fn ($n) => Str::limit($n, 25))->toArray()"
                        :series="[
                            ['name' => 'Anterior', 'data' => $compFilas->pluck('resultado_anterior')->toArray()],
                            ['name' => 'Actual', 'data' => $compFilas->pluck('resultado_actual')->toArray()],
                        ]"
                        :colors="['#94a3b8', '#3b82f6']"
                        :height="300"
                        yaxisFormat="percent"
                    />
                </div>
                @endif

                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Nivel</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Resultado anterior</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Resultado actual</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Tendencia</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($comparativa['filas'] as $fila)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $fila['indicador'] }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $fila['nivel']->colorClass() }}">
                                            {{ $fila['nivel']->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        {{ $fila['resultado_anterior'] !== null ? number_format((float) $fila['resultado_anterior'], 2) : '--' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        {{ $fila['resultado_actual'] !== null ? number_format((float) $fila['resultado_actual'], 2) : '--' }}
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm">
                                        @if($fila['tendencia'] === "\u{2191}")
                                            <span class="text-green-600 font-bold text-lg" title="Mejoro">&#8593;</span>
                                        @elseif($fila['tendencia'] === "\u{2193}")
                                            <span class="text-red-600 font-bold text-lg" title="Empeoro">&#8595;</span>
                                        @else
                                            <span class="text-gray-400 font-bold text-lg" title="Estable">=</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Section 4: Analisis de Desviaciones --}}
        @if(count($desviaciones) > 0)
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Analisis de Desviaciones</h2>

                <div class="space-y-4">
                    @foreach($desviaciones as $desv)
                        <div class="rounded-lg border {{ $desv['semaforo'] === 'rojo' ? 'border-red-200 bg-red-50' : 'border-yellow-200 bg-yellow-50' }} p-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-medium text-gray-900">{{ $desv['indicador'] }}</h3>
                                <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $desv['semaforo'] === 'rojo' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ ucfirst($desv['semaforo']) }}
                                </span>
                            </div>
                            <div class="grid grid-cols-1 gap-2 md:grid-cols-2 text-sm">
                                <div>
                                    <span class="text-gray-500">Nivel:</span>
                                    <span class="text-gray-700">{{ $desv['nivel']->label() }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500">Resultado:</span>
                                    <span class="text-gray-700">{{ number_format((float) $desv['resultado'], 2) }} / {{ number_format((float) $desv['meta'], 2) }}</span>
                                </div>
                            </div>
                            @if($desv['justificacion'])
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Justificacion:</p>
                                    <p class="text-sm text-gray-700">{{ $desv['justificacion'] }}</p>
                                </div>
                            @endif
                            @if($desv['supuestos'])
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">Supuestos:</p>
                                    <p class="text-sm text-gray-700">{{ $desv['supuestos'] }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Section 5: Analisis de Logica Vertical (IA) --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Analisis de Logica Vertical (IA)</h2>

            @if($analisisIa && !str_starts_with($analisisIa, 'Analisis de logica vertical no disponible'))
                <div class="prose prose-sm max-w-none text-gray-700">
                    {!! nl2br(e($analisisIa)) !!}
                </div>
            @else
                <div class="rounded-md bg-blue-50 border border-blue-200 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-700">
                                {{ $analisisIa ?? 'Analisis de logica vertical no disponible. Configure LLM_API_KEY para habilitar el analisis automatico de rupturas causales.' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Section 6: Indicadores Cronicos --}}
        @if(count($cronicos) > 0)
            <div class="mb-6 rounded-lg border border-red-200 bg-white p-6 shadow-sm">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Indicadores Cronicos</h2>
                <p class="mb-4 text-sm text-gray-500">Indicadores con semaforo rojo en 2 o mas de los ultimos 3 ejercicios fiscales.</p>

                <div class="overflow-hidden rounded-lg border border-red-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-red-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Nivel</th>
                                <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Ejercicios en rojo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($cronicos as $cronico)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $cronico['indicador'] }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-gray-600">
                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $cronico['nivel']->colorClass() }}">
                                            {{ $cronico['nivel']->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-bold text-red-800">
                                            {{ $cronico['ejercicios_rojo'] }} / 3
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-page.container>
</div>
