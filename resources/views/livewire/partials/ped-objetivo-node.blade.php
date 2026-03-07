<div class="ml-4 mb-1" x-data="{ expanded_{{ $objetivo->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $objetivo->id }} = !expanded_{{ $objetivo->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $objetivo->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                {{ $objetivo->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($objetivo->descripcion, 50) }}</span>
        </div>

        <button @click.stop="$dispatch('edit-nodo', { tipo: 'objetivo', nodoId: {{ $objetivo->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $objetivo->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Estrategias ({{ $objetivo->estrategias->count() }})</span>
            <button @click="$dispatch('create-nodo', { tipo: 'estrategia', parentId: {{ $objetivo->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Estrategia
            </button>
        </div>

        @foreach($objetivo->estrategias as $estrategia)
            @include('livewire.partials.ped-estrategia-node', ['estrategia' => $estrategia])
        @endforeach
    </div>
</div>
