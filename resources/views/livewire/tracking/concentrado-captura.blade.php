<div>
    <x-page.header>
        <x-slot name="title">Concentrado de Captura</x-slot>
        <x-slot name="actions">
            <button wire:click="exportarPdf" type="button"
                    class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                PDF
            </button>
            <button wire:click="exportarExcel" type="button"
                    class="inline-flex items-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500">
                <svg class="-ml-0.5 mr-1.5 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375" />
                </svg>
                Excel
            </button>
        </x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Date range filters --}}
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-2">
            <div>
                <label for="fechaDesde" class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" wire:model.live="fechaDesde" id="fechaDesde"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
                <label for="fechaHasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" wire:model.live="fechaHasta" id="fechaHasta"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
        </div>

        {{-- Metrics cards --}}
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-white p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-gray-900">{{ $metricas['total'] }}</p>
                <p class="mt-1 text-sm text-gray-500">Total</p>
            </div>
            <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-green-700">{{ $metricas['aprobados'] }}</p>
                <p class="mt-1 text-sm text-green-600">Aprobados</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-blue-700">{{ $metricas['en_revision'] }}</p>
                <p class="mt-1 text-sm text-blue-600">En Revision</p>
            </div>
            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-yellow-700">{{ $metricas['en_captura'] }}</p>
                <p class="mt-1 text-sm text-yellow-600">En Captura</p>
            </div>
            <div class="rounded-lg border border-orange-200 bg-orange-50 p-4 text-center shadow-sm">
                <p class="text-3xl font-bold text-orange-700">{{ $metricas['observados'] }}</p>
                <p class="mt-1 text-sm text-orange-600">Observados</p>
            </div>
        </div>

        {{-- Resumen visual --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2" wire:ignore>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución Global</h4>
                <x-charts.donut
                    :labels="['Aprobados', 'En revisión', 'En captura', 'Observados']"
                    :series="[$metricas['aprobados'], $metricas['en_revision'], $metricas['en_captura'], $metricas['observados']]"
                    :colors="['#22c55e', '#3b82f6', '#eab308', '#f97316']"
                    :height="220"
                    centerText="{{ $metricas['total'] }}"
                    centerSubtext="total"
                />
            </div>

            @php
                $programasConcentrado = $agrupado->map(function ($items, $clave) {
                    return [
                        'clave' => $clave,
                        'aprobados' => $items->sum('aprobados'),
                        'en_revision' => $items->sum('en_revision'),
                        'en_captura' => $items->sum('en_captura'),
                        'observados' => $items->sum('observados'),
                    ];
                })->values();
            @endphp
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Comparativa por Programa</h4>
                <x-charts.bar-grouped
                    :categories="$programasConcentrado->pluck('clave')->toArray()"
                    :series="[
                        ['name' => 'Aprobados', 'data' => $programasConcentrado->pluck('aprobados')->toArray()],
                        ['name' => 'En revisión', 'data' => $programasConcentrado->pluck('en_revision')->toArray()],
                        ['name' => 'En captura', 'data' => $programasConcentrado->pluck('en_captura')->toArray()],
                    ]"
                    :colors="['#22c55e', '#3b82f6', '#eab308']"
                    :height="280"
                />
            </div>
        </div>

        {{-- Grouped table --}}
        @if($agrupado->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-gray-500">No se encontraron avances con los filtros seleccionados.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Aprobados</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">En Revision</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">En Captura</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Observados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @php
                                $currentPrograma = null;
                                $programaGroups = $agrupado->groupBy('programa_clave');
                            @endphp
                            @foreach($programaGroups as $programaClave => $rows)
                                @foreach($rows as $index => $fila)
                                    <tr>
                                        @if($index === 0)
                                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-gray-900" rowspan="{{ $rows->count() }}">
                                                {{ $fila['programa_clave'] }}
                                            </td>
                                        @endif
                                        <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-900">{{ $fila['indicador'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm font-semibold text-gray-900">{{ $fila['total'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-green-700">{{ $fila['aprobados'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-blue-700">{{ $fila['en_revision'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-yellow-700">{{ $fila['en_captura'] }}</td>
                                        <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-orange-700">{{ $fila['observados'] }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-page.container>
</div>
