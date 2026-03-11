@props(['title', 'subtitle' => null])

<div class="md:flex md:items-center md:justify-between">
    <div class="min-w-0 flex-1">
        <h2 class="text-xl font-bold leading-7 text-gray-900 sm:text-2xl sm:truncate">
            {{ $title }}
        </h2>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500 hidden sm:block">{{ $subtitle }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        {{-- Desktop: inline actions --}}
        <div class="hidden md:flex md:mt-0 md:ml-4 space-x-3">
            {{ $slot }}
        </div>
    @endif
</div>
