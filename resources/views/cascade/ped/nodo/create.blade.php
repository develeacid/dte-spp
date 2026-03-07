<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo {{ ucfirst($tipo) }}
        </h2>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Nuevo ' . ucfirst($tipo)],
        ]"
    >

        <livewire:cascade.ped-nodo-form :tipo="$tipo" :parent-id="(int) $parentId" />

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('cascade.ped.index') }}">
                Cancelar
            </x-ui.button.secondary>
        </x-slot:footer>

    </x-page.container>

</x-app-layout>
