@props(['title' => null, 'description' => null])

<div class="bg-white shadow sm:rounded-lg mb-6">
    <div class="px-4 py-5 sm:p-6">
        @if($title)
            <div class="mb-4 border-b border-gray-200 pb-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900">{{ $title }}</h3>
                @if($description)
                    <p class="mt-1 text-sm text-gray-500">{{ $description }}</p>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-6 gap-6">
            {{ $slot }}
        </div>
    </div>
</div>
