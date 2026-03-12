@props([
    'programa',
    'pasoActual' => 1,
])

@php
    $pasos = [
        1 => ['label' => 'Problema', 'icon' => 'problem'],
        2 => ['label' => 'Árbol −', 'icon' => 'tree-minus'],
        3 => ['label' => 'Árbol +', 'icon' => 'tree-plus'],
        4 => ['label' => 'Alternativa', 'icon' => 'alternatives'],
        5 => ['label' => 'Poblaciones', 'icon' => 'funnel'],
        6 => ['label' => 'Alineación', 'icon' => 'alignment'],
    ];

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

    $totalCompletados = collect($completado)->filter()->count();
    $porcentaje = round(($totalCompletados / 6) * 100);
@endphp

<nav aria-label="Progreso de planeación" class="mb-6" x-data="{ pasoActual: {{ $pasoActual }} }">
    {{-- Progress bar --}}
    <div class="mb-3 flex items-center gap-3">
        <div class="flex-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-gradient-to-r from-blue-500 to-green-500 rounded-full transition-all duration-500"
                 style="width: {{ $porcentaje }}%"></div>
        </div>
        <span class="text-xs text-gray-500 font-medium tabular-nums whitespace-nowrap">{{ $totalCompletados }}/6</span>
    </div>

    {{-- Mobile: compact stepper with arrows --}}
    <div class="sm:hidden">
        <div class="flex items-center justify-between bg-white border border-gray-200 rounded-xl px-3 py-2.5 shadow-sm">
            {{-- Previous arrow --}}
            @if($pasoActual > 1)
                <a href="{{ $rutaPaso[$pasoActual - 1] }}"
                   class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
            @else
                <span class="p-1.5 text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></span>
            @endif

            {{-- Current step info --}}
            <div class="flex items-center gap-2.5">
                <span class="flex items-center justify-center w-8 h-8 rounded-full text-sm font-semibold
                    {{ $completado[$pasoActual] ? 'bg-green-100 text-green-700' : 'bg-blue-600 text-white' }}">
                    @if($completado[$pasoActual])
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    @else
                        {{ $pasoActual }}
                    @endif
                </span>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $pasos[$pasoActual]['label'] }}</p>
                    <p class="text-[10px] text-gray-500">Paso {{ $pasoActual }} de 6</p>
                </div>
            </div>

            {{-- Next arrow --}}
            @if($pasoActual < 6 && $accesible[$pasoActual + 1])
                <a href="{{ $rutaPaso[$pasoActual + 1] }}"
                   class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                <span class="p-1.5 text-gray-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></span>
            @endif
        </div>

        {{-- Mobile step dots --}}
        <div class="flex justify-center gap-1.5 mt-2">
            @foreach($pasos as $numero => $paso)
                <span class="w-1.5 h-1.5 rounded-full transition-all
                    {{ $completado[$numero] ? 'bg-green-500' : '' }}
                    {{ $pasoActual === $numero && !$completado[$numero] ? 'bg-blue-600 w-4' : '' }}
                    {{ !$completado[$numero] && $pasoActual !== $numero ? 'bg-gray-300' : '' }}
                "></span>
            @endforeach
        </div>
    </div>

    {{-- Desktop: full stepper --}}
    <ol class="hidden sm:flex items-center w-full">
        @foreach($pasos as $numero => $paso)
            <li class="flex items-center {{ !$loop->last ? 'flex-1' : '' }}">
                @if($accesible[$numero])
                    <a href="{{ $rutaPaso[$numero] }}"
                       class="relative flex flex-col items-center group"
                       title="{{ $paso['label'] }}">
                @else
                    <span class="relative flex flex-col items-center cursor-not-allowed"
                          title="Completa el paso anterior para desbloquear">
                @endif

                    {{-- Step circle --}}
                    <span class="flex items-center justify-center w-10 h-10 rounded-full text-sm font-semibold
                        transition-all duration-200 ease-in-out
                        {{ $completado[$numero]
                            ? 'bg-green-100 text-green-700 ring-2 ring-green-200'
                            : '' }}
                        {{ $pasoActual === $numero && !$completado[$numero]
                            ? 'bg-blue-600 text-white ring-2 ring-blue-300 shadow-md shadow-blue-200'
                            : '' }}
                        {{ !$completado[$numero] && $pasoActual !== $numero && $accesible[$numero]
                            ? 'bg-gray-100 text-gray-500 ring-1 ring-gray-200'
                            : '' }}
                        {{ !$accesible[$numero]
                            ? 'bg-gray-50 text-gray-300 ring-1 ring-gray-100'
                            : '' }}
                        {{ $accesible[$numero]
                            ? 'group-hover:scale-110 group-hover:shadow-md'
                            : '' }}">

                        @if($completado[$numero])
                            {{-- Checkmark --}}
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        @elseif(!$accesible[$numero])
                            {{-- Lock --}}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                            </svg>
                        @else
                            {{-- Step icon based on type --}}
                            @switch($paso['icon'])
                                @case('problem')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                                    </svg>
                                @break
                                @case('tree-minus')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6z"/>
                                    </svg>
                                @break
                                @case('tree-plus')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25a2.25 2.25 0 01-2.25-2.25v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6z"/>
                                    </svg>
                                @break
                                @case('alternatives')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                                    </svg>
                                @break
                                @case('funnel')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-15l-3 4.5-1.5 2.25L12 19.5l-3-4.25L7.5 13l-3-4.5L12 4.5z"/>
                                    </svg>
                                @break
                                @case('alignment')
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                                    </svg>
                                @break
                            @endswitch
                        @endif
                    </span>

                    {{-- Label --}}
                    <span class="mt-1.5 text-xs font-medium whitespace-nowrap transition-colors
                        {{ $completado[$numero] ? 'text-green-700' : '' }}
                        {{ $pasoActual === $numero && !$completado[$numero] ? 'text-blue-700 font-semibold' : '' }}
                        {{ !$completado[$numero] && $pasoActual !== $numero && $accesible[$numero] ? 'text-gray-500' : '' }}
                        {{ !$accesible[$numero] ? 'text-gray-300' : '' }}">
                        {{ $paso['label'] }}
                    </span>

                @if($accesible[$numero])
                    </a>
                @else
                    </span>
                @endif

                {{-- Connector line --}}
                @if(!$loop->last)
                    <div class="flex-1 mx-2 h-0.5 rounded-full transition-colors duration-300
                        {{ $completado[$numero] ? 'bg-green-300' : 'bg-gray-200' }}"></div>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
