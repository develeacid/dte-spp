@if(count($historial) > 0)
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-lg font-medium text-gray-900">Historial</h3>

        <div class="relative">
            {{-- Vertical line --}}
            <div class="absolute left-4 top-0 h-full w-0.5 bg-gray-200"></div>

            <div class="space-y-6">
                @foreach(array_reverse($historial) as $entrada)
                    <div class="relative flex gap-4 pl-10">
                        {{-- Dot --}}
                        <div @class([
                            'absolute left-2.5 top-1.5 h-3 w-3 rounded-full ring-2 ring-white',
                            'bg-green-500' => ($entrada['accion'] ?? '') === 'aprobado',
                            'bg-orange-500' => ($entrada['accion'] ?? '') === 'observado',
                            'bg-indigo-500' => ($entrada['accion'] ?? '') === 'en_revision',
                            'bg-blue-500' => ($entrada['accion'] ?? '') === 'en_captura',
                            'bg-red-500' => ($entrada['accion'] ?? '') === 'vencido',
                            'bg-gray-400' => !in_array($entrada['accion'] ?? '', ['aprobado', 'observado', 'en_revision', 'en_captura', 'vencido']),
                        ])></div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-900">
                                    {{ $entrada['usuario_nombre'] ?? 'Sistema' }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    {{ \Carbon\Carbon::parse($entrada['fecha'] ?? now())->diffForHumans() }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-sm text-gray-600">
                                {{ ($entrada['estado_anterior'] ?? '?') }} &rarr; {{ ($entrada['estado_nuevo'] ?? '?') }}
                            </p>
                            @if(!empty($entrada['observacion']))
                                <p class="mt-1 rounded-md bg-gray-50 p-2 text-sm text-gray-700">
                                    {{ $entrada['observacion'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@else
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
        <p class="text-sm text-gray-500">Sin historial de cambios de estado.</p>
    </div>
@endif
