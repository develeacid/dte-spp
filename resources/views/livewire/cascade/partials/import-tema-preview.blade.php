<div class="border-l-4 border-purple-400 pl-3 py-1" x-data="{ expanded: false }">
    <div class="flex items-center justify-between cursor-pointer" @click="expanded = !expanded">
        <div class="flex items-center space-x-2">
            <span class="transform transition-transform duration-200" :class="expanded ? 'rotate-90' : ''">
                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                {{ $tema['numero'] }}
            </span>
            <span class="text-sm text-gray-700">{{ $tema['nombre'] }}</span>
        </div>
        <span class="text-xs text-gray-400">{{ count($tema['objetivos'] ?? []) }} obj.</span>
    </div>

    <div x-show="expanded" class="ml-4 mt-1 space-y-1">
        @foreach($tema['objetivos'] ?? [] as $objetivo)
            <div class="border-l-4 border-emerald-400 pl-3 py-1">
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-100 text-emerald-800">
                        {{ $objetivo['clave'] }}
                    </span>
                    <span class="text-sm text-gray-700">{{ Str::limit($objetivo['descripcion'], 40) }}</span>
                </div>
                @if(!empty($objetivo['estrategias']))
                    <div class="ml-4 mt-1 space-y-1">
                        @foreach($objetivo['estrategias'] as $estrategia)
                            <div class="border-l-4 border-cyan-400 pl-3 py-1">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800">
                                        {{ $estrategia['clave'] }}
                                    </span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($estrategia['descripcion'], 40) }}</span>
                                </div>
                                @if(!empty($estrategia['lineas']))
                                    <div class="ml-4 mt-1 space-y-1">
                                        @foreach($estrategia['lineas'] as $linea)
                                            <div class="flex items-center space-x-2 text-xs text-gray-600">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-rose-100 text-rose-800">
                                                    {{ $linea['clave'] }}
                                                </span>
                                                {{ Str::limit($linea['descripcion'], 35) }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
