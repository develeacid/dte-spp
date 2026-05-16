<x-app-layout>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cascada de Planes', 'url' => route('cascade.ped.index')],
            ['label' => 'Matriz de Alineación']
        ]"
    >

        <livewire:cascade.matriz-alineacion-manager />

    </x-page.container>

</x-app-layout>
