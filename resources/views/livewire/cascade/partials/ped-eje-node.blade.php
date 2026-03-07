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

            <x-ui.badge color="blue">{{ $eje->numero }}</x-ui.badge>
            <span class="text-sm font-medium text-gray-800">{{ $eje->nombre }}</span>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route('cascade.ped.nodo.edit', ['tipo' => 'eje', 'id' => $eje->id]) }}"
               class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                Editar
            </a>
        </div>
    </div>

    <div x-show="expanded_{{ $eje->id }}" class="ml-6 mt-1">
        <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-gray-500">Temas ({{ $eje->temas->count() }})</span>
            <a href="{{ route('cascade.ped.nodo.create', ['tipo' => 'tema', 'parent_id' => $eje->id]) }}"
               class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                + Tema
            </a>
        </div>

        @foreach($eje->temas as $tema)
            @include('livewire.cascade.partials.ped-tema-node', ['tema' => $tema])
        @endforeach
    </div>
</div>
