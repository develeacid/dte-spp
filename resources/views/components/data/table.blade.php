@props([
    'rows',
    'columns' => [],
    'search' => '',
    'sortBy' => '',
    'sortDir' => 'asc',
    'groupBy' => null,
    'perPage' => 25,
    'traceable' => false,
    'searchPlaceholder' => 'Buscar…',
    'emptyMessage' => 'Sin registros',
])

<div class="space-y-3">
    {{-- Controls bar --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="flex-1 min-w-[200px]">
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ $searchPlaceholder }}"
                value="{{ $search }}"
                class="w-full rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
            />
        </div>

        @if ($traceable)
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" wire:model.live="groupByPrograma" class="rounded border-slate-300" />
                Agrupar por programa
            </label>

            <select wire:model.live="sortBy" class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
                <option value="indicador">Orden: Indicador</option>
                <option value="fecha">Orden: Fecha</option>
            </select>
        @endif

        <select wire:model.live="perPage" class="rounded-md border-slate-200 text-sm dark:border-slate-700 dark:bg-slate-800">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="">Todas</option>
        </select>

        {{ $filters ?? '' }}
    </div>

    {{-- Tabla --}}
    @if ($rows->isEmpty())
        <div class="rounded-md border border-dashed border-slate-200 p-8 text-center text-sm text-slate-500 dark:border-slate-700">
            {{ $emptyMessage }}
        </div>
    @else
        {{-- Desktop --}}
        <div class="hidden sm:block overflow-x-auto rounded-md border border-slate-200 dark:border-slate-700">
            <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
                <thead class="bg-slate-50 dark:bg-slate-900">
                    <tr>
                        @if ($traceable)
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">Trazabilidad</th>
                        @endif
                        @foreach ($columns as $col)
                            <th class="px-3 py-2 text-left text-xs font-semibold text-slate-600 dark:text-slate-300">
                                {{ $col['label'] }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white dark:divide-slate-800 dark:bg-slate-950">
                    @php $currentGroup = null; @endphp
                    @foreach ($rows as $row)
                        @if ($traceable && $groupBy === 'programa')
                            @php
                                $programaClave = (is_object($row) && isset($row->trazabilidad)) ? $row->trazabilidad->programa() : (data_get($row, 'programa_clave') ?? '—');
                            @endphp
                            @if ($programaClave !== $currentGroup)
                                @php $currentGroup = $programaClave; @endphp
                                <tr class="bg-slate-100 dark:bg-slate-800">
                                    <td colspan="{{ count($columns) + ($traceable ? 1 : 0) }}" class="px-3 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        {{ $currentGroup }}
                                    </td>
                                </tr>
                            @endif
                        @endif
                        <tr>
                            @if ($traceable)
                                <td class="px-3 py-2 text-sm">
                                    @if (is_object($row) && isset($row->trazabilidad))
                                        <x-data.indicador-badge :trazabilidad="$row->trazabilidad" />
                                    @endif
                                </td>
                            @endif
                            @foreach ($columns as $col)
                                <td class="px-3 py-2 text-sm text-slate-700 dark:text-slate-300">
                                    {{ data_get($row, $col['key']) }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Mobile cards --}}
        <div class="sm:hidden space-y-2">
            @php $currentGroup = null; @endphp
            @foreach ($rows as $row)
                @if ($traceable && $groupBy === 'programa')
                    @php
                        $programaClave = (is_object($row) && isset($row->trazabilidad)) ? $row->trazabilidad->programa() : (data_get($row, 'programa_clave') ?? '—');
                    @endphp
                    @if ($programaClave !== $currentGroup)
                        @php $currentGroup = $programaClave; @endphp
                        <div class="sticky top-0 bg-slate-100 dark:bg-slate-800 px-3 py-2 text-xs font-semibold rounded">
                            {{ $currentGroup }}
                        </div>
                    @endif
                @endif
                <div class="rounded-md border border-slate-200 p-3 dark:border-slate-700">
                    @if ($traceable && is_object($row) && isset($row->trazabilidad))
                        <x-data.indicador-badge :trazabilidad="$row->trazabilidad" class="mb-2" />
                    @endif
                    @foreach ($columns as $col)
                        <div class="flex justify-between py-0.5 text-sm">
                            <span class="text-slate-500">{{ $col['label'] }}:</span>
                            <span>{{ data_get($row, $col['key']) }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- Cap warning --}}
        @if (! $perPage && $rows->count() >= 1000)
            <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
                Mostrando 1000 de muchos más. Refina tu búsqueda.
            </div>
        @endif

        {{-- Paginator --}}
        @if (method_exists($rows, 'links'))
            <div>{{ $rows->links() }}</div>
        @endif
    @endif
</div>
