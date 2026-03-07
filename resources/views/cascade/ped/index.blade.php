<x-app-layout>
    <x-slot name="header">
        <x-page.header title="Plan Estatal de Desarrollo">
            <x-ui.button.primary href="{{ route('cascade.ped.plan.create') }}">
                Nuevo Plan
            </x-ui.button.primary>
        </x-page.header>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED'],
        ]"
    >

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <livewire:cascade.ped-tree />
            </div>
        </div>

    </x-page.container>

</x-app-layout>
