<div>
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

    <x-page.container>
        {{-- Resumen visual de estados (calculado sobre TODAS las filas filtradas, no la página) --}}
        <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3" wire:ignore>
            @php
                $estadoCounts = collect($filas)->countBy('estado');
                $estadoLabels = ['aprobado', 'en_revision', 'en_captura', 'observado', 'pendiente', 'vencido'];
                $estadoSeries = collect($estadoLabels)->map(fn ($e) => $estadoCounts->get($e, 0))->toArray();
                $estadoColors = ['#22c55e', '#3b82f6', '#eab308', '#f97316', '#9ca3af', '#ef4444'];
            @endphp
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 lg:col-span-1">
                <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Distribución de Estados</h4>
                <x-charts.donut
                    :labels="$estadoLabels"
                    :series="$estadoSeries"
                    :colors="$estadoColors"
                    :height="220"
                    centerText="{{ array_sum($estadoSeries) }}"
                    centerSubtext="registros"
                />
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

        <x-data.table
            :rows="$rows"
            :columns="$columns"
            :search="$search"
            :sort-by="$sortBy"
            :sort-dir="$sortDir"
            :group-by="$groupByPrograma ? 'programa' : null"
            :per-page="$perPage"
            :traceable="true"
            :row-class="$rowClass"
            search-placeholder="Buscar indicador..."
            empty-message="No se encontraron metas periodo con los filtros seleccionados."
        >
            <x-slot:filters>
                <select wire:model.live="filtroPrograma"
                        class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">Todos los programas</option>
                    @foreach($programas as $programa)
                        <option value="{{ $programa->id }}">{{ $programa->clave }} - {{ $programa->nombre }}</option>
                    @endforeach
                </select>

                <select wire:model.live="filtroTrimestre"
                        class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">Todos los trimestres</option>
                    <option value="1">T1</option>
                    <option value="2">T2</option>
                    <option value="3">T3</option>
                    <option value="4">T4</option>
                </select>

                <select wire:model.live="filtroEstado"
                        class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_captura">En captura</option>
                    <option value="en_revision">En revisión</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="observado">Observado</option>
                    <option value="vencido">Vencido</option>
                </select>
            </x-slot:filters>
        </x-data.table>
    </x-page.container>
</div>
