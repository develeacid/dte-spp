@php
    $tipoEnum = $nodo->tipo_nodo instanceof \App\Enums\TipoNodo
        ? $nodo->tipo_nodo
        : \App\Enums\TipoNodo::tryFrom($nodo->tipo_nodo);
    $colorClass = $tipoEnum ? $tipoEnum->colorClass() : 'bg-gray-100 text-gray-800 border-gray-300';
    $label = $tipoEnum ? $tipoEnum->label() : $nodo->tipo_nodo;

    // Determine accent color for left border
    $accentBorder = match(true) {
        $tipoEnum === \App\Enums\TipoNodo::PROBLEMA_CENTRAL => 'border-l-red-500',
        $tipoEnum?->esProblema() && str_contains($tipoEnum->value, 'causa') => 'border-l-orange-500',
        $tipoEnum?->esProblema() && str_contains($tipoEnum->value, 'efecto') => 'border-l-purple-500',
        $tipoEnum?->esObjetivo() => 'border-l-green-500',
        default => 'border-l-gray-400',
    };
@endphp

<div class="rounded-lg border border-l-4 {{ $accentBorder }} bg-white shadow-sm p-3 flex items-start justify-between group hover:shadow-md transition-shadow">
    <div class="min-w-0 flex-1">
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide {{ $colorClass }}">
            {{ $label }}
        </span>
        <p class="mt-1 text-sm text-gray-800 leading-relaxed">{{ $nodo->descripcion }}</p>
    </div>
    <div class="ml-2 flex items-center gap-0.5 opacity-0 group-hover:opacity-100 transition-opacity shrink-0">
        <button
            wire:click="editarNodo({{ $nodo->id }})"
            class="rounded-md p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
            title="Editar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        <button
            wire:click="eliminarNodo({{ $nodo->id }})"
            wire:confirm="¿Eliminar este nodo y sus hijos?"
            class="rounded-md p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-500 transition-colors"
            title="Eliminar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
</div>
