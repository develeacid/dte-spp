<x-form-section submit="createTeam">
    <x-slot name="title">
        Datos de la Unidad Responsable
    </x-slot>

    <x-slot name="description">
        Crea una nueva unidad responsable para gestionar programas presupuestarios y colaborar con otros servidores públicos.
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6">
            <x-label value="Titular" />

            <div class="flex items-center mt-2">
                <img class="size-12 rounded-full object-cover" src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}">

                <div class="ms-4 leading-tight">
                    <div class="text-gray-900">{{ $this->user->name }}</div>
                    <div class="text-gray-700 text-sm">{{ $this->user->email }}</div>
                </div>
            </div>
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="Nombre de la Unidad" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" autofocus />
            <x-input-error for="name" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-button>
            Crear
        </x-button>
    </x-slot>
</x-form-section>
