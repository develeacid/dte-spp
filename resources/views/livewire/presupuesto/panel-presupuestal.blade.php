<div>
    <x-page.header title="Panel Presupuestal" />

    <x-page.container>
        {{-- Filtro de ejercicio --}}
        <div class="mb-6">
            <x-label for="filtroEjercicio" value="Ejercicio Fiscal" />
            <select id="filtroEjercicio" wire:model.live="filtroEjercicio" class="mt-1 w-40 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>

        {{-- KPIs --}}
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Aprobado</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalAprobado, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Ejercido</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalEjercido, 2) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">% Ejercido</p>
                <p @class([
                    'mt-1 text-2xl font-bold',
                    'text-green-600' => $pctEjercido >= 75,
                    'text-yellow-600' => $pctEjercido >= 40 && $pctEjercido < 75,
                    'text-red-600' => $pctEjercido < 40,
                ])>{{ $pctEjercido }}%</p>
            </div>
        </div>

        {{-- Tabla de programas --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Programa</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Aprobado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Ejercido</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Avance</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($programas as $programa)
                        @php
                            $aprobado = $programa->partidasPresupuestales->sum(fn ($p) => $p->monto_efectivo);
                            $ejercido = $programa->partidasPresupuestales->sum(fn ($p) => $p->avancesFinancieros->sum('monto_pagado'));
                            $pct = $aprobado > 0 ? round(($ejercido / $aprobado) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $programa->clave }}</span>
                                <span class="ml-1 text-gray-500 dark:text-gray-400">{{ Str::limit($programa->nombre, 40) }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono text-gray-900 dark:text-white">${{ number_format($aprobado, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono text-gray-700 dark:text-gray-300">${{ number_format($ejercido, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div @class([
                                            'h-full rounded-full',
                                            'bg-green-500' => $pct >= 75,
                                            'bg-yellow-500' => $pct >= 40 && $pct < 75,
                                            'bg-red-500' => $pct < 40,
                                        ]) style="width: {{ min($pct, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">{{ $pct }}%</span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                @can('capturar_avance_financiero')
                                    <a href="{{ route('presupuesto.captura', $programa) }}" class="text-sm text-brand hover:text-brand-dark">Capturar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay programas presupuestarios para este ejercicio.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-page.container>
</div>
