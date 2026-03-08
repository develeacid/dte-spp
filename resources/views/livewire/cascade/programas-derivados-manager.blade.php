<div class="space-y-6">

    {{-- Error flash (success ya manejado por x-page.container) --}}
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Advertencia: sin PED activo --}}
    @if(!$this->planActivo)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        <strong>No hay Plan Estatal de Desarrollo activo.</strong>
                        Debe activar un PED antes de crear programas derivados.
                    </p>
                    <a href="{{ route('cascade.ped.index') }}" class="mt-2 inline-flex items-center text-sm text-yellow-700 underline hover:text-yellow-600">
                        Ir a gestión de PED
                        <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    @endif

    {{-- Header con Stats --}}
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Programas Derivados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Gestione los programas sectoriales, especiales, institucionales y regionales.
                </p>
            </div>

            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                @php($stats = $this->stats)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    {{ $stats['total'] }} programas
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ $stats['objetivos'] }} objetivos
                </span>
            </div>
        </div>

        {{-- Stats por tipo --}}
        <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['key' => 'sectoriales', 'tipo' => \App\Enums\TipoProgramaDerivado::SECTORIAL->value, 'color' => 'blue', 'label' => 'Sectoriales'],
                ['key' => 'especiales', 'tipo' => \App\Enums\TipoProgramaDerivado::ESPECIAL->value, 'color' => 'green', 'label' => 'Especiales'],
                ['key' => 'institucionales', 'tipo' => \App\Enums\TipoProgramaDerivado::INSTITUCIONAL->value, 'color' => 'purple', 'label' => 'Institucionales'],
                ['key' => 'regionales', 'tipo' => \App\Enums\TipoProgramaDerivado::REGIONAL->value, 'color' => 'orange', 'label' => 'Regionales'],
            ] as $item)
                <button wire:click="setFiltroTipo('{{ $item['tipo'] }}')"
                        class="p-4 rounded-lg border-2 transition-all text-left
                            {{ $filtroTipo === $item['tipo']
                                ? 'border-' . $item['color'] . '-500 bg-' . $item['color'] . '-50'
                                : 'border-gray-200 hover:border-gray-300' }}">
                    <div class="text-2xl font-bold text-gray-900">{{ $stats[$item['key']] }}</div>
                    <div class="text-sm text-gray-500">{{ $item['label'] }}</div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Filtros y Acciones --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">

        {{-- Filtro por tipo --}}
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-500">Filtrar:</span>
            <select wire:model.live="filtroTipo"
                    class="border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md text-sm">
                @foreach($this->tiposFiltro as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center space-x-3">
            <button wire:click="expandAll" class="text-sm text-gray-600 hover:text-gray-900">
                Expandir todos
            </button>
            <span class="text-gray-300">|</span>
            <button wire:click="collapseAll" class="text-sm text-gray-600 hover:text-gray-900">
                Contraer todos
            </button>
            <span class="text-gray-300">|</span>
            <x-ui.button.primary
                wire:click="createPrograma"
                :disabled="!$this->planActivo"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Nuevo Programa
            </x-ui.button.primary>
        </div>
    </div>

    {{-- Listado de Programas --}}
    <div class="space-y-4">
        @forelse($this->programas as $programa)
            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">

                {{-- Header del Programa --}}
                <div class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50"
                     wire:click="togglePrograma({{ $programa->id }})">

                    <div class="flex items-center space-x-4">
                        {{-- Icono expandir --}}
                        <span class="transform transition-transform duration-200 {{ isset($expandedProgramas[$programa->id]) ? 'rotate-90' : '' }}">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>

                        {{-- Badge de tipo --}}
                        <x-ui.badge :class="$programa->tipo->colorClass()">
                            {{ $programa->prefijoClave() }}
                        </x-ui.badge>

                        {{-- Nombre --}}
                        <div>
                            <h4 class="font-medium text-gray-900">{{ $programa->nombre }}</h4>
                            <p class="text-sm text-gray-500">
                                {{ $programa->tipo->label() }} · {{ $programa->objetivos->count() }} objetivos
                            </p>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div class="flex items-center space-x-3">
                        <x-ui.button.secondary wire:click.stop="editPrograma({{ $programa->id }})">
                            Editar
                        </x-ui.button.secondary>
                        <x-ui.button.danger wire:click.stop="confirmDeletePrograma({{ $programa->id }})">
                            Eliminar
                        </x-ui.button.danger>
                    </div>
                </div>

                {{-- Contenido expandido (Objetivos) --}}
                @if(isset($expandedProgramas[$programa->id]))
                    <div class="border-t bg-gray-50 p-4">

                        <div class="flex items-center justify-between mb-4">
                            <h5 class="text-sm font-medium text-gray-700">Objetivos</h5>
                            <button wire:click="createObjetivo({{ $programa->id }})"
                                    class="inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Agregar Objetivo
                            </button>
                        </div>

                        @if($programa->objetivos->isEmpty())
                            <p class="text-sm text-gray-500 text-center py-4">
                                Este programa no tiene objetivos. Agregue el primero.
                            </p>
                        @else
                            <div class="space-y-2">
                                @foreach($programa->objetivos as $objetivo)
                                    <div class="flex items-center justify-between p-3 bg-white rounded border hover:shadow-sm transition">
                                        <div class="flex items-center space-x-3">
                                            <x-ui.badge class="bg-indigo-100 text-indigo-800">
                                                {{ $programa->prefijoClave() }}.{{ $objetivo->clave }}
                                            </x-ui.badge>
                                            <span class="text-sm text-gray-700">{{ Str::limit($objetivo->descripcion, 80) }}</span>
                                        </div>

                                        <div class="flex items-center space-x-2">
                                            <button wire:click="editObjetivo({{ $objetivo->id }})"
                                                    class="text-xs text-indigo-600 hover:text-indigo-900 font-medium">
                                                Editar
                                            </button>
                                            <button wire:click="deleteObjetivo({{ $objetivo->id }})"
                                                    wire:confirm="¿Eliminar este objetivo?"
                                                    class="text-xs text-red-600 hover:text-red-900 font-medium">
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-lg border border-gray-200 p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No hay programas derivados</h3>
                <p class="mt-1 text-sm text-gray-500">
                    @if($filtroTipo !== 'todos')
                        No se encontraron programas del tipo seleccionado.
                        <button wire:click="setFiltroTipo('todos')" class="ml-1 text-indigo-600 underline">Ver todos</button>
                    @else
                        Comience creando un nuevo programa derivado.
                    @endif
                </p>
                @if($this->planActivo && $filtroTipo === 'todos')
                    <x-ui.button.primary wire:click="createPrograma" class="mt-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Crear Primer Programa
                    </x-ui.button.primary>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Modal: Crear/Editar Programa --}}
    <x-dialog-modal wire:model="showProgramaModal" maxWidth="lg">
        <x-slot name="title">
            {{ $programaMode === 'create' ? 'Crear Programa Derivado' : 'Editar Programa Derivado' }}
        </x-slot>

        <x-slot name="content">
            <div class="space-y-4">
                <div>
                    <x-label for="programaNombre" value="Nombre del Programa" />
                    <x-input id="programaNombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="programaNombre"
                             placeholder="Ej: Programa Sectorial de Educación" />
                    @error('programaNombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="programaTipo" value="Tipo de Programa" />
                    <select id="programaTipo"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                            wire:model="programaTipo">
                        <option value="">Seleccione un tipo...</option>
                        @foreach(\App\Enums\TipoProgramaDerivado::cases() as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    @error('programaTipo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if($programaTipo)
                        <p class="mt-2 text-sm text-gray-500">
                            {{ \App\Enums\TipoProgramaDerivado::tryFrom($programaTipo)?->descripcion() }}
                        </p>
                    @endif
                </div>

                <div>
                    <x-label for="programaDescripcion" value="Descripción (opcional)" />
                    <textarea id="programaDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="programaDescripcion"
                              maxlength="1000"
                              placeholder="Descripción general del programa..."></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($programaDescripcion) }}/1000 caracteres</p>
                    @error('programaDescripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showProgramaModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="savePrograma" class="bg-indigo-600 text-white">
                {{ $programaMode === 'create' ? 'Crear Programa' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Crear/Editar Objetivo --}}
    <x-dialog-modal wire:model="showObjetivoModal" maxWidth="md">
        <x-slot name="title">
            {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Editar Objetivo' }}
        </x-slot>

        <x-slot name="content">
            @if($programaSeleccionado)
                <p class="mb-4 text-sm text-gray-600">
                    Programa: <strong>{{ $programaSeleccionado->nombre }}</strong>
                </p>
            @endif

            <div class="space-y-4">
                <div>
                    <x-label for="objetivoClave" value="Clave del Objetivo" />
                    <div class="mt-1 flex items-center space-x-2">
                        @if($programaSeleccionado)
                            <span class="text-gray-500 font-medium">{{ $programaSeleccionado->prefijoClave() }}.</span>
                        @endif
                        <x-input id="objetivoClave"
                                 type="text"
                                 class="flex-1"
                                 wire:model="objetivoClave"
                                 placeholder="1, 2, 1.1" />
                    </div>
                    @error('objetivoClave')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="objetivoDescripcion" value="Descripción del Objetivo" />
                    <textarea id="objetivoDescripcion"
                              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                              rows="3"
                              wire:model="objetivoDescripcion"
                              maxlength="500"></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($objetivoDescripcion) }}/500 caracteres</p>
                    @error('objetivoDescripcion')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showObjetivoModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="saveObjetivo" class="bg-indigo-600 text-white">
                {{ $objetivoMode === 'create' ? 'Crear Objetivo' : 'Guardar Cambios' }}
            </x-button>
        </x-slot>
    </x-dialog-modal>

    {{-- Modal: Confirmar Eliminación --}}
    <x-dialog-modal wire:model="showDeleteModal" maxWidth="sm">
        <x-slot name="title">
            Confirmar Eliminación
        </x-slot>

        <x-slot name="content">
            @if($programaSeleccionado)
                <p class="text-gray-700">
                    ¿Está seguro de eliminar el programa <strong>"{{ $programaSeleccionado->nombre }}"</strong>?
                </p>

                @if(isset($programaSeleccionado->objetivos_count) && $programaSeleccionado->objetivos_count > 0)
                    <div class="mt-4 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <p class="ml-3 text-sm text-yellow-700">
                                Este programa tiene <strong>{{ $programaSeleccionado->objetivos_count }} objetivos</strong>
                                que serán eliminados también.
                            </p>
                        </div>
                    </div>
                @endif
            @endif
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$set('showDeleteModal', false)" class="mr-3">
                Cancelar
            </x-secondary-button>
            <x-danger-button wire:click="deletePrograma">
                Eliminar Programa
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

</div>
