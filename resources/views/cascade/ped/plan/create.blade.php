<x-app-layout>
    <x-slot name="header">
        <x-page.header title="Nuevo Plan Estatal de Desarrollo" />
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Nuevo Plan'],
        ]"
    >

        <livewire:cascade.ped-plan-form />

    </x-page.container>

</x-app-layout>
