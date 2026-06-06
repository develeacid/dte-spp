@props([
    'class' => '',
])

<div {{ $attributes->merge(['class' => "sticky top-0 z-30 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3 mb-4 bg-white/95 dark:bg-slate-900/95 backdrop-blur border-b border-slate-200 dark:border-slate-700 $class"]) }}>
    <div class="flex flex-wrap items-center gap-3">
        {{ $slot }}
    </div>
</div>
