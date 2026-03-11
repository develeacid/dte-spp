{{-- Global Admin KPIs --}}
@if($this->globalAdminStats)
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 sm:gap-4 mb-6">
        <x-ui.widget title="Eficacia Promedio" :value="$this->globalAdminStats->eficaciaPromedio . '%'" subtitle="Indicadores aprobados (global)">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
            </x-slot:icon>
        </x-ui.widget>

        <x-ui.widget title="Programas Evaluados" :value="$this->globalAdminStats->programasEvaluados" subtitle="Con seguimiento activo">
            <x-slot:icon>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            </x-slot:icon>
        </x-ui.widget>

        @if($this->globalAdminStats->vencidosCrossTeam > 0)
            <x-ui.widget title="Vencidos (Global)" :value="$this->globalAdminStats->vencidosCrossTeam" subtitle="Todas las unidades">
                <x-slot:icon>
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </x-slot:icon>
            </x-ui.widget>
        @endif
    </div>
@endif

{{-- Include planeador view (team-scoped KPIs + charts + avances por revisar) --}}
@include('livewire.dashboard.partials._planeador')

{{-- Admin Quick Links --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mt-6">
    <a href="{{ route('admin.monitoreo-ia') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5a2.25 2.25 0 010 3l-3 3a2.25 2.25 0 01-3 0l-1.5-1.5a2.25 2.25 0 010-3l4.5-4.5zm-14.6 0a2.25 2.25 0 000 3l3 3a2.25 2.25 0 003 0l1.5-1.5a2.25 2.25 0 000-3L5.2 14.5z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Monitoreo IA</p>
            <p class="text-xs text-gray-500">Presupuesto y uso</p>
        </div>
    </a>
    <a href="{{ route('admin.users') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Gestion de usuarios</p>
            <p class="text-xs text-gray-500">Invitar y administrar</p>
        </div>
    </a>
    <a href="{{ route('tracking.panel') }}" class="flex items-center gap-3 p-4 bg-white rounded-lg border border-gray-200 hover:border-brand hover:shadow-sm transition">
        <div class="w-10 h-10 rounded-lg bg-brand-light text-brand flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6z"/></svg>
        </div>
        <div>
            <p class="text-sm font-medium text-gray-900">Panel de seguimiento</p>
            <p class="text-xs text-gray-500">Revision de avances</p>
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
