<x-page.container title="Gestión de Usuarios" subtitle="Administra las cuentas del sistema">
    <x-slot:actions>
        <button wire:click="openInviteForm" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
            + Invitar Usuario
        </button>
    </x-slot:actions>

    {{-- Formulario de invitación --}}
    @if($showInviteForm)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invitar Usuario</h3>

            <form wire:submit="sendInvitation" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="inviteName" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                        <input type="text" wire:model="inviteName" id="inviteName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('inviteName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteEmail" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input type="email" wire:model="inviteEmail" id="inviteEmail" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('inviteEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteRole" class="block text-sm font-medium text-gray-700">Rol</label>
                        <select wire:model="inviteRole" id="inviteRole" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            <option value="operador">Operador</option>
                            <option value="planeador">Planeador</option>
                            <option value="admin">Administrador</option>
                        </select>
                        @error('inviteRole') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteTeamId" class="block text-sm font-medium text-gray-700">Unidad Responsable</label>
                        <select wire:model="inviteTeamId" id="inviteTeamId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            <option value="">Seleccionar...</option>
                            @foreach($this->teams as $team)
                                <option value="{{ $team->id }}">{{ $team->clave_ur ? $team->clave_ur . ' — ' : '' }}{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @error('inviteTeamId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" wire:click="cancelInvite" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
            </div>
            <div>
                <select wire:model.live="filterEstado" class="border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($usuarios as $usuario)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <x-ui.avatar :name="$usuario->name" :src="$usuario->profile_photo_url ?? null" size="sm" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $usuario->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $usuario->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $usuario->roles->first()?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $usuario->currentTeam?->clave_ur ?? $usuario->currentTeam?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if(! $usuario->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                            @elseif($usuario->isPendingActivation())
                                @if($usuario->isInvitationExpired())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expirado</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            @if($usuario->isPendingActivation() || ($usuario->isActivated() === false && $usuario->invitation_token))
                                <button wire:click="resendInvitation({{ $usuario->id }})" wire:confirm="¿Reenviar invitación a {{ $usuario->email }}?" class="text-emerald-600 hover:text-emerald-900">Reenviar</button>
                            @endif
                            @if($usuario->id !== auth()->id())
                                <button wire:click="toggleActive({{ $usuario->id }})" wire:confirm="{{ $usuario->active ? '¿Desactivar' : '¿Reactivar' }} a {{ $usuario->name }}?" class="{{ $usuario->active ? 'text-red-600 hover:text-red-900' : 'text-emerald-600 hover:text-emerald-900' }}">
                                    {{ $usuario->active ? 'Desactivar' : 'Reactivar' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($usuarios->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</x-page.container>
