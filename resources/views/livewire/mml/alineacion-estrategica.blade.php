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
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-green-50 border border-green-200 px-4 py-3">
                <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-green-700">{{ session('success') }}</p>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        <div class="space-y-8">
            {{-- Section 1: PED Alignment --}}
            <x-forms.section
                title="Plan Estatal de Desarrollo (PED)"
                description="Selecciona el Eje, Tema y Objetivo Estratégico al que contribuye tu programa."
            >
                <div class="col-span-6 space-y-5">
                    {{-- AI Search --}}
                    <div class="rounded-xl border border-purple-200 bg-purple-50/50 p-4">
                        <div class="flex items-center justify-between gap-3 flex-wrap">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                <div>
                                    <p class="text-sm font-semibold text-purple-800">Búsqueda inteligente</p>
                                    <p class="text-xs text-purple-600">Encuentra el Objetivo Estratégico más afín a tu problema central</p>
                                </div>
                            </div>
                            <button
                                wire:click="buscarConIa"
                                wire:loading.attr="disabled"
                                wire:target="buscarConIa"
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500 transition-colors disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="buscarConIa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </span>
                                <span wire:loading wire:target="buscarConIa">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </span>
                                <span wire:loading.remove wire:target="buscarConIa">Buscar con IA</span>
                                <span wire:loading wire:target="buscarConIa">Buscando...</span>
                            </button>
                        </div>

                        {{-- AI Suggestion cards --}}
                        @if (count($sugerenciasIa) > 0)
                            <div class="mt-4 space-y-2">
                                @foreach ($sugerenciasIa as $sug)
                                    @php $isSelected = $objetivoEstrategicoId === $sug['id']; @endphp
                                    <div class="flex items-center justify-between gap-3 rounded-xl p-3 transition-all
                                        {{ $isSelected ? 'bg-green-50 ring-2 ring-green-400 shadow-sm' : 'bg-white ring-1 ring-gray-200 hover:ring-purple-300' }}">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2 flex-wrap">
                                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-bold text-gray-700">
                                                    {{ $sug['clave'] }}
                                                </span>
                                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold
                                                    {{ $sug['score'] >= 70 ? 'bg-green-100 text-green-700' : ($sug['score'] >= 40 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600') }}">
                                                    {{ $sug['score'] }}% relevancia
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-800 leading-relaxed">{{ $sug['descripcion'] }}</p>
                                            <p class="mt-0.5 text-xs text-gray-400">{{ $sug['eje'] }} → {{ $sug['tema'] }}</p>
                                        </div>
                                        @if ($isSelected)
                                            <span class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-semibold text-white">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Seleccionado
                                            </span>
                                        @else
                                            <button
                                                wire:click="seleccionarSugerencia({{ $sug['id'] }})"
                                                class="shrink-0 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-500 transition-colors"
                                            >
                                                Seleccionar
                                            </button>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Dependent selects: Eje → Tema → Objetivo --}}
                    <div>
                        <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">O selecciona manualmente:</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="ejeId" class="block text-sm font-medium text-gray-700 mb-1">Eje estratégico</label>
                                <select wire:model.live="ejeId" id="ejeId"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="">Seleccionar eje...</option>
                                    @foreach ($ejes as $eje)
                                        <option value="{{ $eje->id }}">{{ $eje->numero }}. {{ $eje->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="temaId" class="block text-sm font-medium text-gray-700 mb-1">
                                    Tema
                                    @if(!$ejeId)
                                        <span class="text-xs text-gray-400 font-normal">(selecciona un eje)</span>
                                    @endif
                                </label>
                                <select wire:model.live="temaId" id="temaId"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-400"
                                    @if(! $ejeId) disabled @endif>
                                    <option value="">Seleccionar tema...</option>
                                    @foreach ($temas as $tema)
                                        <option value="{{ $tema->id }}">{{ $tema->clave_completa }} {{ $tema->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="objetivoEstrategicoId" class="block text-sm font-medium text-gray-700 mb-1">
                                    Objetivo estratégico
                                    @if(!$temaId)
                                        <span class="text-xs text-gray-400 font-normal">(selecciona un tema)</span>
                                    @endif
                                </label>
                                <select wire:model="objetivoEstrategicoId" id="objetivoEstrategicoId"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-50 disabled:text-gray-400"
                                    @if(! $temaId) disabled @endif>
                                    <option value="">Seleccionar objetivo...</option>
                                    @foreach ($objetivos as $obj)
                                        <option value="{{ $obj->id }}">{{ $obj->clave_completa }} {{ $obj->descripcion }}</option>
                                    @endforeach
                                </select>
                                @error('objetivoEstrategicoId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- PND/ODS reference badges --}}
                    @if($objetivoEstrategicoId && ($pndRelacionados->isNotEmpty() || $odsRelacionados->isNotEmpty()))
                    <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Alineaciones de referencia</h4>

                        @if($pndRelacionados->isNotEmpty())
                        <div class="mb-3">
                            <span class="text-xs font-semibold text-blue-700 uppercase tracking-wide">PND</span>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach($pndRelacionados as $pnd)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $pnd->clave ?? $pnd->nombre ?? $pnd->descripcion ?? '' }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($odsRelacionados->isNotEmpty())
                        <div>
                            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wide">ODS</span>
                            <div class="mt-1 flex flex-wrap gap-2">
                                @foreach($odsRelacionados as $ods)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                    {{ $ods->numero ?? '' }}. {{ $ods->nombre }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    {{-- Save button --}}
                    <div class="flex justify-end">
                        <button
                            wire:click="guardar"
                            wire:loading.attr="disabled"
                            type="button"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-indigo-500 transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="guardar">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span wire:loading wire:target="guardar">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                            <span wire:loading.remove wire:target="guardar">Guardar alineación</span>
                            <span wire:loading wire:target="guardar">Guardando...</span>
                        </button>
                    </div>
                </div>
            </x-forms.section>

            {{-- Section 2: ODS --}}
            @if($odsObjetivos->isNotEmpty())
                <x-forms.section
                    title="Objetivos de Desarrollo Sostenible (ODS)"
                    description="Selecciona los ODS a los que contribuye tu programa. Opcional pero recomendado."
                >
                    <div class="col-span-6 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-2">
                        @foreach ($odsObjetivos as $ods)
                            @php
                                $isChecked = in_array($ods->id, $odsSeleccionados);
                                $odsColors = [
                                    1 => 'bg-red-600', 2 => 'bg-amber-500', 3 => 'bg-green-600',
                                    4 => 'bg-red-700', 5 => 'bg-orange-500', 6 => 'bg-cyan-500',
                                    7 => 'bg-yellow-500', 8 => 'bg-rose-700', 9 => 'bg-orange-600',
                                    10 => 'bg-pink-600', 11 => 'bg-amber-600', 12 => 'bg-yellow-700',
                                    13 => 'bg-green-700', 14 => 'bg-blue-600', 15 => 'bg-lime-600',
                                    16 => 'bg-blue-800', 17 => 'bg-blue-900',
                                ];
                                $bgColor = $odsColors[$ods->numero] ?? 'bg-gray-600';
                            @endphp
                            <label class="relative flex flex-col items-center p-2.5 rounded-xl border-2 cursor-pointer transition-all
                                {{ $isChecked ? 'border-indigo-400 bg-indigo-50 shadow-sm' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <input
                                    type="checkbox"
                                    wire:model="odsSeleccionados"
                                    value="{{ $ods->id }}"
                                    class="sr-only"
                                />
                                <span class="flex items-center justify-center w-10 h-10 rounded-lg {{ $bgColor }} text-white text-lg font-bold">
                                    {{ $ods->numero }}
                                </span>
                                <span class="mt-1.5 text-[10px] text-center text-gray-600 leading-tight line-clamp-2">{{ $ods->nombre }}</span>
                                @if($isChecked)
                                    <span class="absolute top-1 right-1 flex items-center justify-center w-4 h-4 rounded-full bg-indigo-500">
                                        <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                </x-forms.section>
            @endif

            {{-- Section 3: Transversal Annexes --}}
            @if($anexos->isNotEmpty())
                <x-forms.section
                    title="Anexos Transversales"
                    description="Indica si tu programa se vincula con algún anexo transversal del PEF."
                >
                    <div class="col-span-6 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        @foreach ($anexos as $anexo)
                            @php $isChecked = in_array($anexo->id, $anexosSeleccionados); @endphp
                            <label class="flex items-center gap-3 p-3 rounded-xl border-2 cursor-pointer transition-all
                                {{ $isChecked ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                                <input
                                    type="checkbox"
                                    wire:model="anexosSeleccionados"
                                    value="{{ $anexo->id }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="shrink-0 flex items-center justify-center w-8 h-8 rounded-lg bg-gray-100 text-gray-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </span>
                                    <span class="text-sm text-gray-700 leading-snug">{{ $anexo->nombre }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-forms.section>
            @endif

            {{-- Section 4: Finalize CTA --}}
            @php
                $todosCompletos = $programa->poblacion()->exists()
                    && $programa->mirNiveles()->where('tipo_nivel', 'fin')->whereNotNull('ped_objetivo_estrategico_id')->exists();
            @endphp

            @if ($todosCompletos && ! $programa->planeacion_completada_at)
                <div class="rounded-2xl border-2 border-green-300 bg-gradient-to-br from-green-50 to-emerald-50 p-8 text-center">
                    <div class="flex justify-center mb-3">
                        <span class="flex items-center justify-center w-14 h-14 rounded-full bg-green-100">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <h3 class="text-lg font-bold text-green-800 mb-1">Planeación completa (6/6 pasos)</h3>
                    <p class="text-sm text-green-700 mb-5 max-w-lg mx-auto">
                        Esto generará automáticamente la estructura Fin/Propósito/Componentes de tu MIR
                        a partir de la alternativa seleccionada en el Paso 4.
                    </p>
                    <button
                        wire:click="finalizarPlaneacion"
                        wire:loading.attr="disabled"
                        wire:confirm="¿Finalizar la planeación y generar la MIR? Podrás seguir editando la MIR después."
                        type="button"
                        class="inline-flex items-center gap-2 px-8 py-3 bg-green-600 border border-transparent rounded-xl font-bold text-base text-white uppercase tracking-wide hover:bg-green-500 shadow-lg shadow-green-200 transition-all disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="finalizarPlaneacion">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                        <span wire:loading wire:target="finalizarPlaneacion">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </span>
                        <span wire:loading.remove wire:target="finalizarPlaneacion">Finalizar Planeación y Crear MIR</span>
                        <span wire:loading wire:target="finalizarPlaneacion">Generando MIR...</span>
                    </button>
                </div>
            @elseif ($programa->planeacion_completada_at)
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-5 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex items-center justify-center w-10 h-10 rounded-full bg-green-100">
                            <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Planeación finalizada</p>
                            <p class="text-xs text-gray-500">{{ $programa->planeacion_completada_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <a href="{{ route('mml.mir', $programa) }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 transition-colors">
                        Ir a la MIR
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            @endif
        </div>

        {{-- Alineación normativa (Jurídico) --}}
        @can('ver_sustento_legal')
        <x-forms.section title="Alineación normativa" description="Estado del sustento legal del programa">
            <div class="col-span-6">
                <x-programa.estado-juridico :programa="$programa" />
            </div>
        </x-forms.section>
        @endcan

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
