@props([
    'programa',
    'pasoActual' => 1,
])

@php
    $pasos = [
        1 => 'Problema',
        2 => 'Árbol −',
        3 => 'Árbol +',
        4 => 'Alternativa',
        5 => 'Poblaciones',
        6 => 'Alineación',
    ];

    // Calcular estado de cada paso consultando la DB
    $arbolProblema = $programa->arboles()->where('tipo', 'problema')->first();
    $problemaGuardado = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'problema_central')->whereNotNull('descripcion')->exists()
        : false;

    $tieneCausas = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'causa_directa')->exists()
        : false;
    $tieneEfectos = $arbolProblema
        ? $arbolProblema->nodos()->where('tipo_nodo', 'efecto_directo')->exists()
        : false;

    $arbolObjetivos = $programa->arboles()->where('tipo', 'objetivos')->first();
    $sinPendientes = $arbolObjetivos
        ? ! $arbolObjetivos->nodos()->where('descripcion', 'like', '[Pendiente%')->exists()
        : false;

    $tieneAlternativaSeleccionada = $programa->alternativas()->where('seleccionada', true)->exists();

    $tienePoblacion = $programa->poblacion()->exists();

    $tieneAlineacion = $programa->mirNiveles()
        ->where('tipo_nivel', 'fin')
        ->whereNotNull('ped_objetivo_estrategico_id')
        ->exists();

    $completado = [
        1 => $problemaGuardado,
        2 => $tieneCausas && $tieneEfectos,
        3 => $arbolObjetivos && $sinPendientes,
        4 => $tieneAlternativaSeleccionada,
        5 => $tienePoblacion,
        6 => $tieneAlineacion,
    ];

    // Un paso es accesible si el anterior está completado (o es el paso 1)
    $accesible = [1 => true];
    for ($i = 2; $i <= 6; $i++) {
        $accesible[$i] = $completado[$i - 1];
    }

    $rutaPaso = [
        1 => route('mml.etapa1', $programa),
        2 => route('mml.etapa2', $programa),
        3 => route('mml.etapa3', $programa),
        4 => route('mml.etapa4', $programa),
        5 => route('mml.etapa5', $programa),
        6 => route('mml.etapa6', $programa),
    ];
@endphp

<nav class="mb-8" aria-label="Progreso de planeación">
    <ol class="flex items-center w-full">
        @foreach ($pasos as $numero => $label)
            @php
                $esCompletado = $completado[$numero];
                $esActual = $numero === $pasoActual;
                $esAccesible = $accesible[$numero];

                $circleClass = match (true) {
                    $esCompletado => 'bg-green-600 text-white',
                    $esActual     => 'bg-blue-600 text-white',
                    $esAccesible  => 'bg-gray-200 text-gray-500',
                    default       => 'bg-gray-100 text-gray-400',
                };

                $labelClass = match (true) {
                    $esCompletado => 'text-green-700 font-medium',
                    $esActual     => 'text-blue-700 font-semibold',
                    $esAccesible  => 'text-gray-500',
                    default       => 'text-gray-400',
                };

                $clickable = ($esCompletado || $esAccesible) && ! $esActual;
            @endphp

            <li class="flex items-center {{ $loop->last ? '' : 'flex-1' }}">
                <div class="flex flex-col items-center">
                    @if ($clickable)
                        <a href="{{ $rutaPaso[$numero] }}" class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $circleClass }} hover:ring-2 hover:ring-offset-1 hover:ring-indigo-300 transition-shadow">
                    @else
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium {{ $circleClass }}">
                    @endif
                        @if ($esCompletado)
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        @elseif (! $esAccesible && ! $esActual)
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        @else
                            {{ $numero }}
                        @endif
                    @if ($clickable)
                        </a>
                    @else
                        </span>
                    @endif
                    <span class="mt-1 text-xs {{ $labelClass }} whitespace-nowrap">{{ $label }}</span>
                </div>

                @unless ($loop->last)
                    <div class="flex-1 mx-2 h-0.5 {{ $esCompletado ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
