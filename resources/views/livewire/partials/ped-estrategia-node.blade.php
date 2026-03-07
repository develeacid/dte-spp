<div class="ml-4 mb-1" x-data="{ expanded_{{ $estrategia->id }}: false }">
    <div class="flex items-center justify-between p-2 rounded hover:bg-gray-100 cursor-pointer"
         @click="expanded_{{ $estrategia->id }} = !expanded_{{ $estrategia->id }}">

        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200"
                  :class="expanded_{{ $estrategia->id }} ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>

            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                {{ $estrategia->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($estrategia->descripcion, 50) }}</span>
        </div>

        <button @click.stop="$dispatch('edit-nodo', { tipo: 'estrategia', nodoId: {{ $estrategia->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>

    <div x-show="expanded_{{ $estrategia->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Líneas de Acción ({{ $estrategia->lineasAccion->count() }})</span>
            <button @click="$dispatch('create-nodo', { tipo: 'linea', parentId: {{ $estrategia->id }} })"
                    class="text-xs text-indigo-600 hover:text-indigo-800">
                + Línea
            </button>
        </div>

        @foreach($estrategia->lineasAccion as $linea)
            @include('livewire.partials.ped-linea-node', ['linea' => $linea])
        @endforeach
    </div>
</div>
