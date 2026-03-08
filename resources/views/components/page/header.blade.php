@props(['title', 'subtitle' => null])

<div class="md:flex md:items-center md:justify-between mb-6">
    <div class="flex-1 min-w-0">
        <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            {{ $title }}
        </h1>
        @if($subtitle)
            <p class="mt-1 text-sm text-gray-500">{{ $subtitle }}</p>
        @endif
    </div>

    @if($slot->isNotEmpty())
        <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
            {{ $slot }}
        </div>
    @endif
</div>
