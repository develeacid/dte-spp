{{-- ===== Operador Widgets ===== --}}
@if($this->operadorStats)
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
@endif

{{-- Semaforo de operador --}}
@if($this->haySemaforoData)
    <div class="bg-white rounded-lg border border-gray-200 p-5 mt-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Semaforo de Mis Indicadores</h3>
        <x-charts.donut
            :labels="['Verde', 'Amarillo', 'Rojo']"
            :series="[$this->semaforo['verde'], $this->semaforo['amarillo'], $this->semaforo['rojo']]"
        />
    </div>
@endif

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

{{-- Quick Links --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-6">
    <a href="{{ route('tracking.pendientes') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Capturar avance</p>
            <p class="text-xs text-gray-500">Registrar avance de indicador</p>
        </div>
    </a>
    <a href="{{ route('tracking.pendientes') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-yellow-50 text-yellow-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Mis pendientes</p>
            <p class="text-xs text-gray-500">Indicadores por capturar</p>
        </div>
    </a>
</div>
