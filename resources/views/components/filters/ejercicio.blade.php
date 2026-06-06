@props([
    'model',
    'from' => 2024,
    'to' => null,
    'placeholder' => 'Todos los ejercicios',
])

@php $to = $to ?? now()->year; @endphp

<select
    wire:model.live="{{ $model }}"
    class="rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
>
    <option value="">{{ $placeholder }}</option>
    @for ($y = $to; $y >= $from; $y--)
        <option value="{{ $y }}">{{ $y }}</option>
    @endfor
</select>
