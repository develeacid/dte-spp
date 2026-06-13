<div>
    <x-page.header title="Partidas Presupuestales">
        <x-ui.button.primary href="{{ route('presupuesto.partidas.create') }}">
            Nueva Partida
        </x-ui.button.primary>
    </x-page.header>

    <x-page.container fluid>
        {{-- Filtros --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <x-label for="search" value="Buscar" />
                <x-input id="search" type="text" class="mt-1 block w-full" wire:model.live.debounce.300ms="search" placeholder="Clave o descripción..." />
            </div>
            <div>
                <x-label for="filtroPrograma" value="Programa" />
                <select id="filtroPrograma" wire:model.live="filtroPrograma" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos los programas</option>
                    @foreach ($programas as $programa)
                        <option value="{{ $programa->id }}">{{ $programa->clave }} — {{ $programa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <x-label for="filtroEjercicio" value="Ejercicio Fiscal" />
                <select id="filtroEjercicio" wire:model.live="filtroEjercicio" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
        </div>

        {{-- Tabla --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Clave</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Programa</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Aprobado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Modificado</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">% Ejercido</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($partidas as $partida)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-white">{{ $partida->clave_partida }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $partida->descripcion }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $partida->programa->clave ?? '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono text-gray-900 dark:text-white">${{ number_format($partida->monto_aprobado, 2) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-mono text-gray-500 dark:text-gray-400">
                                {{ $partida->monto_modificado ? '$' . number_format($partida->monto_modificado, 2) : '—' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                <span @class([
                                    'font-semibold',
                                    'text-green-600' => $partida->porcentaje_ejercido >= 75,
                                    'text-yellow-600' => $partida->porcentaje_ejercido >= 40 && $partida->porcentaje_ejercido < 75,
                                    'text-red-600' => $partida->porcentaje_ejercido < 40,
                                ])>{{ $partida->porcentaje_ejercido }}%</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm">
                                <a href="{{ route('presupuesto.partidas.edit', $partida) }}" class="text-brand hover:text-brand-dark mr-2">Editar</a>
                                <a href="{{ route('presupuesto.partidas.modificaciones', $partida) }}" class="text-indigo-600 hover:text-indigo-800 mr-2">Adecuaciones</a>
                                <button wire:click="eliminar({{ $partida->id }})" wire:confirm="¿Eliminar esta partida? Los avances financieros asociados también se eliminarán." class="text-red-600 hover:text-red-800">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No se encontraron partidas presupuestales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $partidas->links() }}
        </div>

        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
