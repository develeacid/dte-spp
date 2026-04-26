<x-page.container :title="'Padrón — '.$programa->clave" subtitle="Beneficiarios atendidos por componente (datos administrados por GeoBase)">
    @if ($programa->geobase_program_id && $componenteSeleccionado)
        <x-slot name="actions">
            @can('generar_snapshot_padron')
                <x-ui.button.primary wire:click="generarSnapshot" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generarSnapshot">Generar snapshot del trimestre</span>
                    <span wire:loading wire:target="generarSnapshot">Generando…</span>
                </x-ui.button.primary>
            @endcan
        </x-slot>
    @endif

    @if (! $programa->geobase_program_id)
        <x-ui.empty-state
            title="Sin vinculación a GeoBase"
            description="Este programa aún no está vinculado a GeoBase. Solicita al admin la vinculación." />
    @elseif (empty($componentesDelPrograma))
        <x-ui.empty-state
            title="Sin Componentes"
            description="Este programa aún no tiene Componentes definidos en su MIR." />
    @else
        @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="rounded-md bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">
                {{ session('info') }}
            </div>
        @endif

        @if ($errorMessage)
            <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800">
                {{ $errorMessage }}
            </div>
        @endif

        <nav class="flex flex-wrap gap-2 border-b border-gray-200 pb-2" aria-label="Componentes">
            @foreach ($componentesDelPrograma as $id => $etiqueta)
                <button
                    type="button"
                    wire:click="seleccionarComponente({{ $id }})"
                    class="px-3 py-1.5 text-sm rounded-md border {{ $componenteSeleccionado === $id ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}">
                    {{ $etiqueta }}
                </button>
            @endforeach
        </nav>

        <div class="rounded-lg border border-gray-200 bg-white p-4 space-y-3">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Fuente de datos</h3>
                <span @class([
                    'inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium',
                    'bg-blue-100 text-blue-800' => $modoFuente === 'snapshot',
                    'bg-yellow-100 text-yellow-800' => $modoFuente === 'vivo',
                ])>
                    {{ $modoFuente === 'snapshot' ? '🔒 Snapshot' : '⚡ Vivo' }}
                </span>
            </div>
            @unless ($modoVivoDisponible)
                <p class="text-xs text-gray-500">Modo "vivo" disponible próximamente — por ahora se muestran los snapshots históricos.</p>
            @endunless
        </div>

        <div class="rounded-lg border border-gray-200 bg-white p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Cobertura del Componente</h3>
            <p class="text-2xl font-bold text-gray-900">{{ number_format($kpis['total'] ?? 0) }}</p>
            <p class="text-xs text-gray-500">Total atendidos (snapshot)</p>
        </div>

        <div class="rounded-lg border border-gray-200 bg-white overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-900">Snapshots históricos</h3>
            </div>
            @if (empty($snapshotsHistoricos))
                <p class="px-4 py-6 text-center text-sm text-gray-500">
                    Sin snapshots — genera el primero del trimestre.
                </p>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach ($snapshotsHistoricos as $s)
                        <li class="px-4 py-3 flex items-center justify-between text-sm {{ ($s['id'] ?? null) === $snapshotIdSeleccionado ? 'bg-indigo-50' : '' }}">
                            <span>{{ \Illuminate\Support\Str::of($s['cutoff_date'] ?? '')->limit(10) }}</span>
                            <span class="font-mono text-xs text-gray-500">{{ \Illuminate\Support\Str::substr($s['snapshot_hash'] ?? '', 0, 16) }}…</span>
                            <button type="button" wire:click="seleccionarSnapshot({{ $s['id'] }})" class="text-indigo-600 hover:underline">
                                Seleccionar
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</x-page.container>
