<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Programas Derivados
        </h2>
    </x-slot>

    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Programas Derivados'],
        ]"
    >
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <livewire:cascade.programas-derivados-manager />
            </div>
        </div>
    </x-page.container>

</x-app-layout>
