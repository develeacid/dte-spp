<div wire:key="data-table-root">
    <x-page.header>
        <x-slot name="title">Seguimiento de indicadores</x-slot>
    </x-page.header>

    <x-page.container>
        <x-page.toolbar>
            <div class="flex-1 min-w-[200px]">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar indicador..."
                    class="w-full rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
                />
            </div>

            <x-filters.programa model="filtroPrograma" :options="$programasOpciones" />
            <x-filters.estado model="filtroEstado" :options="$estadosOpciones" />
            <x-filters.semaforo model="filtroSemaforo" :options="$semaforosOpciones" />

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="groupByPrograma" class="rounded border-slate-300" />
                Agrupar
            </label>

            <select wire:model.live="perPage"
                    class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="">Todas</option>
            </select>
        </x-page.toolbar>

        <x-page.tabs
            :tabs="['dashboard' => 'Dashboard', 'tabla' => 'Tabla']"
            :active="$activeTab"
            model="activeTab"
        />

        @if ($activeTab === 'dashboard')
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2" wire:ignore>
                @php
                    $semaforoCounts = collect($filas)->countBy('semaforo');
                    $semaforoLabels = ['verde', 'amarillo', 'rojo', 'gris'];
                    $semaforoSeries = collect($semaforoLabels)->map(fn ($s) => $semaforoCounts->get($s, 0))->values()->toArray();
                    $semaforoColors = ['#22c55e', '#eab308', '#ef4444', '#9ca3af'];
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Semáforo de Indicadores</h4>
                    <x-charts.donut
                        :labels="$semaforoLabels"
                        :series="$semaforoSeries"
                        :colors="$semaforoColors"
                        :height="220"
                        centerText="{{ array_sum($semaforoSeries) }}"
                        centerSubtext="indicadores"
                    />
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
                    <x-charts.bar-horizontal
                        :categories="$porPrograma->pluck('clave')->toArray()"
                        :series="[['name' => 'Avance %', 'data' => $porPrograma->pluck('avance')->toArray()]]"
                        :height="max(200, $porPrograma->count() * 35)"
                        :referenceLine="100"
                        referenceLabel="Meta"
                    />
                </div>
            </div>

            {{-- KPIs --}}
            @php
                $total = collect($filas)->count();
                $verdes = collect($filas)->where('semaforo', 'verde')->count();
                $amarillos = collect($filas)->where('semaforo', 'amarillo')->count();
                $rojos = collect($filas)->where('semaforo', 'rojo')->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">Total</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $total }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">En verde</div>
                    <div class="text-2xl font-bold text-green-600">{{ $verdes }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">En amarillo</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $amarillos }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">En rojo</div>
                    <div class="text-2xl font-bold text-red-600">{{ $rojos }}</div>
                </div>
            </div>
        @else
            <x-data.table
                :rows="$rows"
                :columns="$columns"
                :group-by="$groupByPrograma ? 'programa' : null"
                :per-page="$perPage"
                :traceable="true"
                :row-class="$rowClass"
                empty-message="No se encontraron indicadores con los filtros seleccionados."
            />
        @endif
    </x-page.container>
</div>
