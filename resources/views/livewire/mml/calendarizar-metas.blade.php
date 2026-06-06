<x-page.container>
    @include('livewire.mml.partials.stepper-importacion', ['pasoActual' => 4])

    <div class="space-y-6">
        @if ($programa)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                    {{ $programa->clave }} &mdash; {{ $programa->nombre }}
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Ejercicio fiscal {{ $programa->ejercicio_fiscal }}
                </p>
            </div>

            @if (count($propuesta) === 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                    <p class="text-yellow-800 dark:text-yellow-200">
                        No hay indicadores activos con meta definida para calendarizar.
                    </p>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Indicador
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Frecuencia
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Meta Anual
                                </th>
                                @php
                                    $maxPeriodos = collect($propuesta)->max(fn ($i) => count($i['periodos']));
                                @endphp
                                @for ($p = 1; $p <= $maxPeriodos; $p++)
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                        P{{ $p }}
                                    </th>
                                @endfor
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($propuesta as $idx => $indicador)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 dark:text-white max-w-xs truncate">
                                        {{ $indicador['nombre'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600 dark:text-gray-300 capitalize">
                                        {{ $indicador['frecuencia'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-center font-medium text-gray-900 dark:text-white">
                                        {{ number_format((float) $indicador['meta'], 4) }}
                                    </td>
                                    @for ($p = 1; $p <= $maxPeriodos; $p++)
                                        <td class="px-2 py-2 text-center">
                                            @php
                                                $periodoData = collect($indicador['periodos'])->firstWhere('periodo', $p);
                                            @endphp
                                            @if ($periodoData)
                                                <input
                                                    type="number"
                                                    step="0.0001"
                                                    value="{{ $periodoData['meta_periodo'] }}"
                                                    wire:change="ajustarMeta({{ $indicador['indicador_id'] }}, {{ $p }}, $event.target.value)"
                                                    class="w-24 text-center text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                                />
                                            @else
                                                <span class="text-gray-300 dark:text-gray-600">&mdash;</span>
                                            @endif
                                        </td>
                                    @endfor
                                    <td class="px-4 py-3 text-center">
                                        @if (isset($warnings[$indicador['indicador_id']]))
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300"
                                                  title="Suma periodos: {{ $warnings[$indicador['indicador_id']]['suma'] }} / Meta: {{ $warnings[$indicador['indicador_id']]['meta'] }}">
                                                Desbalanceado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                OK
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($this->tieneCalendarizacionPrevia)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <label for="justificacion" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Justificación del ajuste de metas
                        </label>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                            Este programa ya tiene metas calendarizadas. Si modificas algún valor, debes registrar el motivo (queda en bitácora de revisiones).
                        </p>
                        <textarea
                            id="justificacion"
                            wire:model="justificacion"
                            rows="3"
                            class="block w-full text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ej. Reasignación por recorte presupuestal autorizado en oficio..."
                        ></textarea>
                        @error('justificacion')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="flex justify-end">
                    <button
                        wire:click="confirmar"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <span wire:loading.remove wire:target="confirmar">Confirmar y Activar Programa</span>
                        <span wire:loading wire:target="confirmar">Guardando...</span>
                    </button>
                </div>
            @endif
        @else
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
                <p class="text-red-800 dark:text-red-200">
                    No se encontró un programa asociado a esta importación.
                </p>
            </div>
        @endif
    </div>
</x-page.container>
