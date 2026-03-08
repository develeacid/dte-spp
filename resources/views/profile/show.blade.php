<x-app-layout>
    <x-page.container title="Mi Perfil" subtitle="Configuración de tu cuenta">

        {{-- Información Personal --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Información Personal</h3>
            <p class="text-sm text-gray-500 mb-6">Actualiza tu nombre, correo electrónico y foto de perfil.</p>
            @livewire('profile.update-profile-information-form')
        </div>

        {{-- Seguridad --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Seguridad</h3>
            <p class="text-sm text-gray-500 mb-6">Gestiona tu contraseña, autenticación de dos factores y sesiones activas.</p>

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mb-8">
                    @livewire('profile.update-password-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mb-8 pt-6 border-t border-gray-200">
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div class="pt-6 border-t border-gray-200">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>
        </div>

        {{-- Mi Unidad Responsable --}}
        @if(Auth::user()->currentTeam)
            <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Mi Unidad Responsable</h3>
                <p class="text-sm text-gray-500 mb-6">Información de la unidad responsable a la que perteneces.</p>
                @livewire('teams.update-team-name-form', ['team' => Auth::user()->currentTeam])
            </div>
        @endif

        {{-- Servidores Públicos (solo admin/planeador) --}}
        @if(Auth::user()->currentTeam)
            @can('gestionar_catalogos')
                <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Servidores Públicos</h3>
                    <p class="text-sm text-gray-500 mb-6">Gestiona los miembros de tu unidad responsable.</p>
                    @livewire('teams.team-member-manager', ['team' => Auth::user()->currentTeam])
                </div>
            @endcan
        @endif

        {{-- NO renderizar delete-user-form — oculto por auditoría --}}

    </x-page.container>
</x-app-layout>
