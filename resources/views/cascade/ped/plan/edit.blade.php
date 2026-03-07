<x-app-layout>
    <x-slot name="header">
        <x-page.header :title="'Editar Plan: ' . $plan->nombre" />
    </x-slot>

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
