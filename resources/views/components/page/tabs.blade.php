@props([
    'tabs' => [],
    'active' => null,
    'model' => 'activeTab',
])

<div class="border-b border-slate-200 dark:border-slate-700 mb-4">
    <nav class="-mb-px flex gap-6" aria-label="Tabs">
        @foreach ($tabs as $key => $label)
            <button
                type="button"
                wire:click="$set('{{ $model }}', '{{ $key }}')"
                @class([
                    'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm',
                    'border-indigo-500 text-indigo-600 dark:text-indigo-400' => $active === $key,
                    'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 dark:text-slate-400' => $active !== $key,
                ])
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>
</div>
