<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Presupuesto', 'url' => route('presupuesto.panel')],
        ['label' => 'Programa Operativo Anual'],
    ]">
        <x-page.header
            title="Programa Operativo Anual (POA)"
            subtitle="Plan operativo calendarizado físico + financiero por ejercicio" />

        <div class="mb-6 flex items-center gap-3">
            <label for="ejercicio" class="text-sm font-medium text-gray-700">Ejercicio fiscal</label>
            <select id="ejercicio" wire:model.live="ejercicio"
                class="rounded-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm text-sm">
                @forelse ($this->ejerciciosDisponibles as $anio)
                    <option value="{{ $anio }}">{{ $anio }}</option>
                @empty
                    <option value="{{ $ejercicio }}">{{ $ejercicio }}</option>
                @endforelse
            </select>
        </div>

        @forelse ($programas as $clave => $filas)
            <div class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">
                        <span class="font-mono text-gray-500">{{ $clave }}</span> · {{ $filas->first()->programa_nombre }}
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-white text-xs uppercase tracking-wider text-gray-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Tipo</th>
                                <th class="px-3 py-2 text-left">Concepto</th>
                                <th class="px-3 py-2 text-left">Unidad</th>
                                <th class="px-3 py-2 text-right">T1</th>
                                <th class="px-3 py-2 text-right">T2</th>
                                <th class="px-3 py-2 text-right">T3</th>
                                <th class="px-3 py-2 text-right">T4</th>
                                <th class="px-3 py-2 text-right">Total anual</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($filas as $fila)
                                <tr>
                                    <td class="px-3 py-2">
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                            'bg-emerald-100 text-emerald-800' => $fila->tipo === 'fisico',
                                            'bg-sky-100 text-sky-800' => $fila->tipo === 'financiero',
                                        ])>{{ $fila->tipo === 'fisico' ? 'Físico' : 'Financiero' }}</span>
                                    </td>
                                    <td class="px-3 py-2 text-gray-900">
                                        @if ($fila->concepto_clave)
                                            <span class="font-mono text-xs text-gray-500">{{ $fila->concepto_clave }}</span>
                                        @endif
                                        {{ $fila->concepto }}
                                    </td>
                                    <td class="px-3 py-2 text-gray-500">{{ $fila->unidad }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $fila->t1 !== null ? number_format($fila->t1, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $fila->t2 !== null ? number_format($fila->t2, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $fila->t3 !== null ? number_format($fila->t3, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-mono">{{ $fila->t4 !== null ? number_format($fila->t4, 2) : '—' }}</td>
                                    <td class="px-3 py-2 text-right font-mono font-semibold">{{ $fila->total !== null ? number_format($fila->total, 2) : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-10 text-center text-sm text-gray-500">
                No hay datos de POA para el ejercicio {{ $ejercicio }}.
            </div>
        @endforelse
    </x-page.container>
</div>
