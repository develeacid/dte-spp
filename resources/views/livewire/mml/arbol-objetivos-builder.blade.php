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
        @if (session('error'))
            <div class="mb-4 rounded-md bg-red-50 p-4">
                <p class="text-sm text-red-700">{{ session('error') }}</p>
            </div>
        @endif

        @include('livewire.mml.partials.mir-impact-warning', ['tieneMir' => $tieneMir])

        @if (!$arbolProblemaId)
            <div class="rounded-md bg-yellow-50 p-4">
                <p class="text-sm text-yellow-700">
                    Primero debes completar el árbol de problemas en la
                    <a href="{{ route('mml.etapa2', $programa) }}" class="font-medium underline">Etapa 2</a>.
                </p>
            </div>
        @else
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    Cada nodo del árbol de problemas se transforma en su equivalente positivo.
                    Revisa y aprueba cada transformación, o usa la IA para sugerir la redacción.
                </p>

                {{-- Vista lado a lado --}}
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center">
                        <h3 class="text-sm font-semibold text-red-700 uppercase tracking-wide">Problema (Original)</h3>
                    </div>
                    <div class="text-center">
                        <h3 class="text-sm font-semibold text-green-700 uppercase tracking-wide">Objetivo (Transformado)</h3>
                    </div>
                </div>

                @foreach ($paresNodos as $par)
                    @php
                        $nodoObj = $par['objetivo'];
                        $nodoProb = $par['problema'];
                        $tipoEnum = $nodoObj->tipo_nodo instanceof \App\Enums\TipoNodo
                            ? $nodoObj->tipo_nodo
                            : \App\Enums\TipoNodo::tryFrom($nodoObj->tipo_nodo);
                        $esPendiente = str_starts_with($nodoObj->descripcion, '[Pendiente');
                    @endphp

                    <div class="grid grid-cols-2 gap-4 items-start">
                        {{-- Columna Problema --}}
                        <div class="rounded-md border border-red-200 bg-red-50 p-3">
                            @php
                                $tipoProbEnum = $nodoProb
                                    ? ($nodoProb->tipo_nodo instanceof \App\Enums\TipoNodo
                                        ? $nodoProb->tipo_nodo
                                        : \App\Enums\TipoNodo::tryFrom($nodoProb->tipo_nodo))
                                    : null;
                            @endphp
                            <span class="text-xs font-semibold uppercase text-red-500">
                                {{ $tipoProbEnum?->label() ?? 'N/A' }}
                            </span>
                            <p class="mt-1 text-sm text-red-800 line-through">
                                {{ $nodoProb?->descripcion ?? 'Sin origen' }}
                            </p>
                        </div>

                        {{-- Columna Objetivo --}}
                        <div class="rounded-md border {{ $esPendiente ? 'border-yellow-300 bg-yellow-50' : 'border-green-200 bg-green-50' }} p-3">
                            <span class="text-xs font-semibold uppercase {{ $esPendiente ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ $tipoEnum?->label() ?? '' }}
                            </span>

                            @if ($editNodoId === $nodoObj->id)
                                <textarea
                                    wire:model="editDescripcion"
                                    rows="2"
                                    class="mt-1 block w-full rounded-md border-gray-300 text-sm"
                                ></textarea>
                                @error('editDescripcion')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="guardarEdicion" class="text-xs text-green-700 font-medium">Guardar</button>
                                    <button wire:click="cancelarEdicion" class="text-xs text-gray-500">Cancelar</button>
                                </div>
                            @else
                                <p class="mt-1 text-sm {{ $esPendiente ? 'text-yellow-800 italic' : 'text-green-800' }}">
                                    {{ $nodoObj->descripcion }}
                                </p>
                                <div class="mt-2 flex gap-2">
                                    <button
                                        wire:click="editarNodo({{ $nodoObj->id }})"
                                        class="text-xs text-indigo-600 hover:text-indigo-800"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        wire:click="transformarConIa({{ $nodoObj->id }})"
                                        wire:loading.attr="disabled"
                                        class="text-xs text-purple-600 hover:text-purple-800 disabled:opacity-50"
                                    >
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
        </x-slot:footer>
    </x-page.container>
</div>
