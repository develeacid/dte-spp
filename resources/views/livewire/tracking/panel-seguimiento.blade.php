<div>
    <x-page.header>
        <x-slot name="title">Seguimiento de indicadores</x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Filters bar --}}
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-3">
            <div>
                <label for="filtroPrograma" class="block text-sm font-medium text-gray-700">Programa</label>
                <select wire:model.live="filtroPrograma" id="filtroPrograma"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los programas</option>
                    @foreach($programas as $programa)
                        <option value="{{ $programa->id }}">{{ $programa->clave }} - {{ $programa->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtroEstado" class="block text-sm font-medium text-gray-700">Estado</label>
                <select wire:model.live="filtroEstado" id="filtroEstado"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    @foreach($estadosAvance as $ea)
                        <option value="{{ $ea->value }}">{{ $ea->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="filtroSemaforo" class="block text-sm font-medium text-gray-700">Semaforo</label>
                <select wire:model.live="filtroSemaforo" id="filtroSemaforo"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    <option value="">Todos</option>
                    <option value="verde">Verde</option>
                    <option value="amarillo">Amarillo</option>
                    <option value="rojo">Rojo</option>
                    <option value="gris">Sin dato</option>
                </select>
            </div>
        </div>

        {{-- Main table --}}
        @if($filas->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center shadow-sm">
                <p class="text-gray-500">No se encontraron indicadores con los filtros seleccionados.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Programa</th>
                            <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nivel</th>
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Indicador</th>
                            <th scope="col" class="hidden md:table-cell px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Meta</th>
                            <th scope="col" class="hidden md:table-cell px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Avance</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Semaforo</th>
                            <th scope="col" class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($filas as $fila)
                            <tr wire:click="toggleExpandir({{ $fila['indicador_id'] }})"
                                class="cursor-pointer hover:bg-gray-50 transition-colors">
                                <td class="hidden sm:table-cell whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    {{ $fila['programa_clave'] }}
                                </td>
                                <td class="hidden sm:table-cell whitespace-nowrap px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $fila['nivel_tipo']->colorClass() }}">
                                        {{ $fila['nivel_tipo']->label() }}
                                    </span>
                                </td>
                                <td class="max-w-xs px-4 py-3 text-sm text-gray-900">
                                    <span class="truncate block">{{ $fila['indicador_nombre'] }}</span>
                                    @if ($fila['indicador']->anexosTransversales->count() > 0)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach ($fila['indicador']->anexosTransversales as $anexo)
                                                <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700">
                                                    {{ $anexo->nombre }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="hidden md:table-cell whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700">
                                    {{ $fila['meta'] !== null ? number_format((float) $fila['meta'], 2) : '—' }}
                                </td>
                                <td class="hidden md:table-cell whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700">
                                    {{ $fila['resultado'] !== null ? number_format((float) $fila['resultado'], 2) : '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <span @class([
                                        'inline-block h-4 w-4 rounded-full',
                                        'bg-green-500' => $fila['semaforo'] === 'verde',
                                        'bg-yellow-400' => $fila['semaforo'] === 'amarillo',
                                        'bg-red-500' => $fila['semaforo'] === 'rojo',
                                        'bg-gray-300' => $fila['semaforo'] === 'gris',
                                    ]) title="{{ ucfirst($fila['semaforo']) }}"></span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    @if($fila['estado'])
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $fila['estado']->colorClass() }}">
                                            {{ $fila['estado']->label() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin avance</span>
                                    @endif
                                </td>
                            </tr>

                            {{-- Expanded detail --}}
                            @if(in_array($fila['indicador_id'], $expandido))
                                <tr>
                                    <td colspan="7" class="bg-gray-50 px-6 py-4">
                                        <div class="space-y-4">
                                            @if($fila['avance'])
                                                {{-- Variables --}}
                                                @if($fila['avance']->variables->count() > 0)
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700">Variables</h4>
                                                        <dl class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                                            @foreach($fila['avance']->variables as $variable)
                                                                <div class="rounded-md bg-white p-3 shadow-sm ring-1 ring-gray-200">
                                                                    <dt class="text-xs font-medium text-gray-500">
                                                                        {{ $variable->indicadorVariable?->nombre ?? $variable->indicadorVariable?->simbolo ?? 'Variable' }}
                                                                    </dt>
                                                                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                                                                        {{ number_format((float) $variable->valor, 4) }}
                                                                        @if($variable->valor_acumulado !== null)
                                                                            <span class="ml-1 text-xs font-normal text-gray-500">(Acum: {{ number_format((float) $variable->valor_acumulado, 4) }})</span>
                                                                        @endif
                                                                    </dd>
                                                                </div>
                                                            @endforeach
                                                        </dl>
                                                    </div>
                                                @endif

                                                {{-- Justification --}}
                                                @if($fila['avance']->justificacion_final || $fila['avance']->justificacion_ia)
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700">Justificacion</h4>
                                                        <p class="mt-1 rounded-md bg-white p-3 text-sm text-gray-600 shadow-sm ring-1 ring-gray-200">
                                                            {{ $fila['avance']->justificacion_final ?? $fila['avance']->justificacion_ia }}
                                                        </p>
                                                    </div>
                                                @endif

                                                {{-- Evidencias --}}
                                                @if($fila['avance']->evidencias->count() > 0)
                                                    <div>
                                                        <h4 class="text-sm font-medium text-gray-700">Evidencias</h4>
                                                        <ul class="mt-2 space-y-1">
                                                            @foreach($fila['avance']->evidencias as $evidencia)
                                                                <li class="flex items-center gap-2 text-sm">
                                                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                                                    </svg>
                                                                    <a href="{{ route('tracking.evidencia.download', $evidencia) }}"
                                                                       class="text-indigo-600 hover:text-indigo-500 hover:underline">
                                                                        {{ $evidencia->nombre_documento ?? $evidencia->nombre_archivo }}
                                                                    </a>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                @endif

                                                {{-- Timeline --}}
                                                @include('livewire.tracking.partials.timeline-observaciones', ['historial' => $fila['avance']->historial_observaciones ?? []])

                                                {{-- Link to flujo --}}
                                                <div class="mt-2">
                                                    <a href="{{ route('tracking.flujo', $fila['avance']) }}"
                                                       class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                                        Ver flujo completo
                                                    </a>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500">Este indicador aun no tiene avances registrados.</p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-page.container>
</div>
