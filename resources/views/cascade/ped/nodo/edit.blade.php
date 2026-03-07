<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar {{ ucfirst($tipo) }}
        </h2>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Editar'],
        ]"
    >

        <livewire:cascade.ped-nodo-form :tipo="$tipo" :nodo-id="$id" />

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('cascade.ped.index') }}">
                Cancelar
            </x-ui.button.secondary>
        </x-slot:footer>

    </x-page.container>

</x-app-layout>
