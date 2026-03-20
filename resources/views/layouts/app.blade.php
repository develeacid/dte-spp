<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased" x-data="{
        collapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        mobileOpen: false,
        toggle() {
            this.collapsed = !this.collapsed;
            localStorage.setItem('sidebar-collapsed', this.collapsed);
        }
    }">
        <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded focus:shadow-lg focus:text-brand focus:ring-2 focus:ring-brand">
            Saltar al contenido principal
        </a>

        <x-banner />

        <div class="min-h-screen bg-background">
            {{-- Sidebar --}}
            <x-ui.sidebar :team="Auth::user()?->currentTeam" :user="Auth::user()">
                <x-layout.sidebar-nav />
            </x-ui.sidebar>

            {{-- Main content area --}}
            <div class="transition-all duration-300 ease-in-out"
                 :class="collapsed ? 'lg:ml-[var(--sidebar-collapsed-width)]' : 'lg:ml-[var(--sidebar-width)]'">

                {{-- Topbar --}}
                <x-ui.topbar :user="Auth::user()">
                    @if(isset($breadcrumb))
                        <x-slot:breadcrumb>{{ $breadcrumb }}</x-slot:breadcrumb>
                    @endif
                </x-ui.topbar>

                {{-- Page Content --}}
                <main id="main-content" class="pb-[var(--bottom-nav-height)] lg:pb-0">
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Bottom Action Bar (mobile only) --}}
        @if(isset($mobileActions))
            <x-ui.bottom-action-bar>
                {{ $mobileActions }}
            </x-ui.bottom-action-bar>
        @endif

        <x-ui.bottom-nav />

        @stack('modals')

        @stack('scripts')

        @livewireScripts
    </body>
</html>
