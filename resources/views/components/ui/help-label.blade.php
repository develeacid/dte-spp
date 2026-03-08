@props([
    'for' => null,
    'glossary' => null,
    'help' => null,
    'position' => 'top',
])

@php
    $helpText = $help ?? ($glossary ? config("glosario.{$glossary}", '') : '');
@endphp

<label {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm font-medium text-gray-700']) }} @if($for) for="{{ $for }}" @endif>
    {{ $slot }}

    @if ($helpText)
        <x-ui.tooltip :text="$helpText" :position="$position">
            <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-gray-200 text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 cursor-help transition-colors" tabindex="0">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 18.75h.007v.008H12v-.008z" />
                </svg>
            </span>
        </x-ui.tooltip>
    @endif
</label>
