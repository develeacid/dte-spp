<x-page.container>
    <x-page.header
        title="Cobertura geográfica"
        subtitle="{{ $programa->clave }} — {{ $programa->nombre }}"
    />

    <div class="space-y-6">
        <div class="bg-white shadow rounded p-4">
            <h3 class="text-sm font-medium text-gray-700 mb-3">Supuestos del MIR</h3>
            @if (count($supuestos) === 0 || collect($supuestos)->every(fn ($n) => empty($n['supuestos'])))
                <p class="text-sm text-gray-500 italic">Sin Supuestos definidos en la MIR del programa.</p>
            @else
                <dl class="space-y-3">
                    @foreach ($supuestos as $nivel)
                        @continue (empty($nivel['supuestos']))
                        <div>
                            <dt class="text-xs font-semibold uppercase text-gray-500">
                                {{ ucfirst($nivel['tipo']) }} — {{ \Illuminate\Support\Str::limit($nivel['narrativa'], 80) }}
                            </dt>
                            <dd class="mt-1 text-sm text-gray-800">{{ $nivel['supuestos'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        @if ($estado === 'inactivo')
            <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
                <p class="text-sm text-amber-800">
                    El padrón de este programa no está activo en GeoBase.
                    Activarlo en el tab Padrón para ver cobertura.
                </p>
            </div>
        @endif

        @if ($estado === 'vacio')
            <div class="rounded-md bg-blue-50 border border-blue-200 p-4">
                <p class="text-sm text-blue-800">Aún no hay beneficiarios inscritos en este programa.</p>
            </div>
        @endif

        @if ($estado === 'no_registrado')
            <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
                <p class="text-sm text-amber-800">
                    El programa no está registrado en GeoBase. Ejecutar
                    <code class="text-xs bg-amber-100 px-1 rounded">php artisan geobase:register-program {{ $programa->clave }}</code>
                    para sincronizar.
                </p>
            </div>
        @endif

        @if ($estado === 'error')
            <div class="rounded-md bg-red-50 border border-red-200 p-4">
                <p class="text-sm text-red-800">{{ $errorMessage }}</p>
            </div>
        @endif

        @if ($estado === 'ok' || $estado === 'vacio' || $estado === 'no_registrado' || $estado === 'error')
            <div class="bg-white shadow rounded p-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-medium text-gray-600 mr-2">Periodo:</span>
                    <button
                        type="button"
                        wire:click="seleccionarPeriodo(null)"
                        class="px-3 py-1 text-xs rounded-full border {{ $periodoSeleccionado === null ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                    >Todo el periodo</button>
                    @foreach ($periodosDisponibles as $p)
                        @php
                            [$year, $q] = explode('-Q', $p);
                            $label = 'Q'.$q.' '.$year;
                        @endphp
                        <button
                            type="button"
                            wire:click="seleccionarPeriodo('{{ $p }}')"
                            class="px-3 py-1 text-xs rounded-full border {{ $periodoSeleccionado === $p ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50' }}"
                        >{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($estado === 'ok' || $estado === 'vacio')
            @if ($consultadoAt)
                <p class="text-xs text-gray-500">
                    Consultado: {{ $consultadoAt }}
                    @if ($periodoSeleccionado !== null)
                        @php [$y, $q] = explode('-Q', $periodoSeleccionado); @endphp
                        — filtrado por Q{{ $q }} {{ $y }}
                    @endif
                    (datos en vivo desde GeoBase)
                </p>
            @endif

            @php
                $hasAlertas = $alertaTrimestre !== null || $alertaMeta !== null || count($municipiosConDrop) > 0;
            @endphp
            <div class="bg-white shadow rounded p-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Alertas de cobertura</h3>
                @if (! $hasAlertas)
                    <p class="text-sm text-emerald-700">Sin alertas activas en este periodo.</p>
                @else
                    <div class="space-y-2">
                        @if ($alertaTrimestre)
                            @php $cls = $alertaTrimestre['nivel'] === 'rojo' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800'; @endphp
                            <div class="rounded border {{ $cls }} px-3 py-2 text-sm">
                                Inscripciones cayó {{ $alertaTrimestre['drop_pct'] }}% vs trimestre anterior.
                            </div>
                        @endif
                        @if ($alertaMeta)
                            @php $cls = $alertaMeta['nivel'] === 'rojo' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800'; @endphp
                            <div class="rounded border {{ $cls }} px-3 py-2 text-sm">
                                Cobertura actual: {{ $alertaMeta['pct'] }}% de la meta de población objetivo.
                            </div>
                        @endif
                        @if (count($municipiosConDrop) > 0)
                            <div class="rounded border bg-amber-50 border-amber-200 text-amber-800 px-3 py-2 text-sm">
                                <p class="font-medium mb-1">Municipios con caída &gt;30% vs trimestre anterior:</p>
                                <ul class="list-disc list-inside">
                                    @foreach ($municipiosConDrop as $mun)
                                        <li>{{ $mun['municipality'] }}: −{{ $mun['drop_pct'] }}%</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif

        @if ($estado === 'ok')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="bg-white shadow rounded p-4">
                    <p class="text-sm text-gray-500">Total beneficiarios</p>
                    <p class="text-3xl font-semibold text-emerald-700">{{ $coverage['total_beneficiaries'] ?? 0 }}</p>
                </div>
                <div class="bg-white shadow rounded p-4">
                    <p class="text-sm text-gray-500">Total inscripciones</p>
                    <p class="text-3xl font-semibold text-blue-700">{{ $coverage['total_enrollments'] ?? 0 }}</p>
                </div>
            </div>

            @if (! empty($coverage['by_status']))
                <div class="bg-white shadow rounded p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-3">Por estatus</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($coverage['by_status'] as $status => $count)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                {{ $status }}: <strong class="ml-1">{{ $count }}</strong>
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($coverage['by_municipality']))
                <div class="bg-white shadow rounded overflow-hidden">
                    <h3 class="text-sm font-medium text-gray-700 px-4 py-3 border-b">Por municipio</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Municipio</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Inscripciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($coverage['by_municipality'] as $row)
                                <tr>
                                    <td class="px-4 py-2 text-sm text-gray-900">{{ $row['municipality'] ?? '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-900 text-right">{{ $row['count'] ?? 0 }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</x-page.container>
