<div wire:poll.60s>
    <x-page.container title="Dashboard" subtitle="SPP — Ejercicio Fiscal 2026">

        {{-- ===== Admin/Planeador Widgets ===== --}}
        @can('revisar_avance')
            @if($this->adminStats->programas > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <x-ui.widget title="Programas" :value="$this->adminStats->programas" subtitle="Activos en tu unidad">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Indicadores" :value="$this->adminStats->indicadores" subtitle="Con seguimiento activo">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Avance Promedio" :value="$this->adminStats->avancePromedio . '%'" subtitle="Todos los indicadores">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    @if($this->adminStats->vencidos > 0)
                        <x-ui.widget title="Vencidos" :value="$this->adminStats->vencidos" subtitle="Requieren atención">
                            <x-slot:icon>
                                <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                            </x-slot:icon>
                        </x-ui.widget>
                    @endif
                </div>
            @else
                <x-ui.empty-state
                    title="No hay programas registrados"
                    description="Crea tu primer programa presupuestario para ver estadísticas aquí."
                    :actionUrl="route('mml.programas')"
                    actionLabel="Ir a Programas" />
            @endif
        @endcan

        {{-- ===== Operador Widgets ===== --}}
        @can('capturar_avance')
            @cannot('revisar_avance')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.widget title="Mis Pendientes" :value="$this->operadorStats->pendientes" subtitle="Avances por capturar">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>

                    <x-ui.widget title="Capturados este mes" :value="$this->operadorStats->capturadosMes" subtitle="Avances enviados">
                        <x-slot:icon>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </x-slot:icon>
                    </x-ui.widget>
                </div>

                @if($this->operadorStats->pendientes === 0)
                    <div class="mt-4">
                        <x-ui.empty-state
                            title="No tienes indicadores pendientes"
                            description="Todos tus avances han sido enviados." />
                    </div>
                @endif
            @endcannot
        @endcan

        {{-- ===== Gráficas Admin/Planeador ===== --}}
        @can('revisar_avance')
            @if($this->adminStats->programas > 0)
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                    {{-- Semáforo Global --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Semáforo Global</h3>
                        @if($this->haySemaforoData)
                            <x-charts.donut
                                :labels="['Verde', 'Amarillo', 'Rojo']"
                                :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
                            />
                        @else
                            <x-ui.empty-state title="Sin datos de semáforo" description="No hay avances con semáforo calculado." />
                        @endif
                    </div>

                    {{-- Avance por Programa --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-5">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Avance por Programa</h3>
                        @if($this->avancePorPrograma->isNotEmpty())
                            <x-charts.bar-horizontal
                                :categories="$this->avancePorPrograma->pluck('programa')->toArray()"
                                :series="[
                                    ['name' => 'Real', 'data' => $this->avancePorPrograma->pluck('real')->toArray()],
                                    ['name' => 'Programado', 'data' => $this->avancePorPrograma->pluck('programado')->toArray()],
                                ]"
                                :height="max(200, $this->avancePorPrograma->count() * 60)"
                            />
                        @else
                            <x-ui.empty-state title="Sin avances capturados" description="No hay avances capturados aún." />
                        @endif
                    </div>
                </div>

                {{-- Tendencia de Captura --}}
                <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4">Tendencia de Captura</h3>
                    <x-charts.line
                        :categories="$this->tendenciaCaptura->pluck('mes')->toArray()"
                        :series="[['name' => 'Avances capturados', 'data' => $this->tendenciaCaptura->pluck('count')->toArray()]]"
                        :height="250"
                    />
                </div>
            @endif
        @endcan

        {{-- Semáforo de operador (solo si NO es admin/planeador) --}}
        @can('capturar_avance')
            @cannot('revisar_avance')
                @if($this->haySemaforoData)
                    <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
                        <h3 class="text-sm font-semibold text-gray-700 mb-4">Semáforo de Mis Indicadores</h3>
                        <x-charts.donut
                            :labels="['Verde', 'Amarillo', 'Rojo']"
                            :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
                        />
                    </div>
                @endif
            @endcannot
        @endcan

        {{-- ===== Actividad Reciente (Placeholder) ===== --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Actividad Reciente</h3>
            <x-ui.empty-state
                title="Próximamente"
                description="El feed de actividad reciente se implementará en un sprint futuro." />
        </div>

    </x-page.container>
</div>
