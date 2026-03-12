<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 5 — Embudo de Poblaciones"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 5: Poblaciones'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="5" />

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

        {{-- Side-by-side: Funnel (left) + Form (right) --}}
        <div x-data="{
            ref: {{ $referencia_cantidad ?? 0 }},
            pot: {{ $potencial_cantidad ?? 0 }},
            obj: {{ $objetivo_cantidad ?? 0 }},
            unidad: '{{ $unidad_medida }}',
            get potPct() { return this.ref > 0 ? Math.round((this.pot / this.ref) * 100) : 0 },
            get objPct() { return this.ref > 0 ? Math.round((this.obj / this.ref) * 100) : 0 },
            get potW() { return this.ref > 0 ? Math.max(30, Math.round((this.pot / this.ref) * 100)) : 70 },
            get objW() { return this.ref > 0 ? Math.max(20, Math.round((this.obj / this.ref) * 100)) : 50 },
            get potValid() { return !this.pot || !this.ref || this.pot <= this.ref },
            get objValid() { return !this.obj || !this.pot || this.obj <= this.pot },
            fmt(n) { return n ? Number(n).toLocaleString('es-MX') : '0' }
        }" class="lg:flex lg:gap-6">
            {{-- Left: Funnel visualization (sticky) --}}
            <div class="lg:w-80 xl:w-96 shrink-0 mb-6 lg:mb-0">
                <div class="sticky top-24">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Embudo de Poblaciones
                        </h3>

                        <div class="space-y-1 flex flex-col items-center">
                            {{-- Referencia (widest) --}}
                            <div class="w-full rounded-t-xl bg-blue-100 border-2 border-blue-200 px-4 py-3 text-center transition-all">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-blue-500">Referencia</p>
                                <p class="text-xl font-bold text-blue-800" x-text="fmt(ref) + ' ' + unidad"></p>
                            </div>

                            {{-- Percentage between ref → pot --}}
                            <div class="flex items-center gap-1 py-0.5" x-show="ref > 0 && pot > 0">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                <span class="text-[10px] font-semibold tabular-nums" :class="potValid ? 'text-gray-500' : 'text-red-600'" x-text="potPct + '%'"></span>
                            </div>

                            {{-- Potencial --}}
                            <div class="rounded bg-amber-100 border-2 px-4 py-3 text-center transition-all"
                                 :class="potValid ? 'border-amber-200' : 'border-red-400 ring-2 ring-red-200'"
                                 :style="'width: ' + potW + '%'">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Potencial</p>
                                <p class="text-xl font-bold text-amber-800" x-text="fmt(pot) + ' ' + unidad"></p>
                            </div>

                            {{-- Percentage between pot → obj --}}
                            <div class="flex items-center gap-1 py-0.5" x-show="pot > 0 && obj > 0">
                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                                <span class="text-[10px] font-semibold tabular-nums" :class="objValid ? 'text-gray-500' : 'text-red-600'" x-text="objPct + '%'"></span>
                            </div>

                            {{-- Objetivo --}}
                            <div class="rounded-b-xl bg-green-100 border-2 px-4 py-3 text-center transition-all"
                                 :class="objValid ? 'border-green-200' : 'border-red-400 ring-2 ring-red-200'"
                                 :style="'width: ' + objW + '%'">
                                <p class="text-[10px] font-bold uppercase tracking-wider text-green-500">Objetivo</p>
                                <p class="text-xl font-bold text-green-800" x-text="fmt(obj) + ' ' + unidad"></p>
                            </div>
                        </div>

                        <p class="mt-3 text-center text-[10px] text-gray-400 leading-relaxed">
                            La Población Atendida se calculará desde el Padrón de Beneficiarios durante la operación del programa.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Right: Form --}}
            <div class="flex-1 min-w-0">
                <x-forms.section
                    title="Poblaciones del programa"
                    description="Define las poblaciones de referencia, potencial y objetivo. Las cantidades deben cumplir: Objetivo ≤ Potencial ≤ Referencia."
                >
                    <div class="col-span-6 space-y-5">
                        {{-- Unit of measure --}}
                        <div>
                            <label for="unidad_medida" class="block text-sm font-medium text-gray-700">Unidad de medida</label>
                            <input
                                type="text"
                                wire:model="unidad_medida"
                                x-on:input="unidad = $event.target.value"
                                id="unidad_medida"
                                placeholder="Ej: Niños, Familias, MIPYMES"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                            @error('unidad_medida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        {{-- Población de Referencia --}}
                        <div class="rounded-xl border-2 border-blue-200 bg-blue-50/50 p-4 space-y-3">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-200">
                                    <span class="text-xs font-bold text-blue-700">1</span>
                                </span>
                                <h4 class="text-sm font-semibold text-blue-800">Población de Referencia</h4>
                            </div>
                            <p class="text-xs text-blue-600">Población total del ámbito geográfico del programa.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="referencia_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" wire:model="referencia_cantidad" x-on:input="ref = parseInt($event.target.value) || 0" id="referencia_cantidad" min="1"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @error('referencia_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="referencia_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                                    <input type="text" wire:model="referencia_fuente" id="referencia_fuente"
                                        placeholder="Ej: INEGI Censo 2020"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- Población Potencial --}}
                        <div class="rounded-xl border-2 p-4 space-y-3 transition-colors"
                             :class="potValid ? 'border-amber-200 bg-amber-50/50' : 'border-red-300 bg-red-50/50'">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full" :class="potValid ? 'bg-amber-200' : 'bg-red-200'">
                                    <span class="text-xs font-bold" :class="potValid ? 'text-amber-700' : 'text-red-700'">2</span>
                                </span>
                                <h4 class="text-sm font-semibold" :class="potValid ? 'text-amber-800' : 'text-red-800'">Población Potencial</h4>
                            </div>
                            <p class="text-xs" :class="potValid ? 'text-amber-600' : 'text-red-600'">Población que presenta el problema o necesidad que el programa busca atender.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="potencial_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" wire:model="potencial_cantidad" x-on:input="pot = parseInt($event.target.value) || 0" id="potencial_cantidad" min="1"
                                        class="mt-1 block w-full rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors"
                                        :class="potValid ? 'border-gray-300' : 'border-red-400 ring-1 ring-red-200'">
                                    @error('potencial_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="potencial_fuente" class="block text-sm font-medium text-gray-700">Fuente (opcional)</label>
                                    <input type="text" wire:model="potencial_fuente" id="potencial_fuente"
                                        placeholder="Ej: CONEVAL 2023"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        {{-- Población Objetivo --}}
                        <div class="rounded-xl border-2 p-4 space-y-3 transition-colors"
                             :class="objValid ? 'border-green-200 bg-green-50/50' : 'border-red-300 bg-red-50/50'">
                            <div class="flex items-center gap-2">
                                <span class="flex items-center justify-center w-6 h-6 rounded-full" :class="objValid ? 'bg-green-200' : 'bg-red-200'">
                                    <span class="text-xs font-bold" :class="objValid ? 'text-green-700' : 'text-red-700'">3</span>
                                </span>
                                <h4 class="text-sm font-semibold" :class="objValid ? 'text-green-800' : 'text-red-800'">Población Objetivo</h4>
                            </div>
                            <p class="text-xs" :class="objValid ? 'text-green-600' : 'text-red-600'">Subconjunto de la población potencial que el programa atenderá en este ejercicio fiscal.</p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="objetivo_cantidad" class="block text-sm font-medium text-gray-700">Cantidad</label>
                                    <input type="number" wire:model="objetivo_cantidad" x-on:input="obj = parseInt($event.target.value) || 0" id="objetivo_cantidad" min="1"
                                        class="mt-1 block w-full rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm transition-colors"
                                        :class="objValid ? 'border-gray-300' : 'border-red-400 ring-1 ring-red-200'">
                                    @error('objetivo_cantidad') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label for="objetivo_justificacion" class="block text-sm font-medium text-gray-700">Justificación (opcional)</label>
                                    <input type="text" wire:model="objetivo_justificacion" id="objetivo_justificacion"
                                        placeholder="¿Por qué este recorte respecto a la potencial?"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

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
                                <span wire:loading.remove wire:target="guardar">Guardar poblaciones</span>
                                <span wire:loading wire:target="guardar">Guardando...</span>
                            </button>
                        </div>
                    </div>
                </x-forms.section>
            </div>
        </div>

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa4', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa6', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Alineación Estratégica →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
