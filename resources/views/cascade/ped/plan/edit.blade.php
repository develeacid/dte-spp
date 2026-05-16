<x-app-layout>
    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'PED', 'url' => route('cascade.ped.index')],
            ['label' => 'Editar Plan'],
        ]"
    >

        <livewire:cascade.ped-plan-form :plan="$plan" />

    </x-page.container>

</x-app-layout>
