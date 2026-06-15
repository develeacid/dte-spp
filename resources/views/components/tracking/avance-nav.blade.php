@props([
    'avance',
    'active' => 'captura',
])

@php
    $tieneResultado = $avance->resultado !== null;
    $numEvidencias = $avance->evidencias()->count();

    $pasos = [
        'captura' => [
            'n' => 1,
            'label' => 'Captura',
            'href' => route('tracking.captura', $avance),
            'estado' => $tieneResultado ? 'resultado registrado' : 'pendiente',
            'completo' => $tieneResultado,
        ],
        'evidencias' => [
            'n' => 2,
            'label' => 'Evidencias',
            'href' => route('tracking.evidencia.index', $avance),
            'estado' => $numEvidencias === 1 ? '1 archivo' : "{$numEvidencias} archivos",
            'completo' => $numEvidencias > 0,
        ],
        'revisar' => [
            'n' => 3,
            'label' => 'Revisar',
            'href' => route('tracking.flujo', $avance),
            'estado' => null, // se muestra como badge de estado del avance
            'completo' => false,
        ],
    ];
@endphp

<nav aria-label="Etapas del avance" class="mb-6 rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <ol class="flex flex-col divide-y divide-slate-200 sm:flex-row sm:divide-x sm:divide-y-0 dark:divide-slate-700">
        @foreach ($pasos as $key => $paso)
            @php $esActivo = $active === $key; @endphp
            <li class="flex-1">
                <a
                    href="{{ $paso['href'] }}"
                    wire:navigate
                    @class([
                        'group flex items-center gap-3 px-4 py-3 transition-colors',
                        'border-b-2 sm:border-b-2 border-indigo-500 bg-indigo-50/60 dark:bg-indigo-500/10' => $esActivo,
                        'border-b-2 border-transparent hover:bg-slate-50 dark:hover:bg-slate-700/40' => ! $esActivo,
                    ])
                    @if ($esActivo) aria-current="step" @endif
                >
                    {{-- Indicador numérico / check --}}
                    <span @class([
                        'flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-indigo-600 text-white' => $esActivo,
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' => ! $esActivo && $paso['completo'],
                        'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400' => ! $esActivo && ! $paso['completo'],
                    ])>
                        @if (! $esActivo && $paso['completo'])
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        @else
                            {{ $paso['n'] }}
                        @endif
                    </span>

                    <span class="min-w-0">
                        <span @class([
                            'block text-sm font-medium',
                            'text-indigo-700 dark:text-indigo-300' => $esActivo,
                            'text-slate-900 dark:text-slate-100' => ! $esActivo,
                        ])>{{ $paso['label'] }}</span>

                        @if ($key === 'revisar')
                            <span class="mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $avance->estado->colorClass() }}">
                                {{ $avance->estado->label() }}
                            </span>
                        @else
                            <span @class([
                                'block truncate text-xs',
                                'text-emerald-600 dark:text-emerald-400' => $paso['completo'],
                                'text-slate-400 dark:text-slate-500' => ! $paso['completo'],
                            ])>{{ $paso['estado'] }}</span>
                        @endif
                    </span>

                    @unless ($loop->last)
                        <svg class="ml-auto hidden h-5 w-5 shrink-0 text-slate-300 sm:block dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    @endunless
                </a>
            </li>
        @endforeach
    </ol>
</nav>
