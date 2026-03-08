<x-guest-layout>
    <div class="text-center">
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">Enlace inválido o expirado</h2>
        <p class="mt-2 text-sm text-gray-600">Este enlace de activación ya no es válido. Puede que haya expirado o ya hayas activado tu cuenta.</p>
        <p class="mt-4 text-sm text-gray-600">Si necesitas un nuevo enlace, contacta al administrador del sistema.</p>

        <div class="mt-6">
            <a href="{{ route('login') }}" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
                Volver al inicio de sesión
            </a>
        </div>
    </div>
</x-guest-layout>
