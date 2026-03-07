<x-app-layout>

    <x-slot name="header">
        <x-page.header
            title="Matriz de Alineación"
            subtitle="Configure las relaciones entre los niveles de la cascada de planeación"
        >
            <x-secondary-button href="{{ route('cascade.ped.index') }}">
                Volver al PED
            </x-secondary-button>
        </x-page.header>
    </x-slot>

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
