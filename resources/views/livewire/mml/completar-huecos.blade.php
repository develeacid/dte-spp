<x-page.container>
    @include('livewire.mml.partials.stepper-importacion', ['pasoActual' => 2])

    <x-page.header title="Completar Huecos de MIR Importada">
        <button wire:click="finalizar"
                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            Finalizar y continuar
        </button>
    </x-page.header>

    {{-- Progress bar --}}
    <div class="mb-6">
        <div class="flex justify-between text-sm text-gray-600 mb-1">
            <span>Progreso de completitud</span>
            <span>{{ $progreso }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="h-3 rounded-full transition-all duration-300 {{ $progreso === 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                 style="width: {{ $progreso }}%"></div>
        </div>
    </div>

    {{-- Niveles and indicators --}}
    @foreach ($niveles as $nivel)
        <div class="mb-6 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b">
                <h3 class="text-sm font-semibold text-gray-700">
                    <span class="inline-block px-2 py-0.5 rounded text-xs font-medium border
                        {{ match($nivel['tipo_nivel']) {
                            'fin' => 'bg-blue-100 text-blue-800 border-blue-300',
                            'proposito' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            'componente' => 'bg-amber-100 text-amber-800 border-amber-300',
                            'actividad' => 'bg-violet-100 text-violet-800 border-violet-300',
                            default => 'bg-gray-100 text-gray-800 border-gray-300',
                        } }}">
                        {{ $nivel['tipo_nivel_label'] }}
                    </span>
                    <span class="ml-2">{{ Str::limit($nivel['resumen_narrativo'] ?? '-', 80) }}</span>
                </h3>
            </div>

            <div class="divide-y">
                @foreach ($nivel['indicadores'] as $indicador)
                    <div class="px-4 py-4 {{ !$indicador['activo_seguimiento'] ? 'bg-red-50' : '' }}">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex items-center gap-2">
                                @if ($indicador['activo_seguimiento'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                                        Requiere correcciones
                                    </span>
                                @endif
                                <span class="text-sm font-medium text-gray-900">{{ $indicador['nombre'] }}</span>
                            </div>
                        </div>

                        {{-- Show current values --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500 mb-3">
                            <div>
                                <span class="font-medium">Tipo:</span>
                                {{ $indicador['tipo'] ?? 'Sin definir' }}
                            </div>
                            <div>
                                <span class="font-medium">Dimension:</span>
                                {{ $indicador['dimension'] ?? 'Sin definir' }}
                            </div>
                            <div>
                                <span class="font-medium">Frecuencia:</span>
                                {{ $indicador['frecuencia'] ?? 'Sin definir' }}
                            </div>
                            <div>
                                <span class="font-medium">Formula:</span>
                                {{ $indicador['formula_texto'] ? Str::limit($indicador['formula_texto'], 30) : 'Sin definir' }}
                            </div>
                        </div>

                        {{-- Inline correction forms for missing fields --}}
                        @if (!empty($indicador['campos_faltantes']))
                            <div class="mt-3 p-3 bg-yellow-50 rounded-md border border-yellow-200">
                                <p class="text-xs font-medium text-yellow-800 mb-2">Campos faltantes:</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    @foreach ($indicador['campos_faltantes'] as $campo)
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                {{ match($campo) {
                                                    'formula_texto' => 'Formula',
                                                    'tipo' => 'Tipo de indicador',
                                                    'dimension' => 'Dimension',
                                                    'frecuencia' => 'Frecuencia',
                                                    default => $campo,
                                                } }}
                                            </label>

                                            @if ($campo === 'formula_texto')
                                                <div class="flex gap-1">
                                                    <input type="text"
                                                           wire:keydown.enter="corregirCampo({{ $indicador['id'] }}, '{{ $campo }}', $event.target.value)"
                                                           class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                           placeholder="Ej: (A/B)*100">
                                                </div>
                                            @elseif ($campo === 'tipo')
                                                <select wire:change="corregirCampo({{ $indicador['id'] }}, '{{ $campo }}', $event.target.value)"
                                                        class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Seleccione...</option>
                                                    @foreach (\App\Enums\TipoIndicador::cases() as $case)
                                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($campo === 'dimension')
                                                <select wire:change="corregirCampo({{ $indicador['id'] }}, '{{ $campo }}', $event.target.value)"
                                                        class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Seleccione...</option>
                                                    @foreach (\App\Enums\DimensionIndicador::cases() as $case)
                                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                                    @endforeach
                                                </select>
                                            @elseif ($campo === 'frecuencia')
                                                <select wire:change="corregirCampo({{ $indicador['id'] }}, '{{ $campo }}', $event.target.value)"
                                                        class="block w-full text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                    <option value="">Seleccione...</option>
                                                    @foreach (\App\Enums\FrecuenciaMedicion::cases() as $case)
                                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                                    @endforeach
                                                </select>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</x-page.container>
