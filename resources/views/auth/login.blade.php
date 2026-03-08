<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Iniciar Sesión</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tus credenciales para acceder al sistema.</p>
    </div>

    @if ($errors->any())
        <div class="mt-4 p-4 rounded-lg bg-red-50 border border-red-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-red-800">
                    @if ($errors->count() === 1)
                        {{ $errors->first() }}
                    @else
                        Se encontraron los siguientes errores:
                    @endif
                </p>
            </div>
            @if ($errors->count() > 1)
                <ul class="mt-2 ml-7 list-disc text-sm text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    @session('status')
        <div class="mt-4 p-4 rounded-lg bg-amber-50 border border-amber-200">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-medium text-amber-800">{{ $value }}</p>
            </div>
        </div>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <div>
            <x-label for="password" value="Contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center">
                <x-checkbox id="remember_me" name="remember" />
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-brand hover:text-brand-dark font-medium" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-button class="w-full justify-center">
            Iniciar Sesión
        </x-button>
    </form>
</x-guest-layout>
