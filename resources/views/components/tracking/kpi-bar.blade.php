@props([
    'stats' => [],
])

@php
    $colsClass = match (count($stats)) {
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        5 => 'md:grid-cols-5',
        6 => 'md:grid-cols-6',
        default => 'md:grid-cols-4',
    };

    $valueClassFor = fn (?string $color): string => match ($color) {
        'green' => 'text-green-600',
        'blue' => 'text-blue-600',
        'red' => 'text-red-600',
        'amber', 'yellow' => 'text-yellow-600',
        default => 'text-slate-900 dark:text-slate-100',
    };
@endphp

<div class="fixed bottom-0 right-0 left-0 z-30 py-3 px-4 sm:px-6 lg:px-8 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-t border-slate-200 dark:border-slate-700"
     :class="collapsed ? 'lg:!left-[var(--sidebar-collapsed-width)]' : 'lg:!left-[var(--sidebar-width)]'">
    <div class="grid grid-cols-2 {{ $colsClass }} gap-3">
        @foreach ($stats as $stat)
            <div class="rounded-md border border-slate-200 bg-white p-2 dark:border-slate-700 dark:bg-slate-800">
                <div class="text-xs text-slate-500">{{ $stat['label'] ?? '' }}</div>
                <div class="text-xl font-bold {{ $valueClassFor($stat['color'] ?? null) }}">{{ $stat['value'] ?? '' }}</div>
            </div>
        @endforeach
    </div>
</div>
