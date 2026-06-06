<div wire:key="concentrado-captura-root">
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

    <x-page.container fluid>
        <x-tracking.toolbar
            :search="$search"
            search-placeholder="Buscar indicador..."
            :programas-opciones="$programasOpciones"
            :niveles-opciones="$nivelesOpciones"
            :estados-opciones="$estadosOpciones"
            :alcance-temporal="$alcanceTemporal"
            :filtro-fecha-desde="$filtroFechaDesde"
            :filtro-fecha-hasta="$filtroFechaHasta"
            :group-by-programa="$groupByPrograma"
            :per-page="$perPage"
            :sort-by="$sortBy"
            :show-orden="false"
        />

        <x-tracking.tabs-shell :active="$activeTab">
            <x-slot:dashboard>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución Global</h4>
                        <div wire:key="concentrado-donut-{{ md5(json_encode($metricas)) }}">
                            <x-charts.donut
                                :labels="['Aprobados', 'En revisión', 'En captura', 'Observados']"
                                :series="[$metricas['aprobados'], $metricas['en_revision'], $metricas['en_captura'], $metricas['observados']]"
                                :colors="['#22c55e', '#3b82f6', '#eab308', '#f97316']"
                                :height="220"
                                centerText="{{ $metricas['total'] }}"
                                centerSubtext="total"
                            />
                        </div>
                    </div>

                    @php
                        $programasConcentrado = $agrupado->groupBy('programa_clave')->map(function ($items, $clave) {
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
                        <div wire:key="concentrado-bar-{{ md5(json_encode($programasConcentrado)) }}">
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
                </div>
            </x-slot:dashboard>

            <x-slot:tabla>
                <x-data.table
                    :rows="$rows"
                    :columns="$columns"
                    :group-by="$groupByPrograma ? 'programa' : null"
                    :per-page="$perPage"
                    :traceable="true"
                    empty-message="No se encontraron avances con los filtros seleccionados."
                />
            </x-slot:tabla>
        </x-tracking.tabs-shell>

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
