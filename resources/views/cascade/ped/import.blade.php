<x-app-layout>

    {{-- Slot Header de Jetstream --}}
    <x-slot name="header">
        <x-page.header
            title="Importar Plan Estatal de Desarrollo"
            subtitle="Cargue un archivo Markdown con la estructura completa del PED"
        >
            <a href="{{ route('cascade.ped.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 disabled:opacity-25 transition">
                Volver
            </a>
        </x-page.header>
    </x-slot>

    {{-- Contenedor de Página --}}
    <x-page.container
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Cascada de Planes', 'url' => route('cascade.ped.index')],
            ['label' => 'Importar PED']
        ]"
    >

        <livewire:cascade.ped-importer />

    </x-page.container>

</x-app-layout>
