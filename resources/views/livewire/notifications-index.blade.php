<div>
    <x-page.container title="Notificaciones">
        <x-slot name="actions">
            <button wire:click="markAllAsRead" class="text-sm text-brand hover:underline">
                Marcar todo como leido
            </button>
        </x-slot>

        {{-- Filter tabs --}}
        <div class="flex items-center gap-4 mb-6 border-b border-gray-200">
            <button
                wire:click="$set('filter', 'all')"
                class="pb-2 text-sm font-medium border-b-2 transition {{ $filter === 'all' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                Todas
            </button>
            <button
                wire:click="$set('filter', 'unread')"
                class="pb-2 text-sm font-medium border-b-2 transition {{ $filter === 'unread' ? 'border-brand text-brand' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
            >
                Sin leer
            </button>
        </div>

        {{-- Notification list --}}
        @if($notifications->isEmpty())
            <x-ui.empty-state
                title="No hay notificaciones"
                :description="$filter === 'unread' ? 'No tienes notificaciones sin leer.' : 'Tu bandeja de notificaciones esta vacia.'" />
        @else
            <div class="bg-white rounded-lg border border-gray-200 divide-y divide-gray-100">
                @foreach($notifications as $notification)
                    <div class="px-5 py-4 flex items-start gap-4 {{ is_null($notification->read_at) ? 'bg-blue-50/30' : '' }}" wire:key="notif-{{ $notification->id }}">
                        {{-- Unread indicator --}}
                        <div class="shrink-0 mt-1.5">
                            @if(is_null($notification->read_at))
                                <div class="w-2 h-2 rounded-full bg-brand"></div>
                            @else
                                <div class="w-2 h-2"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="min-w-0 flex-1">
                            <p class="text-sm text-gray-800">
                                {{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Notificacion del sistema' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ $notification->created_at->diffForHumans() }}
                                &middot;
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                            </p>
                        </div>

                        {{-- Actions --}}
                        <div class="shrink-0">
                            @if(is_null($notification->read_at))
                                <button wire:click="markAsRead('{{ $notification->id }}')" class="text-xs text-gray-500 hover:text-brand" title="Marcar como leido">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </x-page.container>
</div>
