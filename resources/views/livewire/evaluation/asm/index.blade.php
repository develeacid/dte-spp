<div>
    <x-page.header title="Aspectos Susceptibles de Mejora" subtitle="Seguimiento de compromisos derivados de evaluaciones">
        @can('gestionar_asm')
            <x-ui.button.primary href="{{ route('evaluation.asms.create') }}">Nuevo ASM</x-ui.button.primary>
        @endcan
        @can('exportar_reportes')
            @if(Route::has('evaluation.asms.export.xlsx'))
                <x-ui.button.secondary href="{{ route('evaluation.asms.export.xlsx', request()->query()) }}">
                    Exportar XLSX
                </x-ui.button.secondary>
            @endif
        @endcan
    </x-page.header>

    <x-page.container fluid :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Evaluación', 'url' => '#'],
        ['label' => 'ASM'],
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
                <span class="text-xs">Status</span>
                <select wire:model.live="statusFiltro" class="w-full rounded border-gray-300 text-sm">
                    <option value="">Todos</option>
                    @foreach($statuses as $s)
                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                    @endforeach
                </select>
            </label>
            <label class="md:col-span-2">
                <span class="text-xs">Buscar en descripción</span>
                <input wire:model.live.debounce.400ms="busqueda" type="search" class="w-full rounded border-gray-300 text-sm">
            </label>
        </div>

        <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Programa</th>
                        <th class="px-3 py-2">Descripción</th>
                        <th class="px-3 py-2">Responsable</th>
                        <th class="px-3 py-2">Fecha compromiso</th>
                        <th class="px-3 py-2">Semáforo</th>
                        <th class="px-3 py-2">% avance</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($asms as $asm)
                        <tr>
                            <td class="px-3 py-2">{{ $asm->programa->clave }}</td>
                            <td class="px-3 py-2 max-w-xs">
                                <div class="truncate">{{ $asm->descripcion_aspecto }}</div>
                                @if($asm->recomendacion_id)
                                    <span class="mt-1 inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                        Origen externo
                                    </span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $asm->responsable->name }}</td>
                            <td class="px-3 py-2">{{ $asm->fecha_compromiso->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">
                                @include('livewire.evaluation.asm._semaforo-badge', ['semaforo' => $asm->semaforo])
                            </td>
                            <td class="px-3 py-2">{{ $asm->porcentaje_avance }}%</td>
                            <td class="px-3 py-2">{{ $asm->status->label() }}</td>
                            <td class="px-3 py-2 space-x-2">
                                @if(Route::has('evaluation.asms.show'))
                                    <a class="text-indigo-600 text-xs" href="{{ route('evaluation.asms.show', $asm) }}">Ver</a>
                                @endif
                                @can('gestionar_asm')
                                    <a class="text-indigo-600 text-xs" href="{{ route('evaluation.asms.edit', $asm) }}">Editar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-3 py-4 text-center text-gray-500">Sin ASM registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $asms->links() }}</div>

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
