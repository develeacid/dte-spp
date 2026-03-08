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
    <body class="font-sans antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            {{-- Left panel — branding (hidden on mobile) --}}
            <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-950 text-white p-12">
                <div>
                    <div class="flex items-center space-x-3">
                        <x-application-mark class="block h-10 w-auto" />
                        <span class="text-2xl font-bold tracking-tight">SPP</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h1 class="text-4xl font-bold leading-tight">
                        Sistema de Planeación<br>y Programación
                    </h1>
                    <p class="text-emerald-200 text-lg">
                        Ejercicio Fiscal 2026
                    </p>
                    <div class="h-1 w-16 bg-emerald-400 rounded"></div>
                    <p class="text-emerald-300 text-sm max-w-md">
                        Plataforma integral para la gestión de programas presupuestarios, indicadores y seguimiento del desempeño gubernamental.
                    </p>
                </div>

                <p class="text-emerald-400 text-xs">
                    &copy; {{ date('Y') }} — Dirección de Tecnología y Evaluación
                </p>
            </div>

            {{-- Right panel — form --}}
            <div class="flex flex-col justify-center px-6 py-12 lg:px-16 bg-gray-50">
                {{-- Mobile logo --}}
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="flex items-center space-x-2">
                        <x-application-mark class="block h-8 w-auto" />
                        <span class="text-lg font-bold text-gray-900">SPP 2026</span>
                    </div>
                </div>

                <div class="w-full max-w-md mx-auto">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
