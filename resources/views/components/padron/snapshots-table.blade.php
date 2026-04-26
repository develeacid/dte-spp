@props([
    'snapshots' => [],
    'snapshotIdSeleccionado' => null,
])

<div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Snapshots históricos</h3>
    </div>

    @if (empty($snapshots))
        <p class="px-4 py-6 text-center text-sm text-gray-500">
            Sin snapshots — genera el primero del trimestre.
        </p>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha de corte</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Periodo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Hash</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Beneficiarios</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($snapshots as $s)
                    <tr class="{{ ($s['id'] ?? null) === $snapshotIdSeleccionado ? 'bg-indigo-50' : '' }}">
                        <td class="px-4 py-2 text-sm text-gray-900">
                            {{ \Illuminate\Support\Str::of($s['cutoff_date'] ?? '')->limit(10, '') }}
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600">{{ $s['period'] ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs font-mono text-gray-500">
                            {{ \Illuminate\Support\Str::substr($s['snapshot_hash'] ?? '', 0, 16) }}…
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-900 text-right">
                            {{ number_format($s['valor_oficial'] ?? $s['row_count'] ?? 0) }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <button
                                type="button"
                                wire:click="seleccionarSnapshot({{ $s['id'] ?? 0 }})"
                                class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline">
                                {{ ($s['id'] ?? null) === $snapshotIdSeleccionado ? 'Seleccionado' : 'Seleccionar' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
