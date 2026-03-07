<div class="ml-4 mb-1" x-data="{ expanded_{{ $tema->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $tema->id }} = !expanded_{{ $tema->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $tema->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                {{ $tema->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ $tema->nombre }}</span>
        </div>

        <button @click.stop="$dispatch('edit-nodo', { tipo: 'tema', nodoId: {{ $tema->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $tema->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Objetivos ({{ $tema->objetivosEstrategicos->count() }})</span>
            <button @click="$dispatch('create-nodo', { tipo: 'objetivo', parentId: {{ $tema->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Objetivo
            </button>
        </div>

        @foreach($tema->objetivosEstrategicos as $objetivo)
            @include('livewire.partials.ped-objetivo-node', ['objetivo' => $objetivo])
        @endforeach
    </div>
</div>
