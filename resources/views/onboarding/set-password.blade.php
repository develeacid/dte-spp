<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Bienvenido, {{ $user->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">Establece tu contraseña para activar tu cuenta.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('activar.password', ['token' => $token]) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="password" value="Nueva contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmar contraseña" />
            <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-md p-3">
            <p class="text-sm text-amber-800">
                Después de establecer tu contraseña, deberás configurar la autenticación de dos factores para mayor seguridad.
            </p>
        </div>

        <x-button class="w-full justify-center">
            Continuar
        </x-button>
    </form>
</x-guest-layout>
