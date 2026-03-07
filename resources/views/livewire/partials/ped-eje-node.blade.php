<div class="ml-4 mb-2" x-data="{ expanded_{{ $eje->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $eje->id }} = !expanded_{{ $eje->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $eje->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                {{ $eje->numero }}
            </span>
            <span class="text-sm font-medium text-gray-800">{{ $eje->nombre }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <button @click.stop="$dispatch('edit-nodo', { tipo: 'eje', nodoId: {{ $eje->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-900">
                Editar
            </button>
        </div>
    </div>

    <div x-show="expanded_{{ $eje->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Temas ({{ $eje->temas->count() }})</span>
            <button @click="$dispatch('create-nodo', { tipo: 'tema', parentId: {{ $eje->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Tema
            </button>
        </div>

        @foreach($eje->temas as $tema)
            @include('livewire.partials.ped-tema-node', ['tema' => $tema])
        @endforeach
    </div>
</div>
