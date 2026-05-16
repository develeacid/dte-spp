<x-app-layout>
    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED'],
        ]"
    >

        <div class="flex justify-end mb-4">
            <x-ui.button.primary href="{{ route('cascade.ped.plan.create') }}">
                Nuevo Plan
            </x-ui.button.primary>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <livewire:cascade.ped-tree />
            </div>
        </div>

    </x-page.container>

</x-app-layout>
