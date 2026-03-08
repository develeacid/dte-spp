@php
    $tipoEnum = $nodo->tipo_nodo instanceof \App\Enums\TipoNodo
        ? $nodo->tipo_nodo
        : \App\Enums\TipoNodo::tryFrom($nodo->tipo_nodo);
    $colorClass = $tipoEnum ? $tipoEnum->colorClass() : 'bg-gray-100 text-gray-800 border-gray-300';
    $label = $tipoEnum ? $tipoEnum->label() : $nodo->tipo_nodo;
@endphp

<div class="rounded-md border {{ $colorClass }} p-3 flex items-start justify-between group">
    <div>
        <span class="text-xs font-semibold uppercase tracking-wide opacity-70">{{ $label }}</span>
        <p class="mt-0.5 text-sm">{{ $nodo->descripcion }}</p>
    </div>
    <div class="hidden group-hover:flex items-center gap-1">
        <button
            wire:click="editarNodo({{ $nodo->id }})"
            class="rounded p-1 text-gray-500 hover:bg-white hover:text-gray-700"
            title="Editar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        <button
            wire:click="eliminarNodo({{ $nodo->id }})"
            wire:confirm="¿Eliminar este nodo y sus hijos?"
            class="rounded p-1 text-gray-500 hover:bg-white hover:text-red-600"
            title="Eliminar"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
</div>
