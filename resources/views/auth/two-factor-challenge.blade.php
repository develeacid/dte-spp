<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Autenticación de Dos Factores</h2>
    </div>

    <div x-data="{ recovery: false }">
        <p class="mt-2 text-sm text-gray-600" x-show="! recovery">
            Confirma el acceso a tu cuenta ingresando el código de autenticación de tu aplicación.
        </p>

        <p class="mt-2 text-sm text-gray-600" x-cloak x-show="recovery">
            Confirma el acceso a tu cuenta ingresando uno de tus códigos de recuperación de emergencia.
        </p>

        <x-validation-errors class="mt-4" />

        <form method="POST" action="{{ route('two-factor.login') }}" class="mt-6 space-y-4">
            @csrf

            <div x-show="! recovery">
                <x-label for="code" value="Código" />
                <x-input id="code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" />
            </div>

            <div x-cloak x-show="recovery">
                <x-label for="recovery_code" value="Código de Recuperación" />
                <x-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
            </div>

            <div class="flex items-center justify-between">
                <button type="button" class="text-sm text-brand hover:text-brand-dark font-medium"
                        x-show="! recovery"
                        x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    Usar código de recuperación
                </button>

                <button type="button" class="text-sm text-brand hover:text-brand-dark font-medium"
                        x-cloak x-show="recovery"
                        x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    Usar código de autenticación
                </button>

                <x-button>
                    Iniciar Sesión
                </x-button>
            </div>
        </form>
    </div>
</x-guest-layout>
