<x-app-layout>
    <x-slot name="header">
        <x-page.header :title="'Editar ' . ucfirst($tipo)" />
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Editar'],
        ]"
    >

        <livewire:cascade.ped-nodo-form :tipo="$tipo" :nodo-id="$id" />

    </x-page.container>

</x-app-layout>
