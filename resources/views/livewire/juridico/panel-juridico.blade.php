<div>
    <x-page.header title="Panel Jurídico" />

    <x-page.container>
        {{-- Filtro de ejercicio --}}
        <div class="mb-6 flex items-center gap-4">
            <div>
                <x-label for="filtroEjercicio" value="Ejercicio Fiscal" />
                <select id="filtroEjercicio" wire:model.live="filtroEjercicio" class="mt-1 w-40 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <x-label for="filtroEstado" value="Estado" />
                <select id="filtroEstado" wire:model.live="filtroEstado" class="mt-1 w-48 rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Todos</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="en_revision">En revisión</option>
                    <option value="validado">Validado</option>
                    <option value="rechazado">Rechazado</option>
                    <option value="sin_registro">Sin registro</option>
                </select>
            </div>
        </div>

        {{-- Alerta de documentos próximos a vencer --}}
        @if ($documentosProximosVencer > 0)
            <div class="mb-6 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-900/20">
                <p class="text-sm font-medium text-orange-800 dark:text-orange-300">
                    {{ $documentosProximosVencer }} documento(s) normativo(s) próximo(s) a vencer en los próximos {{ config('juridico.dias_alerta_vigencia', 30) }} días.
                </p>
            </div>
        @endif

        {{-- KPIs --}}
        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-4">
            <div class="rounded-lg border border-green-200 bg-white p-6 dark:border-green-800 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Validados</p>
                <p class="mt-1 text-2xl font-bold text-green-600">{{ $validados }}</p>
            </div>
            <div class="rounded-lg border border-yellow-200 bg-white p-6 dark:border-yellow-800 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Pendientes</p>
                <p class="mt-1 text-2xl font-bold text-yellow-600">{{ $pendientes }}</p>
            </div>
            <div class="rounded-lg border border-red-200 bg-white p-6 dark:border-red-800 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Rechazados</p>
                <p class="mt-1 text-2xl font-bold text-red-600">{{ $rechazados }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sin registro</p>
                <p class="mt-1 text-2xl font-bold text-gray-600">{{ $sinRegistro }}</p>
            </div>
        </div>

        {{-- Tabla de programas --}}
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Programa</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Facultad UR</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Mandato</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">ROP</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @forelse ($programas as $programa)
                        @php $v = $programa->validacionJuridica; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-4 py-3 text-sm">
                                <span class="font-medium text-gray-900 dark:text-white">{{ $programa->clave }}</span>
                                <span class="ml-1 text-gray-500 dark:text-gray-400">{{ Str::limit($programa->nombre, 40) }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($v)
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $v->estado->colorClass() }}">
                                        {{ $v->estado->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500">Sin registro</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($v && $v->tiene_facultad_ur)
                                    <span class="text-green-600">&#10003;</span>
                                @else
                                    <span class="text-red-400">&#10007;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($v && $v->tiene_mandato_gasto)
                                    <span class="text-green-600">&#10003;</span>
                                @else
                                    <span class="text-red-400">&#10007;</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                @if ($v && $v->tiene_rop === null)
                                    <span class="text-gray-400">N/A</span>
                                @elseif ($v && $v->tiene_rop)
                                    <span class="text-green-600">&#10003;</span>
                                @else
                                    <span class="text-red-400">&#10007;</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center">
                                <a href="{{ route('juridico.programa', $programa) }}" class="text-sm text-brand hover:text-brand-dark">Ver</a>
                                @can('validar_sustento_legal')
                                    <a href="{{ route('juridico.validacion', $programa) }}" class="ml-2 text-sm text-brand hover:text-brand-dark">Validar</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay programas presupuestarios para este ejercicio.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-page.container>
</div>
