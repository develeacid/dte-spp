<div>
    <x-page.header :title="'Captura Financiera — ' . $programa->clave">
        <a href="{{ route('presupuesto.panel') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Volver al Panel
        </a>
    </x-page.header>

    <x-page.container>
        {{-- Tabs --}}
        <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="$set('seccion', 'calendarizacion')"
                    @class([
                        'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium',
                        'border-brand text-brand' => $seccion === 'calendarizacion',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $seccion !== 'calendarizacion',
                    ])>
                    Calendarización de Metas
                </button>
                <button wire:click="$set('seccion', 'avance')"
                    @class([
                        'whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium',
                        'border-brand text-brand' => $seccion === 'avance',
                        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => $seccion !== 'avance',
                    ])>
                    Avance Financiero
                </button>
            </nav>
        </div>

        @if ($partidas->isEmpty())
            <div class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center dark:border-gray-600">
                <p class="text-gray-500 dark:text-gray-400">Este programa no tiene partidas presupuestales asignadas.</p>
                @can('gestionar_presupuesto')
                    <a href="{{ route('presupuesto.partidas.create') }}" class="mt-2 inline-block text-brand hover:text-brand-dark">Crear partida</a>
                @endcan
            </div>
        @else
            {{-- Sección 1: Calendarización de Metas de Gasto --}}
            @if ($seccion === 'calendarizacion')
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Partida</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Monto Efectivo</th>
                                @for ($t = 1; $t <= 4; $t++)
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">T{{ $t }}</th>
                                @endfor
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($partidas as $partida)
                                @php
                                    $totalMeta = collect($metas[$partida->id] ?? [])->sum(fn ($v) => (float) $v);
                                    $excede = $totalMeta > $partida->monto_efectivo;
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ $partida->clave_partida }} — {{ Str::limit($partida->descripcion, 30) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2 text-right text-sm font-mono text-gray-500 dark:text-gray-400">
                                        ${{ number_format($partida->monto_efectivo, 2) }}
                                    </td>
                                    @for ($t = 1; $t <= 4; $t++)
                                        <td class="px-2 py-2">
                                            <input type="number" step="0.01" min="0"
                                                wire:model.blur="metas.{{ $partida->id }}.{{ $t }}"
                                                class="w-28 rounded border-gray-300 text-right text-sm font-mono shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                        </td>
                                    @endfor
                                    <td @class([
                                        'whitespace-nowrap px-4 py-2 text-right text-sm font-mono font-semibold',
                                        'text-red-600' => $excede,
                                        'text-gray-900 dark:text-white' => !$excede,
                                    ])>
                                        ${{ number_format($totalMeta, 2) }}
                                        @if ($excede)
                                            <span class="block text-xs font-normal text-red-500">Excede monto efectivo</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex justify-end">
                    <x-ui.button.primary wire:click="guardarMetas">Guardar Calendarización</x-ui.button.primary>
                </div>
            @endif

            {{-- Sección 2: Captura de Avance Financiero --}}
            @if ($seccion === 'avance')
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Partida</th>
                                @for ($t = 1; $t <= 4; $t++)
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400" colspan="3">
                                        T{{ $t }}
                                    </th>
                                @endfor
                            </tr>
                            <tr>
                                <th></th>
                                @for ($t = 1; $t <= 4; $t++)
                                    <th class="px-2 py-1 text-center text-[10px] font-medium uppercase text-gray-400">Compr.</th>
                                    <th class="px-2 py-1 text-center text-[10px] font-medium uppercase text-gray-400">Deveng.</th>
                                    <th class="px-2 py-1 text-center text-[10px] font-medium uppercase text-gray-400">Pagado</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($partidas as $partida)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-2 text-sm font-mono font-medium text-gray-900 dark:text-white">
                                        {{ $partida->clave_partida }}
                                    </td>
                                    @for ($t = 1; $t <= 4; $t++)
                                        @php
                                            $metaTrimestre = (float) ($metas[$partida->id][$t] ?? 0);
                                            $pagado = (float) ($avances[$partida->id][$t]['pagado'] ?? 0);
                                            $ratio = $metaTrimestre > 0 ? $pagado / $metaTrimestre : null;
                                            $semaforoColor = match(true) {
                                                $ratio === null => 'border-gray-300 dark:border-gray-600',
                                                $ratio >= config('presupuesto.semaforo.verde_min') && $ratio <= config('presupuesto.semaforo.verde_max') => 'border-green-400',
                                                $ratio >= config('presupuesto.semaforo.amarillo_min') && $ratio <= config('presupuesto.semaforo.amarillo_max') => 'border-yellow-400',
                                                default => 'border-red-400',
                                            };
                                        @endphp
                                        <td class="px-1 py-2">
                                            <input type="number" step="0.01" min="0"
                                                wire:model.blur="avances.{{ $partida->id }}.{{ $t }}.comprometido"
                                                class="w-24 rounded border-gray-300 text-right text-xs font-mono shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                        </td>
                                        <td class="px-1 py-2">
                                            <input type="number" step="0.01" min="0"
                                                wire:model.blur="avances.{{ $partida->id }}.{{ $t }}.devengado"
                                                class="w-24 rounded border-gray-300 text-right text-xs font-mono shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white" />
                                            @error("avances.{$partida->id}.{$t}.devengado") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                        </td>
                                        <td class="px-1 py-2">
                                            <input type="number" step="0.01" min="0"
                                                wire:model.blur="avances.{{ $partida->id }}.{{ $t }}.pagado"
                                                class="w-24 rounded border-2 {{ $semaforoColor }} text-right text-xs font-mono shadow-sm focus:border-brand focus:ring-brand dark:bg-gray-700 dark:text-white" />
                                            @error("avances.{$partida->id}.{$t}.pagado") <p class="text-[10px] text-red-500">{{ $message }}</p> @enderror
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="inline-block h-3 w-3 rounded border-2 border-green-400"></span> Dentro de meta
                    <span class="inline-block h-3 w-3 rounded border-2 border-yellow-400"></span> Desviación moderada
                    <span class="inline-block h-3 w-3 rounded border-2 border-red-400"></span> Desviación alta
                </div>

                <div class="mt-4 flex justify-end">
                    <x-ui.button.primary wire:click="guardarAvances">Guardar Avances</x-ui.button.primary>
                </div>
            @endif
        @endif
    </x-page.container>
</div>
