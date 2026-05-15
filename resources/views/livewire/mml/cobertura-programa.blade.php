<x-page.container>
    <x-page.header
        title="Cobertura geográfica"
        subtitle="{{ $programa->clave }} — {{ $programa->nombre }}"
    />

    <div class="space-y-6">
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
