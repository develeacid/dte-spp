<x-app-layout>
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
