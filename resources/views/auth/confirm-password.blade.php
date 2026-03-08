<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Confirmar Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Esta es un área segura. Por favor confirma tu contraseña antes de continuar.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="password" value="Contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" autofocus />
        </div>

        <x-button class="w-full justify-center">
            Confirmar
        </x-button>
    </form>
</x-guest-layout>
