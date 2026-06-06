<div>
    <x-page.header>
        <x-slot name="title">Gestionar desbloqueos</x-slot>
    </x-page.header>

    <x-page.container fluid>
        @if (session()->has('message'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        @if ($desbloqueos->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-center shadow-sm">
                <p class="text-sm text-gray-500">No hay solicitudes de desbloqueo pendientes.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Operador</th>
                            <th class="hidden sm:table-cell px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Motivo</th>
                            <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($desbloqueos as $desbloqueo)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ \Illuminate\Support\Str::limit($desbloqueo->avance->indicador->nombre, 40) }}
                                </td>
                                <td class="hidden md:table-cell whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $desbloqueo->solicitante?->name ?? '—' }}
                                </td>
                                <td class="hidden sm:table-cell px-6 py-4 text-sm text-gray-500">
                                    {{ \Illuminate\Support\Str::limit($desbloqueo->motivo, 60) }}
                                </td>
                                <td class="hidden md:table-cell whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $desbloqueo->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            wire:click="aprobar({{ $desbloqueo->id }})"
                                            wire:confirm="Esto descongelara el avance y lo regresara a estado En Captura. Continuar?"
                                            class="inline-flex items-center rounded-md bg-green-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-green-500"
                                        >
                                            Aprobar
                                        </button>
                                        <div x-data="{ open: false }" class="relative">
                                            <button
                                                @click="open = !open"
                                                class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
                                            >
                                                Rechazar
                                            </button>
                                            <div
                                                x-show="open"
                                                @click.away="open = false"
                                                x-cloak
                                                class="absolute right-0 z-10 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-4 shadow-lg"
                                            >
                                                <label class="block text-sm font-medium text-gray-700">Resolucion</label>
                                                <textarea
                                                    wire:model="resolucionTexto"
                                                    rows="3"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                    placeholder="Motivo del rechazo..."
                                                ></textarea>
                                                @error('resolucionTexto')
                                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                                @enderror
                                                <div class="mt-2 flex justify-end">
                                                    <button
                                                        wire:click="rechazar({{ $desbloqueo->id }})"
                                                        class="inline-flex items-center rounded-md bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-red-500"
                                                    >
                                                        Confirmar rechazo
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Spacer para que el contenido final no quede tras el KPI bar fijo --}}
        <div class="h-28"></div>
    </x-page.container>

    <x-tracking.kpi-bar :stats="$kpis" />
</div>
