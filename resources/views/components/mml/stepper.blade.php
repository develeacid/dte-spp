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

<nav aria-label="Progreso de planeación" class="mb-6">
    <ol class="flex items-center justify-between sm:justify-start sm:space-x-0 w-full">
        @foreach($pasos as $numero => $label)
            <li class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                @if($accesible[$numero])
                    <a href="{{ $rutaPaso[$numero] }}"
                       class="flex flex-col items-center group">
                @else
                    <span class="flex flex-col items-center opacity-50 cursor-not-allowed">
                @endif

                    <span class="flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10 rounded-full text-xs sm:text-sm font-semibold transition-all
                        {{ $completado[$numero] ? 'bg-green-600 text-white' : '' }}
                        {{ $pasoActual === $numero && !$completado[$numero] ? 'bg-blue-600 text-white ring-2 ring-blue-300' : '' }}
                        {{ !$completado[$numero] && $pasoActual !== $numero && $accesible[$numero] ? 'bg-gray-200 text-gray-600' : '' }}
                        {{ !$accesible[$numero] ? 'bg-gray-100 text-gray-400' : '' }}
                        {{ $accesible[$numero] ? 'group-hover:ring-2 group-hover:ring-offset-1 group-hover:ring-indigo-300' : '' }}">
                        @if($completado[$numero])
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                        @elseif(!$accesible[$numero])
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                        @else
                            {{ $numero }}
                        @endif
                    </span>

                    {{-- Label: always show on sm+, on mobile only show for current step --}}
                    <span class="mt-1 text-[10px] sm:text-xs whitespace-nowrap {{ $pasoActual === $numero ? '' : 'hidden sm:block' }} {{ $completado[$numero] ? 'text-green-700 font-medium' : 'text-gray-500' }}">
                        {{ $label }}
                    </span>

                @if($accesible[$numero])
                    </a>
                @else
                    </span>
                @endif

                {{-- Connector --}}
                @if(!$loop->last)
                    <div class="flex-1 mx-1 sm:mx-2 h-0.5 {{ $completado[$numero] ? 'bg-green-300' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
