<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Presupuesto', 'url' => route('presupuesto.panel')],
        ['label' => 'Partidas', 'url' => route('presupuesto.partidas')],
        ['label' => 'Adecuaciones'],
    ]">
        <x-page.header
            title="Adecuaciones presupuestales"
            :subtitle="$partida->clave_partida . ' · ' . $partida->descripcion" />

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif

        {{-- Resumen aprobado → modificado --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-gray-400">Aprobado</p>
                <p class="font-mono text-lg font-semibold text-gray-900">${{ number_format((float) $partida->monto_aprobado, 2) }}</p>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-indigo-400">Modificado (vigente)</p>
                <p class="font-mono text-lg font-semibold text-indigo-900">${{ number_format((float) ($partida->monto_modificado ?? $partida->monto_aprobado), 2) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-gray-400">Adecuaciones</p>
                <p class="font-mono text-lg font-semibold text-gray-900">{{ $modificaciones->count() }}</p>
            </div>
        </div>

        {{-- Form de registro --}}
        <x-forms.section title="Registrar adecuación" description="Ampliación suma y reducción resta al presupuesto modificado.">
            <div class="col-span-6 sm:col-span-1">
                <x-label for="tipo" value="Tipo" />
                <select id="tipo" wire:model="tipo" class="mt-1 block w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                    @foreach ($tipos as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
                <x-input-error for="tipo" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-1">
                <x-label for="monto" value="Monto" />
                <x-input id="monto" type="number" step="0.01" min="0" class="mt-1 block w-full" wire:model="monto" />
                <x-input-error for="monto" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-1">
                <x-label for="fecha" value="Fecha" />
                <x-input id="fecha" type="date" class="mt-1 block w-full" wire:model="fecha" />
                <x-input-error for="fecha" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-1">
                <x-label for="oficio" value="Oficio (folio)" />
                <x-input id="oficio" type="text" class="mt-1 block w-full" wire:model="oficio" />
                <x-input-error for="oficio" class="mt-1" />
            </div>
            <div class="col-span-6 sm:col-span-2">
                <x-label for="justificacion" value="Justificación" />
                <x-input id="justificacion" type="text" class="mt-1 block w-full" wire:model="justificacion" />
                <x-input-error for="justificacion" class="mt-1" />
            </div>
            <div class="col-span-6 flex justify-end">
                <x-button type="button" wire:click="registrar">Registrar adecuación</x-button>
            </div>
        </x-forms.section>

        {{-- Historial --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                <p class="text-sm font-semibold text-gray-900">Historial de adecuaciones</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Fecha</th>
                            <th class="px-3 py-2 text-left">Tipo</th>
                            <th class="px-3 py-2 text-right">Monto</th>
                            <th class="px-3 py-2 text-left">Oficio</th>
                            <th class="px-3 py-2 text-left">Justificación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($modificaciones as $m)
                            <tr>
                                <td class="px-3 py-2 text-gray-600">{{ $m->fecha?->format('Y-m-d') }}</td>
                                <td class="px-3 py-2">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-emerald-100 text-emerald-800' => $m->tipo === \App\Enums\TipoModificacionPresupuestal::AMPLIACION,
                                        'bg-rose-100 text-rose-800' => $m->tipo === \App\Enums\TipoModificacionPresupuestal::REDUCCION,
                                    ])>{{ $m->tipo->label() }}</span>
                                </td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format((float) $m->monto, 2) }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $m->oficio ?? '—' }}</td>
                                <td class="px-3 py-2 text-gray-500">{{ $m->justificacion ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Sin adecuaciones registradas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-page.container>
</div>
