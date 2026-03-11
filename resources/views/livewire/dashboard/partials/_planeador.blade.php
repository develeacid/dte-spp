{{-- ===== Planeador/Admin KPI Cards ===== --}}
@if($this->adminStats && $this->adminStats->programas > 0)
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
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
            <x-ui.widget title="Vencidos" :value="$this->adminStats->vencidos" subtitle="Requieren atencion">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </x-slot:icon>
            </x-ui.widget>
        @endif
    </div>
@else
    <x-ui.empty-state
        title="No hay programas registrados"
        description="Crea tu primer programa presupuestario para ver estadisticas aqui."
        :actionUrl="route('mml.programas')"
        actionLabel="Ir a Programas" />
@endif

{{-- ===== Charts ===== --}}
@if($this->adminStats && $this->adminStats->programas > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
        {{-- Semaforo Global --}}
        <div class="bg-white rounded-lg border border-gray-200 p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">Semaforo Global</h3>
            @if($this->haySemaforoData)
                <x-charts.donut
                    :labels="['Verde', 'Amarillo', 'Rojo']"
                    :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
                />
            @else
                <x-ui.empty-state title="Sin datos de semaforo" description="No hay avances con semaforo calculado." />
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
                <x-ui.empty-state title="Sin avances capturados" description="No hay avances capturados aun." />
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

{{-- Avances por Revisar --}}
<div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-700">Avances por Revisar</h3>
        <a href="{{ route('tracking.panel') }}" class="text-xs text-brand hover:underline">Panel de seguimiento</a>
    </div>
    @if($this->avancesPorRevisar->isEmpty())
        <p class="text-sm text-gray-500">No hay avances pendientes de revision.</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Indicador</th>
                        <th class="hidden sm:table-cell px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Periodo</th>
                        <th class="hidden md:table-cell px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Operador</th>
                        <th class="hidden md:table-cell px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Enviado</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Accion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($this->avancesPorRevisar as $avance)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ Str::limit($avance->indicador->nombre, 40) }}</td>
                            <td class="hidden sm:table-cell px-4 py-3 text-sm text-gray-500">{{ $avance->metaPeriodo->periodo }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-sm text-gray-500">{{ $avance->capturador?->name ?? '—' }}</td>
                            <td class="hidden md:table-cell px-4 py-3 text-sm text-gray-400">{{ $avance->updated_at->diffForHumans() }}</td>
                            <td class="px-4 py-3 text-sm">
                                <a href="{{ route('tracking.flujo', $avance) }}" class="text-brand hover:underline">Revisar</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- Planeador Quick Links (not shown when included from admin partial) --}}
@if($this->dashboardRole !== 'admin')
    {{-- Quick Links --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
        <a href="{{ route('tracking.panel') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
            <div class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">Panel de seguimiento</p>
                <p class="text-xs text-gray-500">Revision de avances del equipo</p>
            </div>
        </a>
        <a href="{{ route('tracking.vencidos') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
            <div class="w-10 h-10 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-900">Indicadores vencidos</p>
                <p class="text-xs text-gray-500">Avances fuera de plazo</p>
            </div>
        </a>
    </div>

    {{-- Notificaciones Recientes --}}
    <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Notificaciones Recientes</h3>
            <a href="{{ route('notifications.index') }}" class="text-xs text-brand hover:underline">Ver todas</a>
        </div>
        @if($this->recentNotifications->isEmpty())
            <p class="text-sm text-gray-500">No tienes notificaciones sin leer.</p>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($this->recentNotifications as $notification)
                    <li class="py-3 flex items-start gap-3">
                        <div class="shrink-0 mt-0.5 w-2 h-2 rounded-full bg-brand"></div>
                        <div class="min-w-0">
                            <p class="text-sm text-gray-700">{{ $notification->data['message'] ?? $notification->data['mensaje'] ?? 'Nueva notificacion' }}</p>
                            <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
