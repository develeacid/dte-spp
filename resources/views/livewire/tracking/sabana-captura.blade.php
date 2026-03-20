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
        {{-- Filters bar --}}
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-3">
            <div>
                <label for="filtroPrograma" class="block text-sm font-medium text-gray-700">Programa</label>
                <select wire:model.live="filtroPrograma" id="filtroPrograma"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los programas</option>
                    @foreach($programas as $programa)
                        <option value="{{ $programa->id }}">{{ $programa->clave }} - {{ $programa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtroTrimestre" class="block text-sm font-medium text-gray-700">Trimestre</label>
                <select wire:model.live="filtroTrimestre" id="filtroTrimestre"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los trimestres</option>
                    <option value="1">T1 - Primer trimestre</option>
                    <option value="2">T2 - Segundo trimestre</option>
                    <option value="3">T3 - Tercer trimestre</option>
                    <option value="4">T4 - Cuarto trimestre</option>
                </select>
            </div>
            <div>
                <label for="filtroEstado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select wire:model.live="filtroEstado" id="filtroEstado"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_captura">En captura</option>
                    <option value="en_revision">En revision</option>
                    <option value="aprobado">Aprobado</option>
                    <option value="observado">Observado</option>
                    <option value="vencido">Vencido</option>
                </select>
            </div>
        </div>

        {{-- Resumen visual de estados --}}
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

        {{-- Main table --}}
        @if($filas->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-gray-500">No se encontraron metas periodo con los filtros seleccionados.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">T</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Meta</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Operador</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Cierre</th>
                                <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Dias</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($filas as $fila)
                                @php
                                    $rowClass = match($fila['estado']) {
                                        'vencido' => 'bg-red-50',
                                        'aprobado' => 'bg-green-50',
                                        default => '',
                                    };
                                    $estadoBadge = match($fila['estado']) {
                                        'pendiente' => 'bg-gray-100 text-gray-800',
                                        'en_captura' => 'bg-blue-100 text-blue-800',
                                        'en_revision' => 'bg-yellow-100 text-yellow-800',
                                        'aprobado' => 'bg-green-100 text-green-800',
                                        'observado' => 'bg-orange-100 text-orange-800',
                                        'vencido' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    };
                                    $estadoLabel = match($fila['estado']) {
                                        'pendiente' => 'Pendiente',
                                        'en_captura' => 'En captura',
                                        'en_revision' => 'En revision',
                                        'aprobado' => 'Aprobado',
                                        'observado' => 'Observado',
                                        'vencido' => 'Vencido',
                                        default => $fila['estado'],
                                    };
                                    $diasClass = match(true) {
                                        $fila['dias'] < 0 => 'text-red-600 font-bold',
                                        $fila['dias'] <= 7 => 'text-orange-600 font-semibold',
                                        default => 'text-gray-700',
                                    };
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                        <span class="font-medium">{{ $fila['programa_clave'] }}</span>
                                    </td>
                                    <td class="max-w-xs truncate px-4 py-3 text-sm text-gray-900">{{ $fila['indicador'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700">T{{ $fila['periodo'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700">{{ $fila['meta_periodo'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $estadoBadge }}">
                                            {{ $estadoLabel }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ $fila['operador'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700">{{ $fila['fecha_cierre'] }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-center text-sm {{ $diasClass }}">{{ $fila['dias'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </x-page.container>
</div>
