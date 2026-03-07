<div class="ml-4 mb-1 p-2 rounded hover:bg-gray-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <x-ui.badge color="gray">{{ $linea->clave_completa }}</x-ui.badge>
            <span class="text-sm text-gray-700">{{ Str::limit($linea->descripcion, 50) }}</span>
        </div>

        <a href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'linea', 'id' => $linea->id]) }}"
           class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
            Editar
        </a>
    </div>
</div>
