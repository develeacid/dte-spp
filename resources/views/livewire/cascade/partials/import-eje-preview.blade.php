<div class="border-l-4 border-amber-400 pl-3 py-1" x-data="{ expanded: true }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                {{ $eje['numero'] }}
            </span>
            <span class="text-sm font-medium text-gray-800">{{ $eje['nombre'] }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ count($eje['temas'] ?? []) }} temas</span>
    </div>

    <div x-show="expanded" class="ml-4 mt-1 space-y-1">
        @foreach($eje['temas'] ?? [] as $tema)
            @include('livewire.cascade.partials.import-tema-preview', ['tema' => $tema])
        @endforeach
    </div>
</div>
