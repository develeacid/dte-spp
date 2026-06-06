<div wire:key="data-table-root">
    <x-page.header>
        <x-slot name="title">Seguimiento de indicadores</x-slot>
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
            :show-orden="false"
        >
            <x-slot:ambitoExtra>
                <x-filters.semaforo model="filtroSemaforo" :options="$semaforosOpciones" />
            </x-slot>
        </x-tracking.toolbar>

        <x-tracking.tabs-shell :active="$activeTab">
            <x-slot:dashboard>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @php
                        $semaforoCounts = collect($filas)->countBy('semaforo');
                        $semaforoLabels = ['verde', 'amarillo', 'rojo', 'gris'];
                        $semaforoSeries = collect($semaforoLabels)->map(fn ($s) => $semaforoCounts->get($s, 0))->values()->toArray();
                        $semaforoColors = ['#22c55e', '#eab308', '#ef4444', '#9ca3af'];
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Semáforo de Indicadores</h4>
                        <div wire:key="panel-donut-semaforo-{{ md5(json_encode($semaforoSeries)) }}">
                            <x-charts.donut
                                :labels="$semaforoLabels"
                                :series="$semaforoSeries"
                                :colors="$semaforoColors"
                                :height="220"
                                centerText="{{ array_sum($semaforoSeries) }}"
                                centerSubtext="indicadores"
                            />
                        </div>
                    </div>

                    @php
                        $porPrograma = collect($filas)->groupBy('programa_clave')->map(function ($items, $clave) {
                            $conMeta = $items->filter(fn ($i) => $i['meta'] > 0);
                            return [
                                'clave' => $clave,
                                'avance' => $conMeta->count() > 0
                                    ? round($conMeta->avg(fn ($i) => min(($i['resultado'] / $i['meta']) * 100, 150)), 1)
                                    : 0,
                            ];
                        })->sortByDesc('avance')->values();
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Avance Promedio por Programa</h4>
                        <div wire:key="panel-bar-programa-{{ md5(json_encode($porPrograma)) }}">
                            <x-charts.bar-horizontal
                                :categories="$porPrograma->pluck('clave')->toArray()"
                                :series="[['name' => 'Avance %', 'data' => $porPrograma->pluck('avance')->toArray()]]"
                                :height="max(200, $porPrograma->count() * 35)"
                                :referenceLine="100"
                                referenceLabel="Meta"
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
                    :row-class="$rowClass"
                    empty-message="No se encontraron indicadores con los filtros seleccionados."
                />
            </x-slot:tabla>
        </x-tracking.tabs-shell>

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
