@php
    $pasos = [
        1 => 'Subir archivo',
        2 => 'Completar huecos',
        3 => 'Vincular planes',
        4 => 'Calendarizar',
    ];
@endphp

<nav class="mb-8" aria-label="Progreso de importación">
    <ol class="flex items-center w-full">
        @foreach ($pasos as $numero => $label)
            @php
                $completado = $numero < $pasoActual;
                $actual = $numero === $pasoActual;
                $pendiente = $numero > $pasoActual;

                $circleClass = match (true) {
                    $completado => 'bg-green-600 text-white',
                    $actual => 'bg-blue-600 text-white',
                    default => 'bg-gray-200 text-gray-500',
                };

                $labelClass = match (true) {
                    $completado => 'text-green-700 font-medium',
                    $actual => 'text-blue-700 font-semibold',
                    default => 'text-gray-400',
                };
            @endphp

            <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex flex-col items-center">
                    <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $circleClass }}">
                        @if ($completado)
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            {{ $numero }}
                        @endif
                    </span>
                    <span class="mt-1 text-xs {{ $labelClass }} whitespace-nowrap">{{ $label }}</span>
                </div>

                @unless ($loop->last)
                    <div class="flex-1 mx-2 h-0.5 {{ $completado ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
