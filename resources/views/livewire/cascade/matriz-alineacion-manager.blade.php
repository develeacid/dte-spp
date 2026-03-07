<div class="space-y-6">

    {{-- Header con Stats --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900">Resumen de Alineaciones</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Vincule los diferentes niveles de la cascada de planeación.
                </p>
            </div>

            @php($stats = $this->stats)
            <div class="mt-4 md:mt-0 flex items-center space-x-3">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                    PED ↔ PND: {{ $stats['ped_pnd'] }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    PND ↔ ODS: {{ $stats['pnd_ods'] }}
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    Línea ↔ Prog: {{ $stats['linea_programa'] }}
                </span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white shadow sm:rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button wire:click="setActiveTab('ped-pnd')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'ped-pnd' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    PED ↔ PND
                </button>
                <button wire:click="setActiveTab('pnd-ods')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'pnd-ods' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    PND ↔ ODS
                </button>
                <button wire:click="setActiveTab('linea-programa')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'linea-programa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Línea ↔ Programa Derivado
                </button>
            </nav>
        </div>

        <div class="p-6">
            @if($activeTab === 'ped-pnd')
                <livewire:cascade.alineacion-ped-pnd :key="'ped-pnd'" />
            @elseif($activeTab === 'pnd-ods')
                <livewire:cascade.alineacion-pnd-ods :key="'pnd-ods'" />
            @else
                <livewire:cascade.alineacion-linea-programa :key="'linea-programa'" />
            @endif
        </div>
    </div>

    {{-- Diagrama de Cadena --}}
    <div class="bg-white shadow sm:rounded-lg p-6">
        <h4 class="text-sm font-medium text-gray-900 mb-4">Diagrama de Cadena de Alineación</h4>
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex flex-col items-center space-y-2 text-sm">
                <div class="bg-rose-100 text-rose-800 px-4 py-2 rounded font-medium">
                    ODS Meta
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-green-100 text-green-800 px-4 py-2 rounded font-medium">
                    PND Objetivo
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-amber-100 text-amber-800 px-4 py-2 rounded font-medium">
                    PED Objetivo Estratégico
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-blue-100 text-blue-800 px-4 py-2 rounded font-medium">
                    PED Estrategia → Línea de Acción
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                </svg>
                <div class="bg-purple-100 text-purple-800 px-4 py-2 rounded font-medium">
                    Programa Derivado Objetivo
                </div>
            </div>
        </div>
    </div>

    {{-- Componente de Cadena Completa --}}
    <livewire:cascade.cadena-alineacion />

</div>
