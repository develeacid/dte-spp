<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Historial IAFF'],
    ]">
        <x-page.header title="Historial IAFF" :subtitle="$programa->nombre" />
        <p class="mb-4 text-sm text-gray-500">
            Informes de Avance Físico-Financiero persistidos por ejercicio y trimestre. Una vez firmados son inmutables (hash SHA-256).
        </p>

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Ejercicio</th>
                        <th class="px-4 py-3">Trimestre</th>
                        <th class="px-4 py-3">Generado</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Hash</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($iaffs as $iaff)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $iaff->ejercicio_fiscal }}</td>
                            <td class="px-4 py-3 text-gray-600">T{{ $iaff->trimestre }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $iaff->generado_en?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-3">
                                @if ($iaff->estaFirmado())
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">
                                        Firmado · {{ $iaff->firmado_en?->format('Y-m-d') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold text-yellow-800">Borrador</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-gray-400" title="{{ $iaff->hash_sha256 }}">{{ substr($iaff->hash_sha256, 0, 12) }}…</td>
                            <td class="px-4 py-3 text-right">
                                @can('firmar_iaff')
                                    @unless ($iaff->estaFirmado())
                                        <button
                                            wire:click="firmar({{ $iaff->id }})"
                                            wire:confirm="¿Firmar el IAFF {{ $iaff->ejercicio_fiscal }}-T{{ $iaff->trimestre }}? Quedará inmutable."
                                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500"
                                        >
                                            Firmar IAFF
                                        </button>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-400">
                                Sin IAFF generados aún. Se crean al exportar el Avance Trimestral (PDF/Excel).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-page.container>
</div>
