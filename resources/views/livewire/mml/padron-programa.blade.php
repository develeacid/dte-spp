<x-page.container :title="'Padrón — '.$programa->clave" subtitle="Beneficiarios atendidos por componente (datos administrados por GeoBase)">
    @if ($programa->padron_geobase_activo && $componenteSeleccionado)
        <x-slot name="actions">
            @can('exportar_reportes')
                <x-ui.button.secondary :href="route('evaluation.anexo-11', $programa)">
                    Exportar Anexo 11
                </x-ui.button.secondary>
            @endcan
            @can('generar_snapshot_padron')
                <x-ui.button.primary wire:click="generarSnapshot" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generarSnapshot">Generar snapshot del trimestre</span>
                    <span wire:loading wire:target="generarSnapshot">Generando…</span>
                </x-ui.button.primary>
            @endcan
        </x-slot>
    @endif

    @if (! $programa->padron_geobase_activo)
        @if (session('success'))
            <div class="rounded-md bg-green-50 border border-green-200 p-3 text-sm text-green-800 mb-4">
                {{ session('success') }}
            </div>
        @endif
        @if ($errorMessage)
            <div class="rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-800 mb-4">
                {{ $errorMessage }}
            </div>
        @endif
        <x-ui.empty-state
            title="Sin vinculación a GeoBase"
            description="Este programa aún no está vinculado a GeoBase. Una vez activado, los Componentes de la MIR se replicarán a GeoBase y podrás generar snapshots del padrón.">
            @can('generar_snapshot_padron')
                <x-ui.button.primary wire:click="activarPadron" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="activarPadron">Activar padrón en GeoBase</span>
                    <span wire:loading wire:target="activarPadron">Activando…</span>
                </x-ui.button.primary>
            @else
                <p class="text-xs text-gray-500">Solicita a un planeador u operador que active el padrón.</p>
            @endcan
        </x-ui.empty-state>
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

        @php
            $snapshotActual = collect($snapshotsHistoricos)->firstWhere('id', $snapshotIdSeleccionado);
        @endphp

        <x-padron.fuente-banner
            :modoFuente="$modoFuente"
            :modoVivoDisponible="$modoVivoDisponible"
            :snapshot="$snapshotActual" />

        <div>
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Cobertura del Componente</h3>
            <x-padron.kpi-cards :kpis="$kpis" />
        </div>

        <x-padron.snapshots-table
            :snapshots="$snapshotsHistoricos"
            :snapshotIdSeleccionado="$snapshotIdSeleccionado" />
    @endif
</x-page.container>
