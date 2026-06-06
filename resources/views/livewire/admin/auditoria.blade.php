<div>
    <x-page.container title="Auditoría del sistema" fluid>
        {{-- Filtros --}}
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="subjectType" class="block text-sm font-medium text-gray-700">Tipo de modelo</label>
                <select wire:model.live="subjectType" id="subjectType"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($subjectTypes as $fqcn => $label)
                        <option value="{{ $fqcn }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="causerId" class="block text-sm font-medium text-gray-700">Usuario</label>
                <select wire:model.live="causerId" id="causerId"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($usuarios as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="evento" class="block text-sm font-medium text-gray-700">Evento</label>
                <select wire:model.live="evento" id="evento"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="created">Creación</option>
                    <option value="updated">Actualización</option>
                    <option value="deleted">Eliminación</option>
                </select>
            </div>

            <div>
                <label for="fechaDesde" class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" wire:model.live="fechaDesde" id="fechaDesde"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label for="fechaHasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" wire:model.live="fechaHasta" id="fechaHasta"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mb-4 flex justify-end">
            <button wire:click="limpiarFiltros" class="text-sm text-indigo-600 hover:text-indigo-800">
                Limpiar filtros
            </button>
        </div>

        {{-- Tabla de actividad --}}
        <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Evento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Modelo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cambios</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $activity->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                {{ $activity->causer?->name ?? 'Sistema' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @switch($activity->event)
                                    @case('created')
                                        <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Creación</span>
                                        @break
                                    @case('updated')
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800">Actualización</span>
                                        @break
                                    @case('deleted')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Eliminación</span>
                                        @break
                                    @default
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">{{ $activity->event }}</span>
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $this->getSubjectLabel($activity->subject_type) }}
                                <span class="text-gray-400">#{{ $activity->subject_id }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $activity->description }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                @if($activity->properties->has('attributes'))
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 hover:text-indigo-800">
                                            {{ count($activity->properties['attributes']) }} campo(s)
                                        </summary>
                                        <div class="mt-2 max-w-md space-y-1">
                                            @foreach($activity->properties['attributes'] as $key => $value)
                                                <div class="text-xs">
                                                    <span class="font-medium text-gray-700">{{ $key }}:</span>
                                                    <span class="text-gray-500">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                    @if($activity->properties->has('old') && isset($activity->properties['old'][$key]))
                                                        <span class="text-gray-400">(antes: {{ is_array($activity->properties['old'][$key]) ? json_encode($activity->properties['old'][$key]) : $activity->properties['old'][$key] }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-400">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                No se encontraron registros de auditoría para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-4">
            {{ $activities->links() }}
        </div>

        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
