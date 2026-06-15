@props([
    'programa',
    'active' => 'dashboard',
])

@php
    $tabs = [
        'dashboard' => [
            'label' => 'Dashboard MIR',
            'href' => route('tracking.dashboard-indicadores', $programa),
            'show' => true,
        ],
        'padron' => [
            'label' => 'Padrón',
            'href' => route('mml.padron', $programa),
            'show' => auth()->user()->can('ver_padron'),
        ],
        'cobertura' => [
            'label' => 'Cobertura',
            'href' => route('mml.cobertura', $programa),
            'show' => auth()->user()->can('ver_padron'),
        ],
    ];
@endphp

<div class="mb-6 border-b border-slate-200 dark:border-slate-700">
    <nav class="-mb-px flex gap-6" aria-label="Secciones del programa">
        @foreach ($tabs as $key => $tab)
            @continue(! $tab['show'])
            <a
                href="{{ $tab['href'] }}"
                wire:navigate
                @class([
                    'whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors',
                    'border-indigo-500 text-indigo-600 dark:text-indigo-400' => $active === $key,
                    'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => $active !== $key,
                ])
                @if ($active === $key) aria-current="page" @endif
            >
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
