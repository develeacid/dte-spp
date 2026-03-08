<div class="ped-tree" x-data="{ expanded: {{ json_encode($expandedNodes) }} }">

    @if($planes->isEmpty())
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay planes</h3>
            <p class="mt-1 text-sm text-gray-500">Comienza creando un nuevo Plan Estatal de Desarrollo.</p>
        </div>
    @endif

    @foreach($planes as $plan)
        <div class="border rounded-lg mb-4 {{ $plan->activo ? 'border-green-300 bg-green-50' : 'border-gray-200' }}">

            {{-- PLAN HEADER --}}
            <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50"
                 @click="expanded['plan-{{ $plan->id }}'] = !expanded['plan-{{ $plan->id }}']">

                <div class="flex items-center space-x-3">
                    <span class="transform transition-transform duration-200"
                          :class="expanded['plan-{{ $plan->id }}'] ? 'rotate-90' : ''">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </span>

                    <div>
                        <h3 class="font-semibold text-lg text-gray-900">{{ $plan->nombre }}</h3>
                        <p class="text-sm text-gray-500">
                            {{ $plan->periodo_inicio }} - {{ $plan->periodo_fin }}
                            @if($plan->activo)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                    Activo
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <x-ui.button.secondary href="{{ route('cascade.ped.plan.edit', $plan) }}" class="text-xs py-1 px-2">
                        Editar
                    </x-ui.button.secondary>
                </div>
            </div>

            {{-- PLAN CONTENT (EJES) --}}
            <div x-show="expanded['plan-{{ $plan->id }}']" class="border-t bg-gray-50">
                <div class="p-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">
                            Ejes <x-ui.badge color="gray">{{ $plan->ejes->count() }}</x-ui.badge>
                        </span>
                        <x-ui.button.secondary href="{{ route('cascade.ped.nodo.create', ['tipo' => 'eje', 'parent_id' => $plan->id]) }}"
                                               class="text-xs py-1 px-2">
                            + Eje
                        </x-ui.button.secondary>
                    </div>

                    @foreach($plan->ejes as $eje)
                        @include('livewire.cascade.partials.ped-eje-node', ['eje' => $eje, 'level' => 1])
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
</div>
