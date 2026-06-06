@props([
    'model',
    'options' => [],
    'placeholder' => 'Todos los programas',
])

<select
    wire:model.live="{{ $model }}"
    class="rounded-md border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-800"
>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>
