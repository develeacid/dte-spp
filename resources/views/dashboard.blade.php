<x-app-layout>
    <x-page.container title="Dashboard" subtitle="SPP — Ejercicio Fiscal 2026">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Placeholder widgets — will be populated in Sprint 10 --}}
            @can('revisar_avance')
                <x-ui.widget title="Programas" value="—" subtitle="Cargando datos..." />
                <x-ui.widget title="Indicadores" value="—" subtitle="Cargando datos..." />
                <x-ui.widget title="Avance Promedio" value="—" subtitle="Cargando datos..." />
            @endcan
            @can('capturar_avance')
                <x-ui.widget title="Pendientes" value="—" subtitle="Cargando datos..." />
            @endcan
        </div>

        <div class="mt-6">
            <x-ui.empty-state
                title="Dashboard en construcción"
                description="Los widgets operativos con datos reales se implementarán en Sprint 10." />
        </div>
    </x-page.container>
</x-app-layout>
