<div class="ml-4 mb-1 p-2 rounded hover:bg-gray-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">
                {{ $linea->clave_completa }}
            </span>
            <span class="text-sm text-gray-700">{{ Str::limit($linea->descripcion, 50) }}</span>
        </div>

        <button @click="$dispatch('edit-nodo', { tipo: 'linea', nodoId: {{ $linea->id }} })"
                class="text-xs text-indigo-600 hover:text-indigo-900">
            Editar
        </button>
    </div>
</div>
