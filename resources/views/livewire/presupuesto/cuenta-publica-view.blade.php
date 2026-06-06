<div>
    <x-page.header title="Cuenta Pública">
        <div class="flex items-center gap-3">
            <a href="{{ route('presupuesto.exportar.pdf', $filtroEjercicio) }}" class="inline-flex items-center gap-1 rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                PDF
            </a>
            <a href="{{ route('presupuesto.exportar.excel', $filtroEjercicio) }}" class="inline-flex items-center gap-1 rounded-md bg-green-600 px-3 py-2 text-sm font-medium text-white hover:bg-green-700">
                Excel
            </a>
        </div>
    </x-page.header>

    <x-page.container fluid>
        {{-- Filtros --}}
        <div class="mb-6 flex items-center gap-4">
            <div>
                <x-label for="filtroEjercicio" value="Ejercicio Fiscal" />
                <select id="filtroEjercicio" wire:model.live="filtroEjercicio" class="mt-1 w-40 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div class="mt-6">
                <button wire:click="toggleVistaEje" @class([
                    'rounded-md px-3 py-2 text-sm font-medium',
                    'bg-brand text-white' => $vistaEje,
                    'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300' => !$vistaEje,
                ])>
                    Agrupar por Eje PED
                </button>
            </div>
        </div>

        @if ($vistaEje && $resumenEjes->isNotEmpty())
            {{-- Vista agrupada por Eje PED --}}
            @foreach ($resumenEjes as $eje)
                <div class="mb-6 rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $eje->eje }}</h3>
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            ${{ number_format($eje->total_ejercido, 2) }} / ${{ number_format($eje->total_aprobado, 2) }}
                            <span @class([
                                'ml-2 font-semibold',
                                'text-green-600' => $eje->pct_ejercido >= 75,
                                'text-yellow-600' => $eje->pct_ejercido >= 40 && $eje->pct_ejercido < 75,
                                'text-red-600' => $eje->pct_ejercido < 40,
                            ])>({{ $eje->pct_ejercido }}%)</span>
                        </div>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Programa</th>
                                <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">Ejercido</th>
                                <th class="px-4 py-2 text-center text-xs font-medium uppercase text-gray-500">Eficiencia</th>
                                <th class="px-4 py-2 text-center text-xs font-medium uppercase text-gray-500">Semáforo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                            @foreach ($eje->programas as $item)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{ $item['programa']->clave }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-mono">${{ number_format($item['financiero']->pagado, 2) }}</td>
                                    <td class="px-4 py-2 text-center text-sm">{{ $item['eficiencia'] !== null ? $item['eficiencia'] : '—' }}</td>
                                    <td class="px-4 py-2 text-center">
                                        @include('livewire.presupuesto._semaforo-badge', ['color' => $item['semaforo']['combinado']])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        @else
            {{-- Vista por programa --}}
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Programa</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Aprobado</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500">Ejercido</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">% Fin.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Eficiencia</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Sem. Físico</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Sem. Fin.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500">Combinado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Alineación PED</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse ($datos as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-3 text-sm">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $item['programa']->clave }}</span>
                                    <span class="ml-1 text-gray-500">{{ Str::limit($item['programa']->nombre, 30) }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono">${{ number_format($item['financiero']->efectivo, 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono">${{ number_format($item['financiero']->pagado, 2) }}</td>
                                <td class="px-4 py-3 text-center text-sm font-semibold">{{ $item['financiero']->pct_ejercido }}%</td>
                                <td class="px-4 py-3 text-center text-sm">
                                    @if ($item['eficiencia'] !== null)
                                        <span @class([
                                            'font-semibold',
                                            'text-green-600' => $item['eficiencia'] >= 0.9 && $item['eficiencia'] <= 1.1,
                                            'text-yellow-600' => $item['eficiencia'] < 0.9 || $item['eficiencia'] > 1.1,
                                            'text-red-600' => $item['eficiencia'] < 0.6 || $item['eficiencia'] > 1.5,
                                        ])>{{ $item['eficiencia'] }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @include('livewire.presupuesto._semaforo-badge', ['color' => $item['semaforo']['fisico']])
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @include('livewire.presupuesto._semaforo-badge', ['color' => $item['semaforo']['financiero']])
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @include('livewire.presupuesto._semaforo-badge', ['color' => $item['semaforo']['combinado']])
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                    @if ($item['alineacion']['ped_eje'])
                                        <span class="font-medium">{{ $item['alineacion']['ped_eje'] }}</span>
                                        @if ($item['alineacion']['ped_objetivo'])
                                            <br>{{ Str::limit($item['alineacion']['ped_objetivo'], 40) }}
                                        @endif
                                    @else
                                        Sin alineación
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">No hay datos para este ejercicio.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
