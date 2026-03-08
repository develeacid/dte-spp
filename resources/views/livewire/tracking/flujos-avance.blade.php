<div>
    <x-page.header>
        <x-slot name="title">Flujo de avance</x-slot>
        <x-slot name="actions">
            <a href="{{ route('tracking.captura', $avance) }}"
               class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                Ver captura
            </a>
        </x-slot>
    </x-page.header>

    <x-page.container>
        @if (session()->has('message'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        {{-- Indicator info --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900">{{ $avance->indicador->nombre }}</h3>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Periodo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->metaPeriodo?->periodo ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Resultado</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->resultado !== null ? number_format((float) $avance->resultado, 4) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado actual</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $avance->estado->colorClass() }}">
                            {{ $avance->estado->label() }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Action buttons --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Acciones</h3>

            <div class="flex flex-wrap gap-3">
                @if($avance->estado === \App\Enums\EstadoAvance::EN_CAPTURA && auth()->user()->can('capturar_avance'))
                    <button
                        wire:click="enviarRevision"
                        wire:confirm="¿Enviar este avance a revisión?"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Enviar a revisión
                    </button>
                @endif

                @if($avance->estado === \App\Enums\EstadoAvance::EN_REVISION && auth()->user()->can('revisar_avance'))
                    <button
                        wire:click="aprobar"
                        wire:confirm="¿Aprobar este avance? Esta acción es irreversible."
                        class="inline-flex items-center rounded-md bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500"
                    >
                        Aprobar
                    </button>
                @endif

                @if($avance->estado === \App\Enums\EstadoAvance::OBSERVADO && auth()->user()->can('capturar_avance'))
                    <button
                        wire:click="corregir"
                        wire:confirm="¿Devolver a captura para corrección?"
                        class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500"
                    >
                        Corregir
                    </button>
                @endif

                @if($avance->estado === \App\Enums\EstadoAvance::APROBADO || $avance->estado === \App\Enums\EstadoAvance::VENCIDO)
                    <p class="text-sm text-gray-500">No hay acciones disponibles para este estado.</p>
                @endif
            </div>

            {{-- Observation form for planner --}}
            @if($avance->estado === \App\Enums\EstadoAvance::EN_REVISION && auth()->user()->can('revisar_avance'))
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <label for="observacion" class="block text-sm font-medium text-gray-700">Observación</label>
                    <textarea
                        wire:model="observacionTexto"
                        id="observacion"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        placeholder="Describa las observaciones para el operador..."
                    ></textarea>
                    @error('observacionTexto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="mt-3">
                        <button
                            wire:click="observar"
                            class="inline-flex items-center rounded-md bg-orange-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-orange-500"
                        >
                            Observar
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Timeline --}}
        @include('livewire.tracking.partials.timeline-observaciones', ['historial' => $avance->historial_observaciones ?? []])
    </x-page.container>
</div>
