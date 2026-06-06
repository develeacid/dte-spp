<div wire:key="acumulado-anual-root">
    <x-page.header>
        <x-slot name="title">Acumulado Anual {{ $ejercicio }}</x-slot>
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
        />

        <x-tracking.tabs-shell :active="$activeTab">
            <x-slot:dashboard>
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    @php
                        $semaforoCounts = collect($filas)->countBy('semaforo');
                        $semaforoLabels = ['verde', 'amarillo', 'rojo', 'gris'];
                        $semaforoSeries = collect($semaforoLabels)->map(fn ($s) => $semaforoCounts->get($s, 0))->toArray();
                        $semaforoColors = ['#22c55e', '#eab308', '#ef4444', '#9ca3af'];
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Cumplimiento anual</h4>
                        <div wire:key="acumulado-donut-{{ md5(json_encode($semaforoSeries)) }}">
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
                            $acumulado = $items->sum(fn ($r) => (float) ($r['acumulado'] ?? 0));
                            return ['clave' => $clave, 'acumulado' => $acumulado];
                        })->values();
                    @endphp
                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                        <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Acumulado por programa</h4>
                        <div wire:key="acumulado-bar-{{ md5(json_encode($porPrograma)) }}">
                            <x-charts.bar-horizontal
                                :categories="$porPrograma->pluck('clave')->toArray()"
                                :series="[
                                    ['name' => 'Acumulado', 'data' => $porPrograma->pluck('acumulado')->toArray()],
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
                    empty-message="No se encontraron indicadores con avances en el ejercicio seleccionado."
                />
            </x-slot:tabla>
        </x-tracking.tabs-shell>

        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
