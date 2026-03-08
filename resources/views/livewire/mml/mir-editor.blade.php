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
                        <th class="w-1/4 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Resumen Narrativo</th>
                        <th class="w-1/3 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Indicadores</th>
                        <th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Medios de Verificación</th>
                        <th class="w-1/6 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Supuestos</th>
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
    </x-page.container>
</div>
