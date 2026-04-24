<div>
    <x-page.header>
        <x-slot name="title">Aspectos Susceptibles de Mejora</x-slot>
    </x-page.header>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Evaluación', 'url' => '#'],
        ['label' => 'ASM'],
    ]">
        <p class="text-sm text-gray-500">Listado en construcción.</p>
    </x-page.container>
</div>
