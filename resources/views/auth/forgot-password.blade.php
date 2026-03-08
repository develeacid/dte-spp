<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Recuperar Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
    </div>

    @session('status')
        <div class="mt-4 font-medium text-sm text-green-600">
            {{ $value }}
        </div>
    @endsession

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <x-button class="w-full justify-center">
            Enviar Enlace de Recuperación
        </x-button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-brand hover:text-brand-dark font-medium">
                Volver al inicio de sesión
            </a>
        </div>
    </form>
</x-guest-layout>
