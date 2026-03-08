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

            <x-ui.badge color="purple">{{ $tema->clave_completa }}</x-ui.badge>
            <span class="text-sm text-gray-700">{{ $tema->nombre }}</span>
        </div>

        <x-ui.button.secondary href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'tema', 'id' => $tema->id]) }}" class="text-xs py-1 px-2">
            Editar
        </x-ui.button.secondary>
    </div>

    <div x-show="expanded_{{ $tema->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Objetivos ({{ $tema->objetivosEstrategicos->count() }})</span>
            <x-ui.button.secondary href="{{ route('cascade.ped.nodo.create', ['tipo' => 'objetivo', 'parent_id' => $tema->id]) }}" class="text-xs py-1 px-2">
                + Objetivo
            </x-ui.button.secondary>
        </div>

        @foreach($tema->objetivosEstrategicos as $objetivo)
            @include('livewire.cascade.partials.ped-objetivo-node', ['objetivo' => $objetivo])
        @endforeach
    </div>
</div>
