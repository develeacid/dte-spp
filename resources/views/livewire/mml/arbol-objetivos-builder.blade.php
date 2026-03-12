<div>
    <x-slot name="header">
        <x-page.header
            title="Etapa 3 — Árbol de Objetivos"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Etapa 3: Árbol de Objetivos'],
    ]">
        <x-mml.stepper :programa="$programa" :paso-actual="3" />

        @if (session('error'))
            <div class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-4 py-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @include('livewire.mml.partials.mir-impact-warning', ['tieneMir' => $tieneMir])

        @if (!$arbolProblemaId)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <p class="text-sm text-amber-700">
                    Primero debes completar el árbol de problemas en la
                    <a href="{{ route('mml.etapa2', $programa) }}" class="font-semibold underline hover:text-amber-900">Etapa 2</a>.
                </p>
            </div>
        @else
            @php
                $totalPendientes = collect($paresNodos)->filter(fn($p) => str_starts_with($p['objetivo']->descripcion, '[Pendiente'))->count();
                $totalTransformados = count($paresNodos) - $totalPendientes;
            @endphp

            {{-- Header with stats and bulk action --}}
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center gap-3">
                    <p class="text-sm text-gray-600">
                        Cada nodo del árbol de problemas se transforma en su equivalente positivo.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">
                            {{ $totalTransformados }} transformados
                        </span>
                        @if($totalPendientes > 0)
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700">
                                {{ $totalPendientes }} pendientes
                            </span>
                        @endif
                    </div>
                    @if($totalPendientes > 0)
                        <button
                            wire:click="transformarTodosConIa"
                            wire:loading.attr="disabled"
                            wire:target="transformarTodosConIa"
                            class="inline-flex items-center gap-2 rounded-lg bg-purple-600 px-3.5 py-2 text-sm font-medium text-white hover:bg-purple-500 transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="transformarTodosConIa">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                            </span>
                            <span wire:loading wire:target="transformarTodosConIa">
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </span>
                            <span wire:loading.remove wire:target="transformarTodosConIa">Transformar todos con IA</span>
                            <span wire:loading wire:target="transformarTodosConIa">Transformando...</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Column headers --}}
            <div class="hidden sm:grid sm:grid-cols-[1fr_auto_1fr] gap-4 mb-3 px-1">
                <h3 class="text-xs font-semibold text-red-600 uppercase tracking-wide">
                    <x-ui.help-label glossary="problema_central" class="text-xs font-semibold text-red-600 uppercase tracking-wide">
                        Problema (Original)
                    </x-ui.help-label>
                </h3>
                <div class="w-10"></div>
                <h3 class="text-xs font-semibold text-green-600 uppercase tracking-wide">Objetivo (Transformado)</h3>
            </div>

            {{-- Node pairs --}}
            <div class="space-y-3">
                @foreach ($paresNodos as $par)
                    @php
                        $nodoObj = $par['objetivo'];
                        $nodoProb = $par['problema'];
                        $tipoEnum = $nodoObj->tipo_nodo instanceof \App\Enums\TipoNodo
                            ? $nodoObj->tipo_nodo
                            : \App\Enums\TipoNodo::tryFrom($nodoObj->tipo_nodo);
                        $esPendiente = str_starts_with($nodoObj->descripcion, '[Pendiente');
                        $tipoProbEnum = $nodoProb
                            ? ($nodoProb->tipo_nodo instanceof \App\Enums\TipoNodo
                                ? $nodoProb->tipo_nodo
                                : \App\Enums\TipoNodo::tryFrom($nodoProb->tipo_nodo))
                            : null;
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto_1fr] gap-3 sm:gap-4 items-stretch">
                        {{-- Problem column (left) --}}
                        <div class="rounded-xl border border-red-200 bg-red-50/50 p-4">
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-red-700">
                                {{ $tipoProbEnum?->label() ?? 'N/A' }}
                            </span>
                            <p class="mt-1.5 text-sm text-red-800 line-through decoration-red-300">
                                {{ $nodoProb?->descripcion ?? 'Sin origen' }}
                            </p>
                        </div>

                        {{-- Arrow connector --}}
                        <div class="hidden sm:flex items-center justify-center">
                            <div class="flex items-center justify-center w-10 h-10 rounded-full {{ $esPendiente ? 'bg-gray-100' : 'bg-green-100' }}">
                                <svg class="w-5 h-5 {{ $esPendiente ? 'text-gray-400' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </div>
                        </div>
                        {{-- Mobile arrow --}}
                        <div class="sm:hidden flex justify-center -my-1">
                            <svg class="w-5 h-5 {{ $esPendiente ? 'text-gray-400' : 'text-green-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                            </svg>
                        </div>

                        {{-- Objective column (right) --}}
                        <div class="rounded-xl border {{ $esPendiente ? 'border-amber-200 bg-amber-50/50' : 'border-green-200 bg-green-50/50' }} p-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide
                                    {{ $esPendiente ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                    @if($esPendiente)
                                        Pendiente
                                    @else
                                        Transformado
                                    @endif
                                </span>
                                <span class="text-[10px] font-medium text-gray-400 uppercase tracking-wide">
                                    {{ $tipoEnum?->label() ?? '' }}
                                </span>
                            </div>

                            @if ($editNodoId === $nodoObj->id)
                                <textarea
                                    wire:model="editDescripcion"
                                    rows="2"
                                    class="mt-2 block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-none"
                                ></textarea>
                                @error('editDescripcion')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="guardarEdicion" class="inline-flex items-center gap-1 rounded-md bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-500 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        Guardar
                                    </button>
                                    <button wire:click="cancelarEdicion" class="rounded-md bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-200 transition-colors">
                                        Cancelar
                                    </button>
                                </div>
                            @else
                                <p class="mt-1.5 text-sm leading-relaxed {{ $esPendiente ? 'text-amber-800 italic' : 'text-green-800' }}">
                                    {{ $nodoObj->descripcion }}
                                </p>
                                <div class="mt-2 flex items-center gap-2">
                                    <button
                                        wire:click="editarNodo({{ $nodoObj->id }})"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-gray-500 hover:text-indigo-600 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Editar
                                    </button>
                                    <button
                                        wire:click="transformarConIa({{ $nodoObj->id }})"
                                        wire:loading.attr="disabled"
                                        class="inline-flex items-center gap-1 text-xs font-medium text-purple-600 hover:text-purple-800 transition-colors disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="transformarConIa({{ $nodoObj->id }})">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                                        </span>
                                        <span wire:loading.remove wire:target="transformarConIa({{ $nodoObj->id }})">Transformar con IA</span>
                                        <span wire:loading wire:target="transformarConIa({{ $nodoObj->id }})">Transformando...</span>
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('mml.etapa2', $programa) }}">
                Etapa anterior
            </x-ui.button.secondary>
            <a href="{{ route('mml.etapa4', $programa) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500 disabled:opacity-50">
                Siguiente: Selección de Alternativa →
            </a>
        </x-slot:footer>
    </x-page.container>
</div>
