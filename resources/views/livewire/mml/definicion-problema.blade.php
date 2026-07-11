<div>
    <x-slot:mobileActions>
        <button wire:click="guardar" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-md text-xs font-semibold text-gray-700">
            Guardar
        </button>
        <a href="{{ route('mml.etapa2', $programa) }}" class="inline-flex items-center px-3 py-1.5 bg-indigo-600 rounded-md text-xs font-semibold text-white">
            Siguiente
        </a>
    </x-slot:mobileActions>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 1: Problema'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="1" />

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

        {{-- Main content: two-column layout on large screens --}}
        <div class="lg:flex lg:gap-6 min-w-0">
            {{-- Left: Form --}}
            <div class="flex-1 min-w-0">
                <x-forms.section
                    title="Problema Central"
                    description="Describe la situación no deseada que el programa busca atender. Debe ser una condición negativa, no la ausencia de una solución."
                >
                    <div class="col-span-6 space-y-4">
                        <div>
                            <x-ui.help-label for="descripcion" glossary="problema_central" class="block text-sm font-medium text-gray-700">
                                Descripción del problema
                            </x-ui.help-label>
                            <div class="mt-1 relative" x-data="{
                                resize() {
                                    $refs.textarea.style.height = 'auto';
                                    $refs.textarea.style.height = $refs.textarea.scrollHeight + 'px';
                                }
                            }">
                                <textarea
                                    wire:model="descripcion"
                                    id="descripcion"
                                    x-ref="textarea"
                                    x-init="resize()"
                                    @input="resize()"
                                    rows="5"
                                    maxlength="1000"
                                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm resize-none transition-colors"
                                    placeholder="Ej: Alta tasa de desnutrición infantil en comunidades rurales del estado..."
                                ></textarea>
                            </div>
                            <div class="mt-1.5 flex justify-between items-center">
                                <div>
                                    @error('descripcion')
                                        <p class="text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <p class="text-xs text-gray-400 tabular-nums">
                                    {{ strlen($descripcion) }}/1000
                                </p>
                            </div>
                        </div>
                    </div>
                </x-forms.section>

                <x-forms.section
                    title="Ficha de Información Básica — Diagnóstico"
                    description="Responde las preguntas estructurantes del diagnóstico (temario SHCP/CONEVAL). Son opcionales pero sustentan la justificación del programa."
                >
                    <div class="col-span-6 space-y-4">
                        <div class="flex justify-end">
                            @php $completas = $this->completitud; @endphp
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $completas === 5 ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                {{ $completas }}/5 preguntas respondidas
                            </span>
                        </div>

                        <div>
                            <label for="magnitud" class="block text-sm font-medium text-gray-700">¿De qué magnitud y naturaleza es el problema?</label>
                            <p class="text-xs text-gray-400">Cuantifica con datos de fuentes confiables (INEGI, CONEVAL, Estadística 911…). Define la Población Potencial.</p>
                            <textarea wire:model="magnitud" id="magnitud" rows="3" maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label for="focalizacion" class="block text-sm font-medium text-gray-700">¿A quién afecta y cómo se puede focalizar la atención?</label>
                            <p class="text-xs text-gray-400">Grupos más afectados y criterios de priorización. Define la Población Objetivo.</p>
                            <textarea wire:model="focalizacion" id="focalizacion" rows="3" maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label for="causasEfectos" class="block text-sm font-medium text-gray-700">¿Qué causa el problema y qué efectos tiene si no se atiende?</label>
                            <p class="text-xs text-gray-400">Causas verificables (no supuestas) y consecuencias de la inacción. Insumo del Árbol de Problemas.</p>
                            <textarea wire:model="causasEfectos" id="causasEfectos" rows="3" maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label for="bienesServicios" class="block text-sm font-medium text-gray-700">¿Qué bienes o servicios son necesarios para resolverlo?</label>
                            <p class="text-xs text-gray-400">Productos o servicios que la población necesita recibir (no acciones). Anticipa los Componentes.</p>
                            <textarea wire:model="bienesServicios" id="bienesServicios" rows="3" maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                </x-forms.section>
            </div>

            {{-- Right: AI Results Panel (Notion-style sidebar) --}}
            {{-- Right: AI Results Panel (Notion-style sidebar) --}}
            <div class="mt-6 lg:mt-0 lg:w-80 xl:w-96 shrink-0">
                <div class="sticky top-24">
                @if ($resultadoValidacion)
                    <div class="rounded-xl border {{ $resultadoValidacion['is_valid'] ? 'border-green-200 bg-green-50/50' : 'border-amber-200 bg-amber-50/50' }} overflow-hidden shadow-sm">
                        {{-- Panel header --}}
                        <div class="px-4 py-3 border-b {{ $resultadoValidacion['is_valid'] ? 'border-green-200 bg-green-50' : 'border-amber-200 bg-amber-50' }}">
                            <div class="flex items-center gap-2">
                                @if($resultadoValidacion['is_valid'])
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                    </span>
                                    <h3 class="text-sm font-semibold text-green-800">Redacción válida</h3>
                                @else
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-amber-100">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.072 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                    </span>
                                    <h3 class="text-sm font-semibold text-amber-800">Observaciones encontradas</h3>
                                @endif
                            </div>
                        </div>

                        {{-- Panel body --}}
                        <div class="p-4 space-y-4">
                            {{-- Issues as badges --}}
                            @if (!empty($resultadoValidacion['issues']))
                                <div class="space-y-2">
                                    @foreach ($resultadoValidacion['issues'] as $issue)
                                        <div class="flex items-start gap-2 text-sm">
                                            <span class="mt-0.5 shrink-0 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium
                                                {{ str_contains(strtolower($issue), 'negativ') ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700' }}">
                                                {{ str_contains(strtolower($issue), 'negativ') ? 'Estado negativo' : 'Observación' }}
                                            </span>
                                            <span class="text-gray-700">{{ $issue }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Suggestion card --}}
                            @if (!empty($sugerenciaIa))
                                <div class="rounded-lg bg-white border border-gray-200 p-3 shadow-sm">
                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Sugerencia de IA</p>
                                    <p class="text-sm text-gray-700 leading-relaxed">{{ $sugerenciaIa }}</p>
                                    <div class="mt-3 flex items-center gap-2">
                                        <button
                                            wire:click="aceptarSugerencia"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Aceptar sugerencia
                                        </button>
                                        <button
                                            wire:click="$set('sugerenciaIa', '')"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-600 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                            Descartar
                                        </button>
                                    </div>
                                </div>
                            @endif
                            <button wire:click="validarConIa" wire:loading.attr="disabled" type="button" class="mt-2 w-full inline-flex justify-center items-center gap-2 rounded-lg bg-purple-50 border border-purple-200 px-3.5 py-2 text-sm font-medium text-purple-700 hover:bg-purple-100 transition-all disabled:opacity-50">
                                <span wire:loading.remove wire:target="validarConIa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                </span>
                                <span wire:loading wire:target="validarConIa">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </span>
                                <span wire:loading.remove wire:target="validarConIa">Volver a validar</span>
                                <span wire:loading wire:target="validarConIa">Analizando...</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border border-purple-200 bg-purple-50/50 shadow-sm overflow-hidden">
                        <div class="px-4 py-3 border-b border-purple-200 bg-purple-50">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-purple-100">
                                    <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                </span>
                                <h3 class="text-sm font-semibold text-purple-800">Asistente de IA</h3>
                            </div>
                        </div>
                        <div class="p-4 space-y-4">
                            <p class="text-sm text-purple-700 leading-relaxed">
                                Redacta el problema central y haz clic en validar. La IA verificará si tu redacción cumple con la metodología (condición negativa comprobable, no la falta de una solución) y te dará sugerencias de mejora.
                            </p>
                            <button
                                wire:click="validarConIa"
                                wire:loading.attr="disabled"
                                wire:target="validarConIa"
                                type="button"
                                class="w-full inline-flex justify-center items-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition-all disabled:opacity-50"
                            >
                                <span wire:loading.remove wire:target="validarConIa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                </span>
                                <span wire:loading wire:target="validarConIa">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                </span>
                                <span wire:loading.remove wire:target="validarConIa">Validar con IA</span>
                                <span wire:loading wire:target="validarConIa">Analizando...</span>
                            </button>
                        </div>
                    </div>
                @endif
                </div>
            </div>
        </div>

        <x-slot:footer>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">
                Cancelar
            </a>
            <button wire:click="guardar" wire:loading.attr="disabled" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Guardar Problema
            </button>
            <a href="{{ route('mml.etapa2', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Árbol de Problemas →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
