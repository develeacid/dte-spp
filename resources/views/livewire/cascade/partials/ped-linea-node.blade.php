<div class="ml-4 mb-1 p-2 rounded hover:bg-gray-100">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
            <x-ui.badge color="gray">{{ $linea->clave_completa }}</x-ui.badge>
            <span class="text-sm text-gray-700">{{ Str::limit($linea->descripcion, 50) }}</span>
        </div>

        <x-ui.button.secondary href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'linea', 'id' => $linea->id]) }}" class="text-xs py-1 px-2">
            Editar
        </x-ui.button.secondary>
    </div>
</div>
