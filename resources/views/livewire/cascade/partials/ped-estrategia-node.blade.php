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

            <x-ui.badge color="yellow">{{ $estrategia->clave_completa }}</x-ui.badge>
            <span class="text-sm text-gray-700">{{ Str::limit($estrategia->descripcion, 50) }}</span>
        </div>

        <a href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'estrategia', 'id' => $estrategia->id]) }}"
           class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
            Editar
        </a>
    </div>

    <div x-show="expanded_{{ $estrategia->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Líneas de Acción ({{ $estrategia->lineasAccion->count() }})</span>
            <a href="{{ route('cascade.ped.nodo.create', ['tipo' => 'linea', 'parent_id' => $estrategia->id]) }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                + Línea
            </a>
        </div>

        @foreach($estrategia->lineasAccion as $linea)
            @include('livewire.cascade.partials.ped-linea-node', ['linea' => $linea])
        @endforeach
    </div>
</div>
