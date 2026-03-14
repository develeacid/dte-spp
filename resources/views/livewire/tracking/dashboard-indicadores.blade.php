<div>
    <x-slot name="header">
        <x-page.header
            title="Dashboard de Indicadores"
            :subtitle="$programa->nombre"
        />
    </x-slot>

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Seguimiento', 'url' => route('tracking.panel')],
        ['label' => 'Dashboard — ' . $programa->nombre],
    ]">
        <div class="space-y-4">
            @php
                $niveles = collect();
                if ($fin) $niveles->push(['nivel' => $fin, 'color' => 'blue', 'label' => 'FIN']);
                if ($proposito) $niveles->push(['nivel' => $proposito, 'color' => 'emerald', 'label' => 'PROPÓSITO']);
            @endphp

            @foreach($niveles as $item)
                @include('livewire.tracking.partials.dashboard-nivel-row', [
                    'nivel' => $item['nivel'],
                    'color' => $item['color'],
                    'label' => $item['label'],
                    'depth' => 0,
                ])
            @endforeach

            @foreach($componentes as $idx => $componente)
                @include('livewire.tracking.partials.dashboard-nivel-row', [
                    'nivel' => $componente,
                    'color' => 'amber',
                    'label' => 'C' . ($idx + 1),
                    'depth' => 0,
                ])

                @foreach($componente->actividades as $aIdx => $actividad)
                    @include('livewire.tracking.partials.dashboard-nivel-row', [
                        'nivel' => $actividad,
                        'color' => 'violet',
                        'label' => 'A' . ($idx + 1) . '.' . ($aIdx + 1),
                        'depth' => 1,
                    ])
                @endforeach
            @endforeach
        </div>

        @if(!$fin && !$proposito && $componentes->isEmpty())
            <div class="text-center py-12 text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <p class="mt-2 text-sm">No hay niveles MIR configurados para este programa.</p>
            </div>
        @endif
    </x-page.container>
</div>
