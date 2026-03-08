@props([
    'id',
    'title'       => '¿Confirmar acción?',
    'message'     => 'Esta acción no se puede deshacer.',
    'confirmText' => 'Confirmar',
    'cancelText'  => 'Cancelar',
    'confirmUrl'  => null,
    'method'      => 'DELETE',
])

<div x-data="{ open: false }"
     x-on:open-confirm-{{ $id }}.window="open = true"
     x-on:keydown.escape.window="open = false">

    {{-- Overlay + panel --}}
    <div x-show="open"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
         style="display: none;">

        {{-- Backdrop --}}
        <div class="fixed inset-0 bg-gray-500 opacity-75" @click="open = false"></div>

        {{-- Modal panel --}}
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative mb-6 bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-sm sm:mx-auto">

            <div class="px-6 py-4">
                <div class="text-lg font-medium text-gray-900">{{ $title }}</div>
                <div class="mt-4 text-sm text-gray-600">{{ $message }}</div>
            </div>

            <div class="flex flex-row justify-end px-6 py-4 bg-gray-100 space-x-3">
                <x-ui.button.secondary @click="open = false">
                    {{ $cancelText }}
                </x-ui.button.secondary>

                @if($confirmUrl)
                    <form method="POST" action="{{ $confirmUrl }}">
                        @csrf
                        @method($method)
                        <x-ui.button.danger type="submit">{{ $confirmText }}</x-ui.button.danger>
                    </form>
                @else
                    <x-ui.button.danger @click="$dispatch('confirmed-{{ $id }}'); open = false">
                        {{ $confirmText }}
                    </x-ui.button.danger>
                @endif
            </div>
        </div>
    </div>
</div>
