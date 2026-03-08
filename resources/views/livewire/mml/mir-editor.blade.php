<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 5 — Matriz de Indicadores para Resultados"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 5: MIR'],
    ]">
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-16 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Nivel</th>
                        <th class="w-1/4 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                            <x-ui.help-label glossary="resumen_narrativo" class="text-xs font-medium uppercase text-gray-500">
                                Resumen Narrativo
                            </x-ui.help-label>
                        </th>
                        <th class="w-1/3 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                            <x-ui.help-label glossary="indicador" class="text-xs font-medium uppercase text-gray-500">
                                Indicadores
                            </x-ui.help-label>
                        </th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                            <x-ui.help-label glossary="medios_verificacion" class="text-xs font-medium uppercase text-gray-500">
                                Medios de Verificación
                            </x-ui.help-label>
                        </th>
                        <th class="w-1/6 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
                            <x-ui.help-label glossary="supuestos" class="text-xs font-medium uppercase text-gray-500">
                                Supuestos
                            </x-ui.help-label>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    {{-- FIN --}}
                    @if ($fin)
                        @include('livewire.mml.partials.mir-nivel-row', [
                            'nivel' => $fin,
                            'reglas' => $reglasMap['fin'],
                            'colorClass' => 'bg-blue-50 border-l-4 border-l-blue-400',
                        ])
                    @endif

                    {{-- PROPOSITO --}}
                    @if ($proposito)
                        @include('livewire.mml.partials.mir-nivel-row', [
                            'nivel' => $proposito,
                            'reglas' => $reglasMap['proposito'],
                            'colorClass' => 'bg-emerald-50 border-l-4 border-l-emerald-400',
                        ])
                    @endif

                    {{-- COMPONENTES --}}
                    @foreach ($componentes as $componente)
                        @include('livewire.mml.partials.mir-nivel-row', [
                            'nivel' => $componente,
                            'reglas' => $reglasMap['componente'],
                            'colorClass' => 'bg-amber-50 border-l-4 border-l-amber-400',
                            'deletable' => true,
                        ])

                        {{-- ACTIVIDADES del componente --}}
                        @foreach ($componente->actividades as $actividad)
                            @include('livewire.mml.partials.mir-nivel-row', [
                                'nivel' => $actividad,
                                'reglas' => $reglasMap['actividad'],
                                'colorClass' => 'bg-violet-50 border-l-4 border-l-violet-400 pl-6',
                                'deletable' => true,
                            ])
                        @endforeach

                        {{-- Botón agregar actividad --}}
                        <tr class="bg-violet-25">
                            <td colspan="5" class="px-3 py-2 pl-10">
                                <button
                                    wire:click="agregarActividad({{ $componente->id }})"
                                    class="inline-flex items-center gap-1 text-xs text-violet-600 hover:text-violet-800"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Agregar Actividad
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    {{-- Botón agregar componente --}}
                    <tr class="bg-gray-50">
                        <td colspan="5" class="px-3 py-3">
                            <button
                                wire:click="agregarComponente"
                                class="inline-flex items-center gap-1 rounded-md bg-amber-100 px-3 py-1.5 text-sm font-medium text-amber-800 hover:bg-amber-200"
                            >
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                Agregar Componente
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Snapshots --}}
        <div class="mt-6 flex items-center gap-4">
            <div class="flex items-center gap-2">
                <input
                    type="text"
                    wire:model="snapshotEtiqueta"
                    class="rounded-md border-gray-300 text-sm shadow-sm"
                    placeholder="Etiqueta del snapshot..."
                />
                <button
                    wire:click="crearSnapshot"
                    wire:loading.attr="disabled"
                    wire:target="crearSnapshot"
                    class="inline-flex items-center gap-1 rounded-md bg-gray-600 px-3 py-2 text-sm font-medium text-white hover:bg-gray-700"
                >
                    <span wire:loading.remove wire:target="crearSnapshot">Crear snapshot</span>
                    <span wire:loading wire:target="crearSnapshot">Guardando...</span>
                </button>
            </div>
            <button
                wire:click="toggleVersiones"
                class="text-sm text-gray-600 hover:text-gray-800 underline"
            >
                {{ $mostrarVersiones ? 'Ocultar versiones' : 'Ver versiones' }}
            </button>
        </div>

        @if ($mostrarVersiones && $versiones->count() > 0)
            <div class="mt-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">Versiones guardadas</h3>
                <div class="space-y-2">
                    @foreach ($versiones as $version)
                        <div class="flex items-center justify-between rounded bg-white p-2 border border-gray-100">
                            <div>
                                <span class="text-sm font-medium">{{ $version->etiqueta }}</span>
                                <span class="ml-2 text-xs text-gray-500">
                                    {{ $version->created_at->format('d/m/Y H:i') }}
                                    @if ($version->creador)
                                        — {{ $version->creador->name }}
                                    @endif
                                </span>
                            </div>
                            <button
                                wire:click="restaurarVersion({{ $version->id }})"
                                wire:confirm="¿Restaurar esta versión? Se reemplazará la MIR actual."
                                class="rounded bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800 hover:bg-amber-200"
                            >
                                Restaurar
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif ($mostrarVersiones)
            <p class="mt-3 text-sm text-gray-500">No hay versiones guardadas.</p>
        @endif

        {{-- Validación Lógica --}}
        <div class="mt-6">
            <button
                wire:click="validarMirCompleta"
                wire:loading.attr="disabled"
                wire:target="validarMirCompleta"
                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
            >
                <span wire:loading.remove wire:target="validarMirCompleta">Validar MIR completa</span>
                <span wire:loading wire:target="validarMirCompleta">Validando...</span>
            </button>

            @if ($validacionLogicaEjecutada)
                <div class="mt-4 rounded-lg border {{ count($hallazgosLogica) > 0 ? 'border-yellow-300 bg-yellow-50' : 'border-green-300 bg-green-50' }} p-4">
                    <h3 class="text-sm font-semibold {{ count($hallazgosLogica) > 0 ? 'text-yellow-800' : 'text-green-800' }}">
                        Resultado de Validación Lógica
                        @if (count($hallazgosLogica) === 0)
                            — Sin hallazgos
                        @else
                            — {{ count($hallazgosLogica) }} hallazgo(s)
                        @endif
                    </h3>

                    @if (count($hallazgosLogica) > 0)
                        <div class="mt-3 space-y-2">
                            @foreach ($hallazgosLogica as $hallazgo)
                                @php
                                    $colorMap = [
                                        'error_critico' => 'bg-red-100 text-red-800 border-red-200',
                                        'advertencia' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                        'sugerencia' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    ];
                                    $color = $colorMap[$hallazgo['tipo'] ?? 'sugerencia'] ?? $colorMap['sugerencia'];
                                @endphp
                                <div class="rounded border p-2 {{ $color }}">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold uppercase">{{ $hallazgo['nivel'] ?? '' }}</span>
                                        <span class="text-xs">{{ $hallazgo['mensaje'] ?? '' }}</span>
                                    </div>
                                    @if (!empty($hallazgo['detalle']))
                                        <p class="mt-1 text-xs opacity-80">{{ $hallazgo['detalle'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-page.container>
</div>
