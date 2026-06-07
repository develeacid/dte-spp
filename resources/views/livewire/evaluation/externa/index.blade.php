<div>
    <x-page.header title="Evaluaciones externas" subtitle="Evaluaciones de programas realizadas por instancias externas">
        @can('gestionar_evaluacion_externa')
            <x-ui.button.primary href="{{ url('/evaluacion/externas/crear') }}">Nueva evaluación</x-ui.button.primary>
        @endcan
    </x-page.header>

    <x-page.container fluid :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Evaluación', 'url' => '#'],
        ['label' => 'Evaluaciones externas'],
    ]">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4">
            <label>
                <span class="text-xs">Programa</span>
                <select wire:model.live="programaId" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($programas as $p)
                        <option value="{{ $p->id }}">{{ $p->clave }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs">Tipo</span>
                <select wire:model.live="tipoFiltro" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($tipos as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="text-xs">Ejercicio fiscal</span>
                <select wire:model.live="ejercicioFiscal" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($ejercicios as $e)
                        <option value="{{ $e }}">{{ $e }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Programa</th>
                        <th class="px-3 py-2">Ejercicio</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Evaluador externo</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">Fechas</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($evaluaciones as $evaluacion)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $evaluacion->programa->clave }}</div>
                                <div class="text-xs text-gray-500 max-w-xs truncate">{{ $evaluacion->programa->nombre }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $evaluacion->ejercicio_fiscal }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                    {{ $evaluacion->tipo->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 max-w-xs truncate">{{ $evaluacion->evaluador_externo }}</td>
                            <td class="px-3 py-2">
                                @if($evaluacion->estado === \App\Enums\EstadoEvaluacionExterna::CONCLUIDA)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">
                                        {{ $evaluacion->estado->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800">
                                        {{ $evaluacion->estado->label() }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-600">
                                {{ optional($evaluacion->fecha_inicio)->format('d/m/Y') ?? '—' }}
                                &ndash;
                                {{ optional($evaluacion->fecha_fin)->format('d/m/Y') ?? '—' }}
                            </td>
                            <td class="px-3 py-2 space-x-2">
                                <a class="text-indigo-600 text-xs" href="{{ url('/evaluacion/externas/'.$evaluacion->id) }}">Ver</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-4 text-center text-gray-500">Sin evaluaciones externas registradas.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $evaluaciones->links() }}</div>

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
