<x-page.container>
    <x-page.header title="Vincular Alineación con Cascada de Planes">
        @if ($pasoActual >= $totalPasos - 1)
            <button wire:click="finalizar"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Finalizar y continuar
            </button>
        @endif
    </x-page.header>

    {{-- Step indicator --}}
    <div class="mb-6">
        <div class="flex justify-between text-sm text-gray-600 mb-2">
            <span>Paso {{ $pasoActual + 1 }} de {{ $totalPasos }}</span>
            <span>{{ $totalPasos > 0 ? round((($pasoActual + 1) / $totalPasos) * 100) : 0 }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="h-2 rounded-full bg-indigo-500 transition-all duration-300"
                 style="width: {{ $totalPasos > 0 ? round((($pasoActual + 1) / $totalPasos) * 100) : 0 }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main panel: current nivel --}}
        <div class="lg:col-span-2">
            @if ($nivelActual)
                <div class="bg-white shadow rounded-lg overflow-hidden mb-4">
                    <div class="px-4 py-3 bg-gray-50 border-b">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-medium border {{ $nivelActual['color_class'] }}">
                            {{ $nivelActual['tipo_nivel_label'] }}
                        </span>
                    </div>
                    <div class="px-4 py-4">
                        <p class="text-sm text-gray-700 mb-4">{{ $nivelActual['resumen_narrativo'] }}</p>

                        @if ($nivelActual['vinculado'])
                            <div class="flex items-center gap-2 text-sm text-green-700 bg-green-50 px-3 py-2 rounded mb-4">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                Ya vinculado
                            </div>
                        @endif

                        {{-- Search button --}}
                        <button wire:click="buscar"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                            <span wire:loading.remove wire:target="buscar">Buscar Alineacion</span>
                            <span wire:loading wire:target="buscar">Buscando...</span>
                        </button>
                    </div>
                </div>

                {{-- Suggestions list --}}
                @if (count($sugerencias) > 0)
                    <div class="bg-white shadow rounded-lg overflow-hidden mb-4">
                        <div class="px-4 py-3 bg-gray-50 border-b">
                            <h3 class="text-sm font-semibold text-gray-700">Sugerencias de alineacion</h3>
                        </div>
                        <div class="divide-y">
                            @foreach ($sugerencias as $sugerencia)
                                <div class="px-4 py-3 flex items-center justify-between hover:bg-gray-50">
                                    <div class="flex-1 mr-3">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-medium px-2 py-0.5 rounded
                                                {{ $sugerencia['alta_confianza'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                                {{ $sugerencia['score'] }}%
                                            </span>
                                            @if ($sugerencia['alta_confianza'])
                                                <span class="text-xs font-medium text-green-700">Alta confianza</span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-700">{{ $sugerencia['texto'] }}</p>
                                    </div>
                                    <button wire:click="seleccionar({{ $sugerencia['id'] }}, '{{ $sugerencia['tipo'] }}')"
                                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 border border-transparent rounded-md text-xs font-medium text-white hover:bg-indigo-500 transition">
                                        Seleccionar
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Navigation buttons --}}
                <div class="flex justify-between">
                    <button wire:click="anterior"
                            @if ($pasoActual === 0) disabled @endif
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        Anterior
                    </button>
                    <button wire:click="omitir"
                            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Omitir
                    </button>
                </div>
            @else
                <div class="bg-white shadow rounded-lg p-6 text-center text-gray-500">
                    No hay niveles MIR para vincular.
                </div>
            @endif
        </div>

        {{-- Side panel: summary --}}
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg overflow-hidden sticky top-4">
                <div class="px-4 py-3 bg-gray-50 border-b">
                    <h3 class="text-sm font-semibold text-gray-700">Resumen de niveles</h3>
                </div>
                <div class="divide-y">
                    @foreach ($niveles as $index => $nivel)
                        <div class="px-4 py-2 text-xs {{ $index === $pasoActual ? 'bg-indigo-50 border-l-2 border-indigo-500' : '' }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-xs font-medium border {{ $nivel['color_class'] }}">
                                        {{ $nivel['tipo_nivel_label'] }}
                                    </span>
                                    @if ($nivel['vinculado'])
                                        <svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <span class="text-gray-400">Pendiente</span>
                                    @endif
                                </div>
                            </div>
                            <p class="text-gray-500 mt-1 truncate">{{ Str::limit($nivel['resumen_narrativo'] ?? '-', 50) }}</p>
                            @if ($nivel['vinculacion_texto'])
                                <p class="text-green-600 mt-0.5 truncate">{{ Str::limit($nivel['vinculacion_texto'], 40) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-page.container>
