<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Plan Estatal de Desarrollo
        </h2>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED'],
        ]"
    >

        <x-slot:actions>
            <x-ui.button.primary href="{{ route('cascade.ped.plan.create') }}">
                Nuevo Plan
            </x-ui.button.primary>
        </x-slot:actions>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <livewire:cascade.ped-tree />
            </div>
        </div>

    </x-page.container>

</x-app-layout>
