@props([
    'model',
    'placeholder' => 'Todos los trimestres',
])

<select
    wire:model.live="{{ $model }}"
    class="rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
>
    <option value="">{{ $placeholder }}</option>
    <option value="1">T1</option>
    <option value="2">T2</option>
    <option value="3">T3</option>
    <option value="4">T4</option>
</select>
