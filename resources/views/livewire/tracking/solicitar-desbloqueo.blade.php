<div>
    <x-page.header>
        <x-slot name="title">Solicitar desbloqueo</x-slot>
    </x-page.header>

    <x-page.container>
        @if (session()->has('message'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Avance info --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="text-lg font-medium text-gray-900">{{ $avance->indicador->nombre }}</h3>
            <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $avance->estado->colorClass() }}">
                            {{ $avance->estado->label() }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Congelado desde</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->congelado_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Meta del periodo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $avance->metaPeriodo?->meta_periodo ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- Solicitud form --}}
        @if ($avance->estaCongelado())
            <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <h3 class="mb-4 text-lg font-medium text-gray-900">Nueva solicitud de desbloqueo</h3>

                <form wire:submit="solicitar">
                    <div class="mb-4">
                        <label for="motivo" class="block text-sm font-medium text-gray-700">
                            Motivo de la solicitud
                        </label>
                        <textarea
                            wire:model="motivo"
                            id="motivo"
                            rows="4"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            placeholder="Explique por que necesita desbloquear este avance (minimo 10 caracteres)..."
                        ></textarea>
                        @error('motivo')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                        >
                            Enviar solicitud
                        </button>
                    </div>
                </form>
            </div>
        @else
            <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800">
                Este avance no esta congelado. No es necesario solicitar un desbloqueo.
            </div>
        @endif

        {{-- Previous desbloqueos --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
            <h3 class="mb-4 text-lg font-medium text-gray-900">Historial de solicitudes</h3>

            @if ($desbloqueos->isEmpty())
                <p class="text-sm text-gray-500">No hay solicitudes de desbloqueo para este avance.</p>
            @else
                <div class="space-y-4">
                    @foreach ($desbloqueos as $desbloqueo)
                        <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span @class([
                                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                        'bg-yellow-100 text-yellow-800' => $desbloqueo->estado === 'pendiente',
                                        'bg-green-100 text-green-800' => $desbloqueo->estado === 'aprobado',
                                        'bg-red-100 text-red-800' => $desbloqueo->estado === 'rechazado',
                                    ])>
                                        {{ ucfirst($desbloqueo->estado) }}
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $desbloqueo->created_at->format('d/m/Y H:i') }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    Solicitado por: {{ $desbloqueo->solicitante?->name ?? '—' }}
                                </span>
                            </div>
                            <p class="mt-2 text-sm text-gray-700">{{ $desbloqueo->motivo }}</p>
                            @if ($desbloqueo->resolucion)
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium">Resolucion:</span> {{ $desbloqueo->resolucion }}
                                </p>
                            @endif
                            @if ($desbloqueo->resolutor)
                                <p class="mt-1 text-xs text-gray-400">
                                    Resuelto por: {{ $desbloqueo->resolutor->name }}
                                    el {{ $desbloqueo->resuelto_at?->format('d/m/Y H:i') }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-page.container>
</div>
