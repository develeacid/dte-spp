<x-app-layout>
    <x-slot name="header">
        <x-page.header :title="'Nuevo ' . ucfirst($tipo)" />
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Nuevo ' . ucfirst($tipo)],
        ]"
    >

        <livewire:cascade.ped-nodo-form :tipo="$tipo" :parent-id="(int) $parentId" />

    </x-page.container>

</x-app-layout>
