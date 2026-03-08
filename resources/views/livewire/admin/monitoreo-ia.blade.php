<div>
    <x-page.header>
        <x-slot name="title">Monitoreo de IA</x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Filtros de periodo --}}
        <div class="mb-6 flex items-center gap-4">
            <label class="text-sm font-medium text-gray-700">Periodo:</label>
            <select wire:model.live="periodo" class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="day">Hoy</option>
                <option value="week">Ultima semana</option>
                <option value="month">Mes actual</option>
            </select>
            <span class="text-sm text-gray-500">{{ $fechaDesde }} — {{ $fechaHasta }}</span>
        </div>

        {{-- Alertas --}}
        @if(count($alertas) > 0)
            <div class="mb-6 space-y-2">
                @foreach($alertas as $alerta)
                    <div class="rounded-md border border-red-300 bg-red-50 p-4">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                            </svg>
                            <span class="ml-3 text-sm font-medium text-red-800">
                                Presupuesto {{ ucfirst($alerta['scope']) }}{{ $alerta['scope_id'] ? ' #'.$alerta['scope_id'] : '' }}:
                                ${{ number_format($alerta['spent_usd'], 4) }} / ${{ number_format($alerta['budget_usd'], 2) }} USD
                                ({{ $alerta['percent'] }}%)
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <dt class="text-sm font-medium text-gray-500">Total llamadas</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($metricas['total_calls']) }}</dd>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <dt class="text-sm font-medium text-gray-500">Total tokens</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($metricas['total_tokens']) }}</dd>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <dt class="text-sm font-medium text-gray-500">Costo estimado</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">${{ number_format($metricas['total_cost'], 4) }}</dd>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <dt class="text-sm font-medium text-gray-500">Tiempo promedio</dt>
                <dd class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($metricas['avg_duration']) }} ms</dd>
            </div>
            <div class="rounded-lg border bg-white p-4 shadow-sm">
                <dt class="text-sm font-medium text-gray-500">Tasa de error</dt>
                <dd class="mt-1 text-2xl font-semibold {{ $metricas['error_rate'] > 10 ? 'text-red-600' : 'text-gray-900' }}">{{ $metricas['error_rate'] }}%</dd>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            {{-- Uso por tipo --}}
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Uso por tipo de operacion</h3>
                @if(count($usoPorTipo) > 0)
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Metodo</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Llamadas</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Tokens</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Costo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($usoPorTipo as $tipo)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $tipo['method'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($tipo['calls']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($tipo['tokens']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">${{ number_format((float)$tipo['cost'], 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-500">Sin datos para el periodo seleccionado.</p>
                @endif
            </div>

            {{-- Uso por usuario --}}
            <div class="rounded-lg border bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Top 10 usuarios</h3>
                @if(count($usoPorUsuario) > 0)
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-500">Usuario</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Llamadas</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Tokens</th>
                                <th class="px-3 py-2 text-right font-medium text-gray-500">Costo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($usoPorUsuario as $user)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-gray-900">{{ $user['name'] }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($user['calls']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">{{ number_format($user['tokens']) }}</td>
                                    <td class="px-3 py-2 text-right text-gray-600">${{ number_format((float)$user['cost'], 4) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-sm text-gray-500">Sin datos para el periodo seleccionado.</p>
                @endif
            </div>
        </div>

        {{-- Uso por UR --}}
        <div class="mt-8 rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Uso por Unidad Responsable</h3>
            @if(count($usoPorUr) > 0)
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Equipo</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Llamadas</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Tokens</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($usoPorUr as $ur)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $ur['team_name'] }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ number_format($ur['calls']) }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ number_format($ur['tokens']) }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">${{ number_format((float)$ur['cost'], 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500">Sin datos para el periodo seleccionado.</p>
            @endif
        </div>

        {{-- Tendencia --}}
        <div class="mt-8 rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Tendencia diaria</h3>
            @if(count($tendencia) > 0)
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-500">Fecha</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Llamadas</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Tokens</th>
                            <th class="px-3 py-2 text-right font-medium text-gray-500">Costo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($tendencia as $day)
                            <tr>
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $day['fecha'] }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ number_format($day['calls']) }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">{{ number_format($day['tokens']) }}</td>
                                <td class="px-3 py-2 text-right text-gray-600">${{ number_format((float)$day['cost'], 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500">Sin datos para el periodo seleccionado.</p>
            @endif
        </div>

        {{-- Presupuestos --}}
        <div class="mt-8 rounded-lg border bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Presupuestos del mes</h3>
            @if(count($presupuestos) > 0)
                <div class="space-y-4">
                    @foreach($presupuestos as $budget)
                        <div class="rounded-lg border p-4 {{ $budget['over_threshold'] ? 'border-red-300 bg-red-50' : 'border-gray-200' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ ucfirst($budget['scope']) }}{{ $budget['scope_id'] ? ' #'.$budget['scope_id'] : '' }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    ${{ number_format($budget['spent_usd'], 4) }} / ${{ number_format($budget['budget_usd'], 2) }} USD
                                </span>
                            </div>
                            <div class="mt-2 w-full rounded-full bg-gray-200">
                                <div class="h-2.5 rounded-full {{ $budget['over_threshold'] ? 'bg-red-500' : 'bg-indigo-500' }}"
                                     style="width: {{ min($budget['percent'], 100) }}%"></div>
                            </div>
                            <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                                <span>{{ $budget['percent'] }}% utilizado</span>
                                <span>Umbral: {{ $budget['threshold'] }}%</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No hay presupuestos configurados para este mes.</p>
            @endif
        </div>
    </x-page.container>
</div>
