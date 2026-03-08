<div class="relative" x-data="{ open: $wire.entangle('open') }">
    {{-- Bell button --}}
    <button @click="open = !open" class="relative p-2 text-gray-500 hover:text-gray-700 focus:outline-none transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if($this->unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white bg-red-500 rounded-full">
                {{ $this->unreadCount > 9 ? '9+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    <div
        x-show="open"
        x-transition
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50"
    >
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700">Notificaciones</h3>
            @if($this->unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-brand hover:underline">Marcar todo como leido</button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            @forelse($this->notifications as $notification)
                <div class="px-4 py-3 hover:bg-gray-50 flex items-start gap-3" wire:key="notif-{{ $notification->id }}">
                    <div class="shrink-0 mt-1 w-2 h-2 rounded-full bg-brand"></div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-gray-700">{{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion' }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                    <button wire:click="markAsRead('{{ $notification->id }}')" class="shrink-0 text-gray-400 hover:text-gray-600" title="Marcar como leido">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">
                    No tienes notificaciones sin leer.
                </div>
            @endforelse
        </div>

        <div class="px-4 py-3 border-t border-gray-100 text-center">
            <a href="{{ route('notifications.index') }}" class="text-xs text-brand hover:underline">Ver todas las notificaciones</a>
        </div>
    </div>
</div>
