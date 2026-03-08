<x-action-section>
    <x-slot name="title">
        Eliminar Unidad Responsable
    </x-slot>

    <x-slot name="description">
        Eliminar permanentemente esta unidad responsable.
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600">
            Una vez que una unidad responsable es eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de eliminarla, descarga cualquier dato o información que desees conservar.
        </div>

        <div class="mt-5">
            <x-danger-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                Eliminar Unidad
            </x-danger-button>
        </div>

        <!-- Delete Team Confirmation Modal -->
        <x-confirmation-modal wire:model.live="confirmingTeamDeletion">
            <x-slot name="title">
                Eliminar Unidad Responsable
            </x-slot>

            <x-slot name="content">
                ¿Estás seguro de que deseas eliminar esta unidad responsable? Una vez eliminada, todos sus recursos y datos serán eliminados permanentemente.
            </x-slot>

            <x-slot name="footer">
                <x-secondary-button wire:click="$toggle('confirmingTeamDeletion')" wire:loading.attr="disabled">
                    Cancelar
                </x-secondary-button>

                <x-danger-button class="ms-3" wire:click="deleteTeam" wire:loading.attr="disabled">
                    Eliminar Unidad
                </x-danger-button>
            </x-slot>
        </x-confirmation-modal>
    </x-slot>
</x-action-section>
