<div wire:poll.60s>
    <x-page.container title="Dashboard" subtitle="SPP — Ejercicio Fiscal 2026">

        @if($this->dashboardRole === 'admin')
            @include('livewire.dashboard.partials._admin')
        @elseif($this->dashboardRole === 'planeador')
            @include('livewire.dashboard.partials._planeador')
        @else
            @include('livewire.dashboard.partials._operador')
        @endif

        {{-- Partial presupuestal — visible para cualquier rol con permiso financiero --}}
        @include('livewire.dashboard.partials._analista_financiero')

    </x-page.container>
</div>
