<div wire:key="sabana-captura-root">
    <x-page.header>
        <x-slot name="title">Sabana de Captura</x-slot>
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
            <x-filters.trimestre model="filtroTrimestre" />
            <x-filters.estado model="filtroEstado" :options="$estadosOpciones" />

            {{-- Toggle modo fecha (mutuamente exclusivo) --}}
            <div class="inline-flex rounded-md border border-slate-200 overflow-hidden dark:border-slate-700">
                <button type="button"
                        wire:click="$set('modoFecha', 'anio')"
                        @class([
                            'px-3 py-1.5 text-sm',
                            'bg-indigo-600 text-white' => $modoFecha === 'anio',
                            'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $modoFecha !== 'anio',
                        ])>
                    Año
                </button>
                <button type="button"
                        wire:click="$set('modoFecha', 'fecha')"
                        @class([
                            'px-3 py-1.5 text-sm border-l border-slate-200 dark:border-slate-700',
                            'bg-indigo-600 text-white' => $modoFecha === 'fecha',
                            'bg-white text-slate-700 dark:bg-slate-800 dark:text-slate-300' => $modoFecha !== 'fecha',
                        ])>
                    Fecha
                </button>
            </div>

            @if ($modoFecha === 'anio')
                <x-filters.ejercicio model="filtroEjercicio" />
            @else
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

            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="groupByPrograma" class="rounded border-slate-300" />
                Agrupar
            </label>

            <select wire:model.live="sortBy"
                    class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                <option value="indicador">Orden: Indicador</option>
                <option value="fecha">Orden: Fecha</option>
            </select>

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
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                @php
                    $estadoCounts = collect($filas)->countBy('estado');
                    $estadoLabels = ['aprobado', 'en_revision', 'en_captura', 'observado', 'pendiente', 'vencido'];
                    $estadoSeries = collect($estadoLabels)->map(fn ($e) => $estadoCounts->get($e, 0))->toArray();
                    $estadoColors = ['#22c55e', '#3b82f6', '#eab308', '#f97316', '#9ca3af', '#ef4444'];
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-1">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución de Estados</h4>
                    <div wire:key="sabana-donut-estado-{{ md5(json_encode($estadoSeries)) }}">
                        <x-charts.donut
                            :labels="$estadoLabels"
                            :series="$estadoSeries"
                            :colors="$estadoColors"
                            :height="220"
                            centerText="{{ array_sum($estadoSeries) }}"
                            centerSubtext="registros"
                        />
                    </div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
                    @php
                        $porProgramaEstado = collect($filas)->groupBy('programa_clave')->map(function ($items, $clave) {
                            return [
                                'clave' => $clave,
                                'aprobados' => $items->where('estado', 'aprobado')->count(),
                                'pendientes' => $items->whereIn('estado', ['pendiente', 'en_captura', 'en_revision'])->count(),
                                'problemas' => $items->whereIn('estado', ['observado', 'vencido'])->count(),
                            ];
                        })->values();
                    @endphp
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Estado por Programa</h4>
                    <div wire:key="sabana-bar-programa-{{ md5(json_encode($porProgramaEstado)) }}">
                        <x-charts.bar-horizontal
                            :categories="$porProgramaEstado->pluck('clave')->toArray()"
                            :series="[
                                ['name' => 'Aprobados', 'data' => $porProgramaEstado->pluck('aprobados')->toArray()],
                                ['name' => 'En proceso', 'data' => $porProgramaEstado->pluck('pendientes')->toArray()],
                                ['name' => 'Observado/Vencido', 'data' => $porProgramaEstado->pluck('problemas')->toArray()],
                            ]"
                            :height="max(200, $porProgramaEstado->count() * 40)"
                        />
                    </div>
                </div>
            </div>

            {{-- KPIs --}}
            @php
                $total = collect($filas)->count();
                $aprobados = collect($filas)->where('estado', 'aprobado')->count();
                $vencidos = collect($filas)->where('estado', 'vencido')->count();
                $enProceso = collect($filas)->whereIn('estado', ['pendiente', 'en_captura', 'en_revision'])->count();
            @endphp
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4">
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">Total</div>
                    <div class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $total }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">Aprobados</div>
                    <div class="text-2xl font-bold text-green-600">{{ $aprobados }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">En proceso</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $enProceso }}</div>
                </div>
                <div class="rounded-md border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <div class="text-xs text-slate-500">Vencidos</div>
                    <div class="text-2xl font-bold text-red-600">{{ $vencidos }}</div>
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
                empty-message="No se encontraron metas periodo con los filtros seleccionados."
            />
        @endif
    </x-page.container>
</div>
