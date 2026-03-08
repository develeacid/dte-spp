@props(['team' => null, 'user' => null])

<div x-data="{
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        mobileOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        }
     }"
     x-on:keydown.escape.window="mobileOpen = false"
     class="relative">

    {{-- Mobile backdrop --}}
    <div x-show="mobileOpen" x-cloak
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-600/75 lg:hidden"
         @click="mobileOpen = false">
    </div>

    {{-- Sidebar panel --}}
    <aside :class="[
               mobileOpen ? 'translate-x-0' : '-translate-x-full',
               collapsed ? 'lg:w-[var(--sidebar-collapsed-width)]' : 'lg:w-[var(--sidebar-width)]',
               'lg:translate-x-0'
           ]"
           class="fixed inset-y-0 left-0 z-50 flex flex-col w-[var(--sidebar-width)] bg-white border-r border-gray-200 transition-all duration-300 ease-in-out">

        {{-- Logo --}}
        <div class="flex items-center h-16 px-4 border-b border-gray-100 shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-2">
                <x-application-mark class="block h-8 w-auto" />
                <span x-show="!collapsed" x-cloak class="text-lg font-semibold text-gray-900 truncate">
                    SPP 2026
                </span>
            </a>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 overflow-y-auto py-4 px-2 space-y-1">
            {{ $slot }}
        </nav>

        {{-- Footer: team switcher + user --}}
        <div class="border-t border-gray-200 px-2 py-3 space-y-2 shrink-0">
            {{-- Team switcher --}}
            @if($team)
                <div x-show="!collapsed" x-cloak class="px-3 py-2">
                    <div class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">Unidad Responsable</div>
                    <div class="text-sm font-medium text-gray-700 truncate">{{ $team->name }}</div>
                </div>
                <div x-show="collapsed" x-cloak class="flex justify-center py-2 relative group">
                    <span class="w-8 h-8 rounded-full bg-brand-light text-brand-dark flex items-center justify-center text-xs font-bold">
                        {{ substr($team->name, 0, 2) }}
                    </span>
                    <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
                        <div class="bg-gray-900 text-white text-xs rounded-md py-1 px-2 whitespace-nowrap shadow-lg">
                            {{ $team->name }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- User --}}
            @if($user)
                <div x-show="!collapsed" x-cloak class="flex items-center px-3 py-2">
                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}
                    </span>
                    <div class="ml-3 min-w-0">
                        <div class="text-sm font-medium text-gray-700 truncate">{{ $user->name }}</div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-xs text-gray-400 hover:text-gray-600">Cerrar sesión</button>
                        </form>
                    </div>
                </div>
                <div x-show="collapsed" x-cloak class="flex justify-center py-2 relative group">
                    <span class="w-8 h-8 rounded-full bg-gray-200 text-gray-600 flex items-center justify-center text-xs font-bold">
                        {{ collect(explode(' ', $user->name))->map(fn($w) => strtoupper(substr($w, 0, 1)))->take(2)->join('') }}
                    </span>
                    <div class="absolute left-full ml-2 top-0 hidden group-hover:block z-50">
                        <div class="bg-gray-900 text-white text-xs rounded-md py-1 px-2 whitespace-nowrap shadow-lg">
                            {{ $user->name }}
                        </div>
                    </div>
                </div>
            @endif

            {{-- Collapse toggle (desktop only) --}}
            <button @click="toggle()" class="hidden lg:flex w-full items-center justify-center py-2 text-gray-400 hover:text-gray-600 transition-colors">
                <svg x-show="!collapsed" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7"/>
                </svg>
                <svg x-show="collapsed" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </aside>
</div>
