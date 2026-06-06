<div wire:key="reporte-desviaciones-root">
    <x-page.header>
        <x-slot name="title">Reporte de Desviaciones</x-slot>
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
            :show-orden="true"
        />

        <x-tracking.tabs-shell :active="$activeTab">
            <x-slot:dashboard>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @php
                        $tipoCounts = collect($filas)->countBy('tipo_justificacion');
                        $tipoLabels = ['final', 'ia', 'ambas'];
                        $tipoSeries = collect($tipoLabels)->map(fn ($t) => $tipoCounts->get($t, 0))->toArray();
                        $tipoColors = ['#22c55e', '#f59e0b', '#3b82f6'];
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Por tipo de justificación</h4>
                        <div wire:key="desviaciones-donut-{{ md5(json_encode($tipoSeries)) }}">
                            <x-charts.donut
                                :labels="$tipoLabels"
                                :series="$tipoSeries"
                                :colors="$tipoColors"
                                :height="220"
                                centerText="{{ array_sum($tipoSeries) }}"
                                centerSubtext="registros"
                            />
                        </div>
                    </div>
                    @php
                        $porPrograma = collect($filas)->groupBy('programa_clave')->map(function ($items, $clave) {
                            return ['clave' => $clave, 'total' => $items->count()];
                        })->values();
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Desviaciones por programa</h4>
                        <div wire:key="desviaciones-bar-{{ md5(json_encode($porPrograma)) }}">
                            <x-charts.bar-horizontal
                                :categories="$porPrograma->pluck('clave')->toArray()"
                                :series="[
                                    ['name' => 'Desviaciones', 'data' => $porPrograma->pluck('total')->toArray()],
                                ]"
                                :height="max(200, $porPrograma->count() * 40)"
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
                    empty-message="No se encontraron avances con justificación de desviación."
                />
            </x-slot:tabla>
        </x-tracking.tabs-shell>

        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
