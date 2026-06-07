<div>
    <x-page.header>
        <x-slot name="title">Evaluacion Transversal</x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Tabs --}}
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button wire:click="switchTab('ped')"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'ped' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Por Eje PED
                </button>
                <button wire:click="switchTab('ods')"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'ods' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Por ODS
                </button>
                <button wire:click="switchTab('ur')"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'ur' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Por Unidad Responsable
                </button>
                <button wire:click="switchTab('anexo')"
                    class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium {{ $tab === 'anexo' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' }}">
                    Por Anexo Transversal
                </button>
            </nav>
        </div>

        {{-- Tab: PED --}}
        @if($tab === 'ped')
            @if(!empty($data['sin_alineacion']))
                <div class="mb-6 rounded-md border border-yellow-300 bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">Programas sin alineacion PED</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach($data['sin_alineacion'] as $prog)
                                        <li>{{ $prog['clave'] }} - {{ $prog['programa'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Gráfica resumen PED --}}
            @if (!empty($data['ejes']))
            <div class="mb-6" wire:ignore>
                @php
                    $ejesChart = collect($data['ejes']);
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Índice de Eficacia por Eje PED</h4>
                    <x-charts.bar-horizontal
                        :categories="$ejesChart->pluck('eje_nombre')->map(fn ($n) => Str::limit($n, 30))->toArray()"
                        :series="[['name' => 'Índice %', 'data' => $ejesChart->pluck('promedio_indice')->toArray()]]"
                        :height="max(200, $ejesChart->count() * 45)"
                        :referenceLine="100"
                        referenceLabel="Meta"
                    />
                </div>
            </div>

            {{-- Heatmap PED × Programa --}}
            @php
                $heatmapRows = collect($data['ejes'])->map(fn ($e) => ['id' => $e['eje_numero'], 'nombre' => 'Eje ' . $e['eje_numero']]);
                $heatmapCols = collect($data['ejes'])->flatMap(fn ($e) => collect($e['programas'] ?? []))->pluck('clave')->unique()->values()->toArray();
                $heatmapValues = collect($data['ejes'])->map(function ($eje) use ($heatmapCols) {
                    $progs = collect($eje['programas'] ?? [])->keyBy('clave');
                    return collect($heatmapCols)->map(function ($clave) use ($progs) {
                        $p = $progs->get($clave);
                        return $p ? [
                            'valor' => $p['indice'] ?? 0,
                            'semaforo' => match(true) {
                                ($p['indice'] ?? 0) >= 75 => 'verde',
                                ($p['indice'] ?? 0) >= 50 => 'amarillo',
                                default => 'rojo',
                            },
                            'detalle' => $clave . ': ' . ($p['indice'] ?? 0) . '%',
                        ] : ['valor' => null, 'semaforo' => 'gris', 'detalle' => 'Sin datos'];
                    })->toArray();
                })->toArray();
            @endphp
            @if (count($heatmapCols) > 0)
            <div class="mb-6" wire:ignore>
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Matriz Eje × Programa</h4>
                    <x-charts.heatmap
                        :rows="$heatmapRows->toArray()"
                        :columns="$heatmapCols"
                        :values="$heatmapValues"
                    />
                </div>
            </div>
            @endif
            @endif

            @forelse($data['ejes'] as $eje)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Eje {{ $eje['eje_numero'] }}: {{ $eje['eje_nombre'] }}</h3>
                        <span class="text-2xl font-bold text-indigo-600">{{ number_format($eje['promedio_indice'], 2) }}%</span>
                    </div>

                    {{-- Semaforos --}}
                    <div class="mb-4 grid grid-cols-5 gap-2">
                        <div class="rounded bg-green-50 p-2 text-center">
                            <span class="text-lg font-bold text-green-700">{{ $eje['semaforos']['verde'] }}</span>
                            <p class="text-xs text-green-600">Verde</p>
                        </div>
                        <div class="rounded bg-yellow-50 p-2 text-center">
                            <span class="text-lg font-bold text-yellow-700">{{ $eje['semaforos']['amarillo'] }}</span>
                            <p class="text-xs text-yellow-600">Amarillo</p>
                        </div>
                        <div class="rounded bg-red-50 p-2 text-center">
                            <span class="text-lg font-bold text-red-700">{{ $eje['semaforos']['rojo'] }}</span>
                            <p class="text-xs text-red-600">Rojo</p>
                        </div>
                        <div class="rounded bg-purple-50 p-2 text-center">
                            <span class="text-lg font-bold text-purple-700">{{ $eje['semaforos']['rojo_alto'] ?? 0 }}</span>
                            <p class="text-xs text-purple-600">Rojo alto</p>
                        </div>
                        <div class="rounded bg-gray-50 p-2 text-center">
                            <span class="text-lg font-bold text-gray-700">{{ $eje['semaforos']['sin_dato'] }}</span>
                            <p class="text-xs text-gray-600">Sin dato</p>
                        </div>
                    </div>

                    {{-- Programs table --}}
                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Indice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($eje['programas'] as $prog)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $prog['clave'] }} - {{ $prog['nombre'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">{{ number_format((float) $prog['indice'], 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
                    No hay evaluaciones disponibles para este ejercicio fiscal.
                </div>
            @endforelse
        @endif

        {{-- Tab: ODS --}}
        @if($tab === 'ods')
            {{-- Gráfica resumen ODS --}}
            @if (is_iterable($data) && count($data) > 0)
            <div class="mb-6" wire:ignore>
                @php
                    $odsChart = collect($data);
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Índice de Eficacia por ODS</h4>
                    <x-charts.bar-horizontal
                        :categories="$odsChart->pluck('ods_nombre')->map(fn ($n) => Str::limit($n, 30))->toArray()"
                        :series="[['name' => 'Índice %', 'data' => $odsChart->pluck('promedio_indice')->toArray()]]"
                        :height="max(200, $odsChart->count() * 45)"
                        :referenceLine="100"
                        referenceLabel="Meta"
                    />
                </div>
            </div>
            @endif

            @forelse($data as $ods)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">ODS {{ $ods['ods_numero'] }}: {{ $ods['ods_nombre'] }}</h3>
                        <span class="text-2xl font-bold text-indigo-600">{{ number_format($ods['promedio_indice'], 2) }}%</span>
                    </div>

                    <div class="mb-4 grid grid-cols-5 gap-2">
                        <div class="rounded bg-green-50 p-2 text-center">
                            <span class="text-lg font-bold text-green-700">{{ $ods['semaforos']['verde'] }}</span>
                            <p class="text-xs text-green-600">Verde</p>
                        </div>
                        <div class="rounded bg-yellow-50 p-2 text-center">
                            <span class="text-lg font-bold text-yellow-700">{{ $ods['semaforos']['amarillo'] }}</span>
                            <p class="text-xs text-yellow-600">Amarillo</p>
                        </div>
                        <div class="rounded bg-red-50 p-2 text-center">
                            <span class="text-lg font-bold text-red-700">{{ $ods['semaforos']['rojo'] }}</span>
                            <p class="text-xs text-red-600">Rojo</p>
                        </div>
                        <div class="rounded bg-purple-50 p-2 text-center">
                            <span class="text-lg font-bold text-purple-700">{{ $ods['semaforos']['rojo_alto'] ?? 0 }}</span>
                            <p class="text-xs text-purple-600">Rojo alto</p>
                        </div>
                        <div class="rounded bg-gray-50 p-2 text-center">
                            <span class="text-lg font-bold text-gray-700">{{ $ods['semaforos']['sin_dato'] }}</span>
                            <p class="text-xs text-gray-600">Sin dato</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Indice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($ods['programas'] as $prog)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $prog['clave'] }} - {{ $prog['nombre'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">{{ number_format((float) $prog['indice'], 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
                    No hay vinculacion ODS disponible para este ejercicio fiscal.
                </div>
            @endforelse
        @endif

        {{-- Tab: UR --}}
        @if($tab === 'ur')
            {{-- Gráfica resumen UR --}}
            @if (is_iterable($data) && count($data) > 0)
            <div class="mb-6" wire:ignore>
                @php
                    $urChart = collect($data);
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Índice de Eficacia por Unidad Responsable</h4>
                    <x-charts.bar-horizontal
                        :categories="$urChart->pluck('team_nombre')->map(fn ($n) => Str::limit($n, 30))->toArray()"
                        :series="[['name' => 'Índice %', 'data' => $urChart->pluck('promedio_indice')->toArray()]]"
                        :height="max(200, $urChart->count() * 45)"
                        :referenceLine="100"
                        referenceLabel="Meta"
                    />
                </div>
            </div>
            @endif

            @forelse($data as $ur)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">{{ $ur['ranking'] }}</span>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $ur['team_nombre'] }}</h3>
                        </div>
                        <span class="text-2xl font-bold text-indigo-600">{{ number_format($ur['promedio_indice'], 2) }}%</span>
                    </div>

                    <div class="mb-4 grid grid-cols-5 gap-2">
                        <div class="rounded bg-green-50 p-2 text-center">
                            <span class="text-lg font-bold text-green-700">{{ $ur['semaforos']['verde'] }}</span>
                            <p class="text-xs text-green-600">Verde</p>
                        </div>
                        <div class="rounded bg-yellow-50 p-2 text-center">
                            <span class="text-lg font-bold text-yellow-700">{{ $ur['semaforos']['amarillo'] }}</span>
                            <p class="text-xs text-yellow-600">Amarillo</p>
                        </div>
                        <div class="rounded bg-red-50 p-2 text-center">
                            <span class="text-lg font-bold text-red-700">{{ $ur['semaforos']['rojo'] }}</span>
                            <p class="text-xs text-red-600">Rojo</p>
                        </div>
                        <div class="rounded bg-purple-50 p-2 text-center">
                            <span class="text-lg font-bold text-purple-700">{{ $ur['semaforos']['rojo_alto'] ?? 0 }}</span>
                            <p class="text-xs text-purple-600">Rojo alto</p>
                        </div>
                        <div class="rounded bg-gray-50 p-2 text-center">
                            <span class="text-lg font-bold text-gray-700">{{ $ur['semaforos']['sin_dato'] }}</span>
                            <p class="text-xs text-gray-600">Sin dato</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Indice</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($ur['programas'] as $prog)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $prog['clave'] }} - {{ $prog['nombre'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm font-medium text-gray-900">{{ number_format((float) $prog['indice'], 2) }}%</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
                    No hay evaluaciones por unidad responsable disponibles.
                </div>
            @endforelse
        @endif

        {{-- Tab: Anexo --}}
        @if($tab === 'anexo')
            {{-- Gráfica resumen Anexo --}}
            @if (is_iterable($data) && count($data) > 0)
            <div class="mb-6" wire:ignore>
                @php
                    $anexoChart = collect($data);
                @endphp
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">Índice de Eficacia por Anexo Transversal</h4>
                    <x-charts.bar-horizontal
                        :categories="$anexoChart->pluck('anexo_nombre')->map(fn ($n) => Str::limit($n, 30))->toArray()"
                        :series="[['name' => 'Índice %', 'data' => $anexoChart->pluck('promedio_indice')->toArray()]]"
                        :height="max(200, $anexoChart->count() * 45)"
                        :referenceLine="100"
                        referenceLabel="Meta"
                    />
                </div>
            </div>
            @endif

            @forelse($data as $anexo)
                <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $anexo['anexo_nombre'] }}</h3>
                            <p class="text-sm text-gray-500">{{ $anexo['anexo_clave'] }} &middot; {{ $anexo['total_indicadores'] }} indicadores &middot; {{ $anexo['total_programas'] }} programas</p>
                        </div>
                        <span class="text-2xl font-bold text-indigo-600">{{ number_format($anexo['promedio_indice'], 2) }}%</span>
                    </div>

                    <div class="mb-4 grid grid-cols-5 gap-2">
                        <div class="rounded bg-green-50 p-2 text-center">
                            <span class="text-lg font-bold text-green-700">{{ $anexo['semaforos']['verde'] }}</span>
                            <p class="text-xs text-green-600">Verde</p>
                        </div>
                        <div class="rounded bg-yellow-50 p-2 text-center">
                            <span class="text-lg font-bold text-yellow-700">{{ $anexo['semaforos']['amarillo'] }}</span>
                            <p class="text-xs text-yellow-600">Amarillo</p>
                        </div>
                        <div class="rounded bg-red-50 p-2 text-center">
                            <span class="text-lg font-bold text-red-700">{{ $anexo['semaforos']['rojo'] }}</span>
                            <p class="text-xs text-red-600">Rojo</p>
                        </div>
                        <div class="rounded bg-purple-50 p-2 text-center">
                            <span class="text-lg font-bold text-purple-700">{{ $anexo['semaforos']['rojo_alto'] ?? 0 }}</span>
                            <p class="text-xs text-purple-600">Rojo alto</p>
                        </div>
                        <div class="rounded bg-gray-50 p-2 text-center">
                            <span class="text-lg font-bold text-gray-700">{{ $anexo['semaforos']['sin_dato'] }}</span>
                            <p class="text-xs text-gray-600">Sin dato</p>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Nivel</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @foreach($anexo['indicadores'] as $ind)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $ind['nombre'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600">{{ $ind['clave_programa'] }} - {{ $ind['programa'] }}</td>
                                        <td class="px-4 py-3 text-center text-sm text-gray-600">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $ind['nivel']->colorClass() }}">
                                                {{ $ind['nivel']->label() }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-lg border border-gray-200 bg-white p-6 text-center text-gray-500">
                    No hay indicadores etiquetados con anexos transversales.
                </div>
            @endforelse
        @endif
    </x-page.container>
</div>
