<div>
    <x-page.header title="Importar Programa desde MIR" />

    <x-page.container>
        @include('livewire.mml.partials.stepper-importacion', ['pasoActual' => 1])
        {{-- Upload section --}}
        <div class="mb-6">
            <label for="archivo" class="block text-sm font-medium text-gray-700 mb-2">
                Archivo MIR (.md, .csv, .xlsx) — máximo 5 MB
            </label>
            <input
                type="file"
                id="archivo"
                wire:model="archivo"
                accept=".md,.csv,.xlsx"
                class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            />
            <div wire:loading wire:target="archivo" class="mt-2 text-sm text-gray-500">
                Procesando archivo...
            </div>
            @error('archivo')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        {{-- Error message --}}
        @if ($errorMensaje)
            <div class="mb-6 rounded-md bg-red-50 p-4 border border-red-200">
                <p class="text-sm text-red-700">{{ $errorMensaje }}</p>
            </div>
        @endif

        {{-- Preview section --}}
        @if ($preview)
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Vista previa</h3>

                @if ($preview['nombre'] || $preview['clave'])
                    <div class="mb-3 text-sm text-gray-600">
                        @if ($preview['nombre'])
                            <span class="font-medium">Programa:</span> {{ $preview['nombre'] }}
                        @endif
                        @if ($preview['clave'])
                            <span class="ml-3 font-medium">Clave:</span> {{ $preview['clave'] }}
                        @endif
                        @if ($preview['ejercicio_fiscal'])
                            <span class="ml-3 font-medium">Ejercicio:</span> {{ $preview['ejercicio_fiscal'] }}
                        @endif
                    </div>
                @endif

                <table class="min-w-full divide-y divide-gray-200 border rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nivel</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Resumen Narrativo</th>
                            <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Indicadores</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($preview['niveles'] as $nivel)
                            <tr>
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 capitalize">{{ $nivel['tipo_nivel'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-700 max-w-md truncate">{{ \Illuminate\Support\Str::limit($nivel['resumen_narrativo'], 80) }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 text-center">{{ $nivel['indicadores_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Diagnosis panel --}}
            @if ($conteo)
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Diagnóstico</h3>

                    {{-- Summary badges --}}
                    <div class="flex gap-4 mb-4">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $conteo['critico'] > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-500' }}">
                            Críticos: {{ $conteo['critico'] }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $conteo['menor'] > 0 ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-500' }}">
                            Menores: {{ $conteo['menor'] }}
                        </span>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $conteo['advertencia'] > 0 ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-500' }}">
                            Advertencias: {{ $conteo['advertencia'] }}
                        </span>
                    </div>

                    {{-- Gaps list --}}
                    @if (count($diagnostico) > 0)
                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @foreach ($diagnostico as $gap)
                                @php
                                    $colorClass = match ($gap['severidad']) {
                                        'critico' => 'border-red-300 bg-red-50 text-red-800',
                                        'menor' => 'border-yellow-300 bg-yellow-50 text-yellow-800',
                                        'advertencia' => 'border-blue-300 bg-blue-50 text-blue-800',
                                        default => 'border-gray-300 bg-gray-50 text-gray-800',
                                    };
                                    $nivelLabel = $preview['niveles'][$gap['nivel_idx']]['tipo_nivel'] ?? 'N/A';
                                @endphp
                                <div class="flex items-start gap-3 p-3 border rounded-md {{ $colorClass }}">
                                    <div class="text-xs font-medium capitalize whitespace-nowrap">
                                        {{ $nivelLabel }}
                                        @if ($gap['indicador_idx'] !== null)
                                            &rarr; Ind. {{ $gap['indicador_idx'] + 1 }}
                                        @endif
                                    </div>
                                    <div class="text-sm">{{ $gap['mensaje'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-green-600">No se encontraron brechas. Los datos se ven completos.</p>
                    @endif
                </div>
            @endif

            {{-- Continue button --}}
            <div class="flex justify-end">
                <button
                    wire:click="continuar"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Continuar
                </button>
            </div>
        @endif
    </x-page.container>
</div>
