@props([
    'search' => '',
    'searchPlaceholder' => 'Buscar...',
    'programasOpciones' => [],
    'nivelesOpciones' => [],
    'estadosOpciones' => [],
    'alcanceTemporal' => 'todo',
    'filtroFechaDesde' => null,
    'filtroFechaHasta' => null,
    'groupByPrograma' => true,
    'perPage' => 25,
    'showOrden' => true,
    'sortBy' => 'indicador',
])

<x-page.toolbar>
    {{-- Busqueda standalone --}}
    <div class="w-full">
        <input
            type="search"
            wire:model.live.debounce.300ms="search"
            placeholder="{{ $searchPlaceholder }}"
            class="w-full max-w-md rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
        />
    </div>

    {{-- Familia 1: Ambito --}}
    <div class="w-full flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide min-w-[60px] dark:text-slate-400">Ámbito</span>
        <x-filters.programa model="filtroPrograma" :options="$programasOpciones" />
        <x-filters.mir-nivel model="filtroMirNivel" :options="$nivelesOpciones" />
        <x-filters.estado model="filtroEstado" :options="$estadosOpciones" />
        {{ $ambitoExtra ?? '' }}
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

        @if ($alcanceTemporal === 'anio')
            <x-filters.trimestre model="filtroTrimestre" />
        @endif

        {{ $tiempoExtra ?? '' }}
    </div>

    {{-- Familia 3: Vista --}}
    <div class="w-full flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide min-w-[60px] dark:text-slate-400">Vista</span>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model.live="groupByPrograma" class="rounded border-slate-300" />
            Agrupar
        </label>

        @if ($showOrden)
            <select wire:model.live="sortBy"
                    class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                <option value="indicador">Orden: Indicador</option>
                <option value="fecha">Orden: Fecha</option>
            </select>
        @endif

        <select wire:model.live="perPage"
                class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="">Todas</option>
        </select>

        {{ $vistaExtra ?? '' }}
    </div>
</x-page.toolbar>
