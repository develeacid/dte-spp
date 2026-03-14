@props(['programa'])

@php
    $validacion = $programa->validacionJuridica;
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    {{-- Pills del checklist --}}
    <div class="flex gap-2">
        <span @class([
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $validacion?->tiene_facultad_ur,
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $validacion && !$validacion->tiene_facultad_ur,
            'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => !$validacion,
        ])>
            {!! $validacion?->tiene_facultad_ur ? '&#10003;' : ($validacion ? '&#10007;' : '—') !!}
            Facultad UR
        </span>

        <span @class([
            'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
            'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $validacion?->tiene_mandato_gasto,
            'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => $validacion && !$validacion->tiene_mandato_gasto,
            'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400' => !$validacion,
        ])>
            {!! $validacion?->tiene_mandato_gasto ? '&#10003;' : ($validacion ? '&#10007;' : '—') !!}
            Mandato
        </span>

        @if ($validacion?->tiene_rop !== null)
            <span @class([
                'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium',
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $validacion->tiene_rop,
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' => !$validacion->tiene_rop,
            ])>
                {!! $validacion->tiene_rop ? '&#10003;' : '&#10007;' !!}
                ROP
            </span>
        @endif
    </div>

    {{-- Link para Jurídico --}}
    @can('gestionar_sustento_legal')
        <a href="{{ route('juridico.programa', $programa) }}" class="text-xs text-brand hover:text-brand-dark">Gestionar sustento legal &rarr;</a>
    @endcan
</div>
