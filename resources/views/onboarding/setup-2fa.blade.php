<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Configurar Autenticación de Dos Factores</h2>
        <p class="mt-2 text-sm text-gray-600">Escanea el código QR con tu aplicación de autenticación (Google Authenticator, Authy, etc.).</p>
    </div>

    <div class="mt-6">
        <div class="flex justify-center p-4 bg-white border border-gray-200 rounded-lg">
            {!! $qrCodeSvg !!}
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-md">
            <p class="text-xs text-gray-500 mb-1">Clave de configuración manual:</p>
            <p class="text-sm font-mono text-gray-700 break-all">{{ $setupKey }}</p>
        </div>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('activar.2fa.confirm', ['token' => $token]) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="code" value="Código de verificación" />
            <x-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" required autofocus autocomplete="one-time-code" placeholder="Ingresa el código de 6 dígitos" />
        </div>

        <x-button class="w-full justify-center">
            Verificar y Activar
        </x-button>
    </form>
</x-guest-layout>
