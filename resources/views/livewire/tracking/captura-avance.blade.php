<div>
    <x-page.header>
        <x-slot name="title">Captura de avance</x-slot>
    </x-page.header>

    <x-page.container>
        <x-tracking.avance-nav :avance="$avance" active="captura" />

        @if (session()->has('message'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        @if(session('sync_success'))
            <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded text-sm text-emerald-700">
                {{ session('sync_success') }}
            </div>
        @endif
        @if(session('sync_error'))
            <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded text-sm text-amber-700">
                {{ session('sync_error') }}
            </div>
        @endif

        {{-- Indicator info --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900">{{ $avance->indicador->nombre }}</h3>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Formula</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->indicador->formula_texto ?? 'Sin formula' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Meta del periodo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->metaPeriodo?->meta_periodo ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Sentido</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->indicador->sentido?->label() ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        @if($avance->estaCongelado() || ! $avance->estado->esEditable())
            <div class="mb-6 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                Este avance no se puede editar porque esta
                @if($avance->estaCongelado()) congelado @else en un estado no editable @endif.
            </div>
        @endif

        <form wire:submit="guardar" class="space-y-6">
            {{-- Variable inputs --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Variables del indicador</h3>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach($avance->indicador->variables as $variable)
                        @php
                            $avanceVar = $avance->variables->firstWhere('indicador_variable_id', $variable->id);
                            $isSynced = $avanceVar?->synced_from_geobase ?? false;
                        @endphp
                        <div>
                            <label for="var-{{ $variable->id }}" class="block text-sm font-medium text-gray-700">
                                {{ $variable->nombre }}
                                <span class="text-gray-400">({{ $variable->simbolo }})</span>
                            </label>
                            <div class="mt-1 flex items-center">
                                <input
                                    type="number"
                                    step="any"
                                    id="var-{{ $variable->id }}"
                                    wire:model="valores.{{ $variable->id }}"
                                    wire:change="calcular"
                                    @class([
                                        'block w-full rounded-md shadow-sm sm:text-sm',
                                        'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500' => !$isSynced,
                                        'border-emerald-300 bg-emerald-50 text-emerald-900 focus:border-emerald-500 focus:ring-emerald-500' => $isSynced,
                                    ])
                                    placeholder="Valor de {{ $variable->simbolo }}"
                                    @if($avance->estaCongelado() || ! $avance->estado->esEditable() || $isSynced) disabled @endif
                                />
                                @if($variable->hasGeoBaseLink())
                                    <button
                                        type="button"
                                        wire:click="sincronizarVariable({{ $variable->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="sincronizarVariable({{ $variable->id }})"
                                        class="ml-2 inline-flex items-center px-2 py-1 text-xs font-medium text-white bg-emerald-600 rounded hover:bg-emerald-700 disabled:opacity-50"
                                        @if($avance->estaCongelado() || !$avance->estado->esEditable()) disabled @endif
                                    >
                                        <svg wire:loading.remove wire:target="sincronizarVariable({{ $variable->id }})" class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                        </svg>
                                        <svg wire:loading wire:target="sincronizarVariable({{ $variable->id }})" class="w-3 h-3 mr-1 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                        </svg>
                                        {{ $isSynced ? 'Re-sincronizar' : 'Sincronizar' }}
                                    </button>
                                @endif
                            </div>
                            @if($isSynced && $avanceVar->synced_at)
                                <p class="mt-1 text-xs text-emerald-600">
                                    Valor de GeoBase &middot; {{ $avanceVar->synced_at->diffForHumans() }}
                                </p>
                            @endif
                            @error("valores.{$variable->id}")
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Result display --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Resultado calculado</h3>

                <div class="flex items-center gap-6">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Resultado</span>
                        <p class="mt-1 text-2xl font-bold text-gray-900">
                            {{ $resultado !== null ? number_format($resultado, 4) : '—' }}
                        </p>
                    </div>

                    @if($semaforoCalculado)
                        <div>
                            <span class="text-sm font-medium text-gray-500">Semaforo</span>
                            <div class="mt-1 flex items-center gap-2">
                                <span @class([
                                    'inline-block h-6 w-6 rounded-full',
                                    'bg-green-500' => $semaforoCalculado === 'verde',
                                    'bg-yellow-500' => $semaforoCalculado === 'amarillo',
                                    'bg-red-500' => $semaforoCalculado === 'rojo',
                                    'bg-purple-500' => $semaforoCalculado === 'rojo_alto',
                                ])></span>
                                <span class="text-sm font-medium text-gray-700">
                                    @if($semaforoCalculado === 'rojo_alto')
                                        Rojo alto — sobrecumplimiento
                                    @else
                                        {{ ucfirst($semaforoCalculado) }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Structured deviation analysis --}}
            @if(in_array($semaforoCalculado, ['amarillo', 'rojo', 'rojo_alto']))
                <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Análisis de desviación</h3>
                        @unless($avance->estaCongelado() || ! $avance->estado->esEditable())
                            <button
                                type="button"
                                wire:click="generarJustificacionIa"
                                wire:loading.attr="disabled"
                                wire:target="generarJustificacionIa"
                                class="inline-flex items-center rounded-md bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-200"
                            >
                                <div wire:loading wire:target="generarJustificacionIa" class="mr-2">
                                    <svg class="h-4 w-4 animate-spin text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                </div>
                                {{ $justificacionIa ? 'Regenerar con IA' : 'Generar con IA' }}
                            </button>
                        @endunless
                    </div>

                    @if($justificacionIa)
                        <p class="mb-2 text-xs text-gray-400">
                            Borrador generado por IA pre-cargado en "Causa raíz". Revise y edite antes de guardar.
                        </p>
                    @endif

                    <p class="mb-4 text-sm text-gray-500">
                        Cuando el semáforo es amarillo, rojo o rojo alto es obligatorio documentar el análisis estructurado de la desviación.
                    </p>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="analisis-dato" class="block text-sm font-medium text-gray-700">Dato (qué ocurrió)</label>
                            <textarea
                                id="analisis-dato"
                                wire:model="analisis.dato"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Describa el resultado obtenido frente a la meta..."
                                @if($avance->estaCongelado() || ! $avance->estado->esEditable()) disabled @endif
                            ></textarea>
                            @error('analisis.dato')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="analisis-causa" class="block text-sm font-medium text-gray-700">Causa raíz</label>
                            <textarea
                                id="analisis-causa"
                                wire:model="analisis.causa"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Explique por qué ocurrió la desviación..."
                                @if($avance->estaCongelado() || ! $avance->estado->esEditable()) disabled @endif
                            ></textarea>
                            @error('analisis.causa')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="analisis-accion" class="block text-sm font-medium text-gray-700">Acción correctiva</label>
                            <textarea
                                id="analisis-accion"
                                wire:model="analisis.accion"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Indique qué se hará para corregir la desviación..."
                                @if($avance->estaCongelado() || ! $avance->estado->esEditable()) disabled @endif
                            ></textarea>
                            @error('analisis.accion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="analisis-proyeccion" class="block text-sm font-medium text-gray-700">Proyección</label>
                            <textarea
                                id="analisis-proyeccion"
                                wire:model="analisis.proyeccion"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Describa qué se espera lograr con las acciones..."
                                @if($avance->estaCongelado() || ! $avance->estado->esEditable()) disabled @endif
                            ></textarea>
                            @error('analisis.proyeccion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            @unless($avance->estaCongelado() || ! $avance->estado->esEditable())
                <div class="flex justify-end">
                    <button
                        type="submit"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        <div wire:loading wire:target="guardar" class="mr-2">
                            <svg class="h-4 w-4 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        </div>
                        Guardar avance
                    </button>
                </div>
            @endunless
        </form>
    </x-page.container>
</div>
