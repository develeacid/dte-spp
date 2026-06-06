@props(['trazabilidad'])

<span
    class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-700 ring-1 ring-inset ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700"
    title="{{ $trazabilidad->nivel() }} — Programa {{ $trazabilidad->programa() }}"
>
    {{ $trazabilidad->clave() }}
</span>
