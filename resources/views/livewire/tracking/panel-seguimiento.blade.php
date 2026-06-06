<div wire:key="data-table-root">
    <x-page.header>
        <x-slot name="title">Seguimiento de indicadores</x-slot>
    </x-page.header>

    <x-page.container fluid>
        <x-page.toolbar>
            {{-- Búsqueda standalone --}}
            <div class="w-full">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar indicador..."
                    class="w-full max-w-md rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
                />
            </div>

            {{-- Familia 1: Ámbito --}}
            <div class="w-full flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide min-w-[60px] dark:text-slate-400">Ámbito</span>
                <x-filters.programa model="filtroPrograma" :options="$programasOpciones" />
                <x-filters.mir-nivel model="filtroMirNivel" :options="$nivelesOpciones" />
                <x-filters.estado model="filtroEstado" :options="$estadosOpciones" />
                <x-filters.semaforo model="filtroSemaforo" :options="$semaforosOpciones" />
            </div>

            {{-- Familia 2: Tiempo --}}
            <div class="w-full flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide min-w-[60px] dark:text-slate-400">Tiempo</span>
                <div class="inline-flex rounded-md border border-slate-200 overflow-hidden dark:border-slate-700">
                <button type="button"
                        wire:click="$set('alcanceTemporal', 'todo')"
                        @class([
                            'px-3 py-1.5 text-sm',
                            'bg-indigo-600 text-white' => $alcanceTemporal === 'todo',
                            'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $alcanceTemporal !== 'todo',
                        ])>
                    Todo
                </button>
                <button type="button"
                        wire:click="$set('alcanceTemporal', 'anio')"
                        @class([
                            'px-3 py-1.5 text-sm border-l border-slate-200 dark:border-slate-700',
                            'bg-indigo-600 text-white' => $alcanceTemporal === 'anio',
                            'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $alcanceTemporal !== 'anio',
                        ])>
                    Año
                </button>
                <button type="button"
                        wire:click="$set('alcanceTemporal', 'rango')"
                        @class([
                            'px-3 py-1.5 text-sm border-l border-slate-200 dark:border-slate-700',
                            'bg-indigo-600 text-white' => $alcanceTemporal === 'rango',
                            'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $alcanceTemporal !== 'rango',
                        ])>
                    Rango
                </button>
                </div>

                @if ($alcanceTemporal === 'anio')
                    <x-filters.ejercicio model="filtroEjercicio" />
                @elseif ($alcanceTemporal === 'rango')
                    <div class="flex items-center gap-2">
                        <input type="date"
                               wire:model.live="filtroFechaDesde"
                               title="Desde"
                               class="rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800" />
                        <span class="text-sm text-slate-500">—</span>
                        <input type="date"
                               wire:model.live="filtroFechaHasta"
                               title="Hasta"
                               class="rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800" />
                    </div>
                @endif

                {{-- Trimestre solo tiene sentido cuando hay un año específico --}}
                @if ($alcanceTemporal === 'anio')
                    <x-filters.trimestre model="filtroTrimestre" />
                @endif
            </div>

            {{-- Familia 3: Vista --}}
            <div class="w-full flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide min-w-[60px] dark:text-slate-400">Vista</span>

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
            </div>
        </x-page.toolbar>

        <x-page.tabs
            :tabs="['dashboard' => 'Dashboard', 'tabla' => 'Tabla']"
            :active="$activeTab"
            model="activeTab"
        />

        @if ($activeTab === 'dashboard')
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

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    {{-- KPI status bar fija al viewport (respeta sidebar) --}}
    @php
        $total = collect($filas)->count();
        $verdes = collect($filas)->where('semaforo', 'verde')->count();
        $amarillos = collect($filas)->where('semaforo', 'amarillo')->count();
        $rojos = collect($filas)->where('semaforo', 'rojo')->count();
    @endphp
    <div class="fixed bottom-0 right-0 left-0 z-30 py-3 px-4 sm:px-6 lg:px-8 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-700"
         :class="collapsed ? 'lg:!left-[var(--sidebar-collapsed-width)]' : 'lg:!left-[var(--sidebar-width)]'">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="text-xs text-slate-500">Total</div>
                <div class="text-xl font-bold text-slate-900 dark:text-slate-100">{{ $total }}</div>
            </div>
            <div class="rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="text-xs text-slate-500">En verde</div>
                <div class="text-xl font-bold text-green-600">{{ $verdes }}</div>
            </div>
            <div class="rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="text-xs text-slate-500">En amarillo</div>
                <div class="text-xl font-bold text-yellow-600">{{ $amarillos }}</div>
            </div>
            <div class="rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="text-xs text-slate-500">En rojo</div>
                <div class="text-xl font-bold text-red-600">{{ $rojos }}</div>
            </div>
        </div>
    </div>
</div>
