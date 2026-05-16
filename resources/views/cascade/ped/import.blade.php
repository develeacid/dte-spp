<x-app-layout>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cascada de Planes', 'url' => route('cascade.ped.index')],
            ['label' => 'Importar PED']
        ]"
    >

        <livewire:cascade.ped-importer />

    </x-page.container>

</x-app-layout>
