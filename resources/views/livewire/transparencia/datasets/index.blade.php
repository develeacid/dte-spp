<div>
<x-page.container title="Datos Abiertos" subtitle="Gestión y aprobación de datasets para publicación" fluid>
    <div class="bg-white shadow-sm rounded-lg p-4">
        <div class="mb-4 flex flex-wrap gap-2">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Buscar clave o nombre..." class="rounded border-gray-300 px-3 py-2" />

            <select wire:model.live="filtroStatus" class="rounded border-gray-300 px-3 py-2">
                <option value="">Todos los status</option>
                <option value="borrador">Borrador</option>
                <option value="revision">Revisión</option>
                <option value="aprobado">Aprobado</option>
                <option value="publicado">Publicado</option>
                <option value="retirado">Retirado</option>
            </select>

            <select wire:model.live="filtroSistema" class="rounded border-gray-300 px-3 py-2">
                <option value="">Todos los sistemas</option>
                <option value="spp">SPP</option>
                <option value="geobase">GeoBase</option>
            </select>

            <select wire:model.live="filtroTipo" class="rounded border-gray-300 px-3 py-2">
                <option value="">Plantillas y entregas</option>
                <option value="plantilla">Solo plantillas</option>
                <option value="entrega">Solo entregas</option>
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Clave</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Sistema</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Periodo</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Autor</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($datasets as $ds)
                        <tr>
                            <td class="px-4 py-2 text-sm font-mono">{{ $ds->dataset_clave }}</td>
                            <td class="px-4 py-2 text-sm">{{ $ds->nombre }}</td>
                            <td class="px-4 py-2 text-sm">{{ $ds->sistema_origen }}</td>
                            <td class="px-4 py-2 text-sm">{{ $ds->periodo ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm">
                                <span class="inline-block rounded px-2 py-1 text-xs {{ match($ds->status->value) {
                                    'borrador' => 'bg-gray-100 text-gray-700',
                                    'revision' => 'bg-yellow-100 text-yellow-800',
                                    'aprobado' => 'bg-blue-100 text-blue-800',
                                    'publicado' => 'bg-green-100 text-green-800',
                                    'retirado' => 'bg-red-100 text-red-800',
                                    default => 'bg-gray-100',
                                } }}">{{ $ds->status->value }}</span>
                            </td>
                            <td class="px-4 py-2 text-sm">{{ $ds->creadoPor?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-sm space-x-2">
                                <a href="{{ route('transparencia.datos-abiertos.show', $ds) }}" class="text-blue-600 hover:underline">Ver</a>
                                @if($ds->periodo === null)
                                    @can('crearEntrega', $ds)
                                        <a href="{{ route('transparencia.datos-abiertos.crear-entrega', $ds) }}" class="text-green-600 hover:underline">Crear entrega</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">No hay datasets que coincidan con los filtros.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $datasets->links() }}</div>
    </div>

    <div class="h-28"></div>
</x-page.container>

<x-tracking.kpi-bar :stats="$kpis" />
</div>
