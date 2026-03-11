<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 6 — Alineación Estratégica"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 6: Alineación'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="6" />

        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        {{-- Plan Estatal de Desarrollo --}}
        <x-forms.section
            title="Plan Estatal de Desarrollo (PED)"
            description="Selecciona el Eje, Tema y Objetivo Estratégico al que contribuye tu programa. Puedes usar la búsqueda con IA para obtener sugerencias basadas en tu problema central."
        >
            <div class="space-y-4">
                {{-- Búsqueda con IA --}}
                <div class="flex items-center gap-3">
                    <button
                        wire:click="buscarConIa"
                        wire:loading.attr="disabled"
                        wire:target="buscarConIa"
                        type="button"
                        class="inline-flex items-center rounded-md bg-purple-50 px-3 py-2 text-sm font-semibold text-purple-700 ring-1 ring-inset ring-purple-300 hover:bg-purple-100 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="buscarConIa">Sugerir alineación con IA</span>
                        <span wire:loading wire:target="buscarConIa">Buscando...</span>
                    </button>
                    <span class="text-xs text-gray-500">Busca el Objetivo Estratégico más afín a tu problema central</span>
                </div>

                @if (count($sugerenciasIa) > 0)
                    <div class="rounded-md border border-indigo-200 bg-indigo-50 p-3 space-y-2">
                        <p class="text-xs font-medium text-indigo-700">Sugerencias de alineación:</p>
                        @foreach ($sugerenciasIa as $sug)
                            <div class="flex items-center justify-between gap-2 rounded bg-white p-2 text-sm">
                                <div>
                                    <span class="font-medium text-gray-900">{{ $sug['clave'] }}</span>
                                    <span class="ml-1 text-gray-600">{{ $sug['descripcion'] }}</span>
                                    <span class="ml-1 text-xs text-gray-400">({{ $sug['score'] }}%)</span>
                                    <p class="text-xs text-gray-500">{{ $sug['eje'] }} → {{ $sug['tema'] }}</p>
                                </div>
                                <button
                                    wire:click="seleccionarSugerencia({{ $sug['id'] }})"
                                    class="shrink-0 rounded bg-indigo-600 px-3 py-1 text-xs text-white hover:bg-indigo-700"
                                >
                                    Seleccionar
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Selects dependientes --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="ejeId" class="block text-sm font-medium text-gray-700">Eje estratégico</label>
                        <select wire:model.live="ejeId" id="ejeId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Seleccionar eje...</option>
                            @foreach ($ejes as $eje)
                                <option value="{{ $eje->id }}">{{ $eje->numero }}. {{ $eje->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="temaId" class="block text-sm font-medium text-gray-700">Tema</label>
                        <select wire:model.live="temaId" id="temaId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @if(! $ejeId) disabled @endif>
                            <option value="">Seleccionar tema...</option>
                            @foreach ($temas as $tema)
                                <option value="{{ $tema->id }}">{{ $tema->clave_completa }} {{ $tema->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="objetivoEstrategicoId" class="block text-sm font-medium text-gray-700">Objetivo estratégico</label>
                        <select wire:model="objetivoEstrategicoId" id="objetivoEstrategicoId"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            @if(! $temaId) disabled @endif>
                            <option value="">Seleccionar objetivo...</option>
                            @foreach ($objetivos as $obj)
                                <option value="{{ $obj->id }}">{{ $obj->clave_completa }} {{ $obj->descripcion }}</option>
                            @endforeach
                        </select>
                        @error('objetivoEstrategicoId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button
                        wire:click="guardar"
                        wire:loading.attr="disabled"
                        type="button"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="guardar">Guardar alineación</span>
                        <span wire:loading wire:target="guardar">Guardando...</span>
                    </button>
                </div>
            </div>
        </x-forms.section>

        {{-- CTA Finalizar Planeación --}}
        @php
            $todosCompletos = $programa->poblacion()->exists()
                && $programa->mirNiveles()->where('tipo_nivel', 'fin')->whereNotNull('ped_objetivo_estrategico_id')->exists();
        @endphp

        @if ($todosCompletos && ! $programa->planeacion_completada_at)
            <div class="mt-6 rounded-lg border-2 border-green-300 bg-green-50 p-6 text-center">
                <p class="text-sm font-semibold text-green-800 mb-2">Planeación completa (6/6 pasos)</p>
                <p class="text-xs text-green-700 mb-4">
                    Esto generará automáticamente la estructura Fin/Propósito/Componentes de tu MIR
                    a partir de la alternativa seleccionada en el Paso 4.
                </p>
                <button
                    wire:click="finalizarPlaneacion"
                    wire:loading.attr="disabled"
                    wire:confirm="¿Finalizar la planeación y generar la MIR? Podrás seguir editando la MIR después."
                    type="button"
                    class="inline-flex items-center px-6 py-3 bg-green-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-green-500 disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="finalizarPlaneacion">Finalizar Planeación y Crear MIR →</span>
                    <span wire:loading wire:target="finalizarPlaneacion">Generando MIR...</span>
                </button>
            </div>
        @elseif ($programa->planeacion_completada_at)
            <div class="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4 text-center">
                <p class="text-sm text-gray-600">
                    Planeación finalizada el {{ $programa->planeacion_completada_at->format('d/m/Y H:i') }}.
                    <a href="{{ route('mml.mir', $programa) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        Ir a la MIR →
                    </a>
                </p>
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa5', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            @if ($programa->planeacion_completada_at)
                <a href="{{ route('mml.mir', $programa) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                    Ir a la MIR →
                </a>
            @endif
        </x-slot:footer>
    </x-page.container>
</div>
