<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Restablecer Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tu nueva contraseña para continuar.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        </div>

        <div>
            <x-label for="password" value="Nueva contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmar contraseña" />
            <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-button class="w-full justify-center">
            Restablecer Contraseña
        </x-button>
    </form>
</x-guest-layout>
