<div class="space-y-6">

    {{-- Mensaje de éxito --}}
    @if($showSuccess)
        <div class="bg-white shadow sm:rounded-lg p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">¡Importación Exitosa!</h3>
            <p class="text-gray-600 mb-6">{{ session('message') }}</p>
            <div class="flex justify-center space-x-4">
                <a href="{{ route('cascade.ped.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                    Ver Plan Importado
                </a>
                <button wire:click="nuevaImportacion"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 transition">
                    Nueva Importación
                </button>
            </div>
        </div>
    @else

    {{-- Información inicial --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex items-start space-x-4">
            <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-sm font-medium text-gray-900">Formato del Archivo</h3>
                <div class="mt-2 text-sm text-gray-600">
                    <p>El archivo Markdown debe seguir la siguiente estructura de headings:</p>
                    <ul class="mt-2 space-y-1 list-disc list-inside">
                        <li><code class="bg-gray-100 px-1 rounded">#</code> Plan (H1)</li>
                        <li><code class="bg-gray-100 px-1 rounded">##</code> Eje (H2)</li>
                        <li><code class="bg-gray-100 px-1 rounded">###</code> Tema (H3)</li>
                        <li><code class="bg-gray-100 px-1 rounded">####</code> Objetivo Estratégico (H4)</li>
                        <li><code class="bg-gray-100 px-1 rounded">#####</code> Estrategia (H5)</li>
                        <li><code class="bg-gray-100 px-1 rounded">-</code> Línea de Acción (lista)</li>
                    </ul>
                </div>
                <button wire:click="descargarEjemplo"
                        class="mt-3 inline-flex items-center text-sm text-indigo-600 hover:text-indigo-800">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Descargar archivo de ejemplo
                </button>
            </div>
        </div>
    </div>

    {{-- Formulario de carga --}}
    @if(!$showPreview)
        <div class="bg-white shadow sm:rounded-lg p-6">
            <div class="flex items-center justify-center w-full">
                <label for="dropzone-file"
                       class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 @error('archivo') border-red-300 @enderror">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                        @if($archivo)
                            <svg class="w-10 h-10 mb-3 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mb-2 text-sm text-gray-700 font-medium">{{ $archivo->getClientOriginalName() }}</p>
                            <p class="text-xs text-gray-500">{{ number_format($archivo->getSize() / 1024, 1) }} KB</p>
                        @else
                            <svg class="w-10 h-10 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="mb-2 text-sm text-gray-500">
                                <span class="font-semibold">Click para seleccionar</span> o arrastra y suelta
                            </p>
                            <p class="text-xs text-gray-500">Archivos Markdown (.md, .markdown, .txt) - Máx. 10MB</p>
                        @endif
                    </div>
                    <input id="dropzone-file"
                           type="file"
                           class="hidden"
                           wire:model="archivo"
                           accept=".md,.markdown,.txt" />
                </label>
            </div>
            @error('archivo')
                <p class="mt-2 text-sm text-red-600 text-center">{{ $message }}</p>
            @enderror
        </div>
    @endif

    {{-- Errores de parsing --}}
    @if(!empty($parseErrors))
        <div class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Se encontraron errores en el archivo</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach($parseErrors as $error)
                                <li>
                                    @if($error['line'] > 0)
                                        <span class="font-medium">Línea {{ $error['line'] }}:</span>
                                    @endif
                                    {{ $error['message'] }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <button wire:click="cancelar"
                            class="mt-3 text-sm font-medium text-red-600 hover:text-red-800">
                        Cargar otro archivo
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Previsualización --}}
    @if($showPreview && empty($parseErrors))

        {{-- Estadísticas --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Resumen de Importación</h3>
            <div class="grid grid-cols-2 md:grid-cols-6 gap-4">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stats['plan'] }}</div>
                    <div class="text-xs text-gray-500">Plan</div>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-amber-600">{{ $stats['ejes'] }}</div>
                    <div class="text-xs text-gray-500">Ejes</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600">{{ $stats['temas'] }}</div>
                    <div class="text-xs text-gray-500">Temas</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-emerald-600">{{ $stats['objetivos'] }}</div>
                    <div class="text-xs text-gray-500">Objetivos</div>
                </div>
                <div class="bg-cyan-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-cyan-600">{{ $stats['estrategias'] }}</div>
                    <div class="text-xs text-gray-500">Estrategias</div>
                </div>
                <div class="bg-rose-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-rose-600">{{ $stats['lineas'] }}</div>
                    <div class="text-xs text-gray-500">Líneas</div>
                </div>
            </div>
        </div>

        {{-- Formulario de edición --}}
        <div class="bg-white shadow sm:rounded-lg p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Datos del Plan</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <x-label for="planNombre" value="Nombre del Plan" />
                    <x-input id="planNombre"
                             type="text"
                             class="mt-1 block w-full"
                             wire:model="planNombre" />
                    @error('planNombre')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="planPeriodoInicio" value="Año de Inicio" />
                    <x-input id="planPeriodoInicio"
                             type="number"
                             class="mt-1 block w-full"
                             wire:model="planPeriodoInicio" />
                    @error('planPeriodoInicio')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <x-label for="planPeriodoFin" value="Año de Fin" />
                    <x-input id="planPeriodoFin"
                             type="number"
                             class="mt-1 block w-full"
                             wire:model="planPeriodoFin" />
                    @error('planPeriodoFin')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="flex items-center">
                        <x-checkbox wire:model="planActivo" />
                        <span class="ml-2 text-sm text-gray-700">
                            Establecer como plan activo (desactivará otros planes)
                        </span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Previsualización del árbol --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="p-4 border-b">
                <h3 class="text-lg font-medium text-gray-900">Estructura del Plan</h3>
                <p class="text-sm text-gray-500">Vista previa de la jerarquía detectada</p>
            </div>
            <div class="p-4 max-h-96 overflow-y-auto">
                @if(!empty($parsedData))
                    <div class="space-y-2">
                        @foreach($parsedData['ejes'] ?? [] as $eje)
                            @include('livewire.cascade.partials.import-eje-preview', ['eje' => $eje])
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-end space-x-4">
            <x-secondary-button wire:click="cancelar">
                Cancelar
            </x-secondary-button>
            <x-button wire:click="confirmarImportacion">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Confirmar Importación
            </x-button>
        </div>
    @endif

    @endif {{-- fin del else de showSuccess --}}
</div>
