<x-app-layout>
    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-6">
                {{ __('API Tokens') }}
            </h2>
            @livewire('api.api-token-manager')
        </div>
    </div>
</x-app-layout>
