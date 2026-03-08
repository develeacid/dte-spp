@props(['user' => null])

<header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-6 bg-white border-b border-gray-200">
    {{-- Left: mobile hamburger + breadcrumb --}}
    <div class="flex items-center space-x-4">
        {{-- Mobile hamburger --}}
        <button @click="mobileOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Breadcrumb slot --}}
        @if(isset($breadcrumb))
            <nav class="hidden sm:flex text-sm text-gray-500 space-x-1">
                {{ $breadcrumb }}
            </nav>
        @endif
    </div>

    {{-- Right: user dropdown --}}
    <div class="flex items-center">
        @if($user)
            <x-dropdown align="right" width="60">
                <x-slot name="trigger">
                    <button class="flex items-center space-x-3 text-sm text-gray-600 hover:text-gray-900 focus:outline-none transition">
                        <x-ui.avatar :name="$user->name" :src="Laravel\Jetstream\Jetstream::managesProfilePhotos() ? $user->profile_photo_url : null" size="sm" />
                        <div class="hidden md:block text-left">
                            <div class="font-medium text-gray-700">{{ $user->name }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $user->roles->first()?->name ?? 'usuario' }} · {{ $user->currentTeam?->clave_ur ?? $user->currentTeam?->name ?? '' }}
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    {{-- User info header --}}
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <x-ui.avatar :name="$user->name" :src="Laravel\Jetstream\Jetstream::managesProfilePhotos() ? $user->profile_photo_url : null" size="md" />
                            <div>
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </div>

                    <x-dropdown-link href="{{ route('profile.show') }}">
                        Mi Perfil
                    </x-dropdown-link>

                    <div class="border-t border-gray-100"></div>

                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            Cerrar Sesión
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        @endif
    </div>
</header>
