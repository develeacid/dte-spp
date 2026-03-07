<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo Plan Estatal de Desarrollo
        </h2>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Nuevo Plan'],
        ]"
    >

        <livewire:cascade.ped-plan-form />

        <x-slot:footer>
            <x-ui.button.secondary href="{{ route('cascade.ped.index') }}">
                Cancelar
            </x-ui.button.secondary>
        </x-slot:footer>

    </x-page.container>

</x-app-layout>
