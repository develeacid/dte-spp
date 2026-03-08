<x-guest-layout>
    <div>
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">¡Tu cuenta está lista!</h2>
        <p class="mt-2 text-sm text-gray-600">Has configurado tu contraseña y autenticación de dos factores correctamente.</p>
    </div>

    @if($recoveryCodes)
        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm font-semibold text-amber-800 mb-2">Códigos de recuperación</p>
            <p class="text-xs text-amber-700 mb-3">Guarda estos códigos en un lugar seguro. Los necesitarás si pierdes acceso a tu aplicación de autenticación. Solo se muestran una vez.</p>
            <div class="grid grid-cols-2 gap-1 font-mono text-sm bg-white p-3 rounded border border-amber-200">
                @foreach($recoveryCodes as $code)
                    <div class="text-gray-700">{{ $code }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
            Ir al Dashboard
        </a>
    </div>
</x-guest-layout>
