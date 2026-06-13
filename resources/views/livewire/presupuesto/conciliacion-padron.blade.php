<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Presupuesto', 'url' => route('presupuesto.panel')],
        ['label' => 'Conciliación físico-financiera'],
    ]">
        <x-page.header
            title="Conciliación físico-financiera"
            :subtitle="$programa->clave . ' · ' . $programa->nombre . ' · Ejercicio ' . $ejercicio" />

        <p class="mb-6 text-sm text-gray-500">
            Cruce entre lo <b>pagado</b> por tesorería (registro local) y lo <b>efectivamente entregado</b>
            a beneficiarios según el padrón (geobase, en vivo).
        </p>

        @if ($error)
            <div class="mb-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-700">{{ $error }}</div>
        @endif

        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-gray-400">Pagado (tesorería)</p>
                <p class="font-mono text-lg font-semibold text-gray-900">${{ number_format($pagado, 2) }}</p>
            </div>
            <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                <p class="text-xs uppercase tracking-wider text-indigo-400">Entregado (padrón)</p>
                <p class="font-mono text-lg font-semibold text-indigo-900">
                    {{ $entregado !== null ? '$' . number_format($entregado, 2) : '—' }}
                </p>
            </div>
            <div @class([
                'rounded-xl border p-4 shadow-sm',
                'border-gray-200 bg-white' => $diferencia === null,
                'border-emerald-200 bg-emerald-50' => $diferencia !== null && abs($diferencia) < 0.01,
                'border-rose-200 bg-rose-50' => $diferencia !== null && abs($diferencia) >= 0.01,
            ])>
                <p class="text-xs uppercase tracking-wider text-gray-400">Diferencia (pagado − entregado)</p>
                <p class="font-mono text-lg font-semibold text-gray-900">
                    {{ $diferencia !== null ? '$' . number_format($diferencia, 2) : '—' }}
                </p>
            </div>
        </div>

        @if (! empty($porComponente))
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50 px-4 py-3">
                    <p class="text-sm font-semibold text-gray-900">Entregado por componente (padrón)</p>
                </div>
                <table class="min-w-full text-sm">
                    <thead class="text-xs uppercase tracking-wider text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Componente (spp_mir_nivel_id)</th>
                            <th class="px-3 py-2 text-right">Monto entregado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($porComponente as $c)
                            <tr>
                                <td class="px-3 py-2 font-mono text-gray-700">{{ $c['spp_mir_nivel_id'] }}</td>
                                <td class="px-3 py-2 text-right font-mono">${{ number_format((float) $c['monto_entregado'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page.container>
</div>
