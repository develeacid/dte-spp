{{-- Partial Presupuestal — visible para roles con ver_datos_financieros --}}
@if($this->financieroStats)
    <div class="mb-6">
        <h3 class="mb-3 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Presupuesto</h3>
        <div class="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-4">
            <x-ui.widget title="Presupuesto Total" :value="'$' . number_format($this->financieroStats->total_aprobado, 0)" :subtitle="'Ejercicio ' . config('presupuesto.ejercicio_default')">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot:icon>
            </x-ui.widget>

            <x-ui.widget title="% Ejercido" :value="$this->financieroStats->pct_ejercido . '%'" subtitle="Acumulado">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                </x-slot:icon>
            </x-ui.widget>

            <x-ui.widget title="Total Ejercido" :value="'$' . number_format($this->financieroStats->total_ejercido, 0)" subtitle="Pagado">
                <x-slot:icon>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </x-slot:icon>
            </x-ui.widget>

            @if($this->financieroStats->alertas_subejercicio > 0)
                <x-ui.widget title="Alertas Subejercicio" :value="$this->financieroStats->alertas_subejercicio" subtitle="Programas con rezago">
                    <x-slot:icon>
                        <svg class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </x-slot:icon>
                </x-ui.widget>
            @endif
        </div>
    </div>
@endif
