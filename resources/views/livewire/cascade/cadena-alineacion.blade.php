<x-dialog-modal wire:model="showModal" max-width="2xl">
    <x-slot name="title">
        Cadena de Alineación Completa
    </x-slot>

    <x-slot name="content">
        @php($cadena = $this->cadena)

        @if($cadena)
            <div class="space-y-4">

                {{-- PED --}}
                <div class="border-l-4 border-blue-500 pl-4">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Plan Estatal de Desarrollo</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['plan']->nombre }}</div>
                </div>

                <div class="border-l-4 border-blue-400 pl-4 ml-4">
                    <div class="text-xs text-gray-500 mb-1">Eje</div>
                    <div class="text-sm text-gray-700">{{ $cadena['eje']->numero }}. {{ $cadena['eje']->nombre }}</div>
                </div>

                <div class="border-l-4 border-blue-300 pl-4 ml-8">
                    <div class="text-xs text-gray-500 mb-1">Tema</div>
                    <div class="text-sm text-gray-700">{{ $cadena['tema']->clave_completa }} - {{ $cadena['tema']->nombre }}</div>
                </div>

                <div class="border-l-4 border-amber-500 pl-4 ml-12">
                    <div class="text-xs text-gray-500 mb-1">Objetivo Estratégico</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['objetivo_estrategico']->clave_completa }}</div>
                    <div class="text-xs text-gray-600">{{ Str::limit($cadena['objetivo_estrategico']->descripcion, 100) }}</div>
                </div>

                <div class="border-l-4 border-emerald-500 pl-4 ml-12">
                    <div class="text-xs text-gray-500 mb-1">Estrategia</div>
                    <div class="text-sm text-gray-700">{{ $cadena['estrategia']->clave_completa }}</div>
                </div>

                <div class="border-l-4 border-indigo-500 pl-4 ml-16">
                    <div class="text-xs text-gray-500 mb-1">Línea de Acción</div>
                    <div class="text-sm font-medium text-gray-900">{{ $cadena['linea_accion']->clave_completa }}</div>
                    <div class="text-xs text-gray-600">{{ Str::limit($cadena['linea_accion']->descripcion, 100) }}</div>
                </div>

                {{-- PND Objetivos --}}
                @if($cadena['pnd_objetivos']->isNotEmpty())
                    <div class="border-l-4 border-green-600 pl-4 ml-12 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Objetivos PND Alineados</div>
                        @foreach($cadena['pnd_objetivos'] as $pnd)
                            <div class="bg-green-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">{{ $pnd->clave }}</span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($pnd->descripcion, 60) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ODS Metas --}}
                @if($cadena['ods_metas']->isNotEmpty())
                    <div class="border-l-4 border-rose-500 pl-4 ml-12 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Metas ODS Contribuidas</div>
                        @foreach($cadena['ods_metas'] as $ods)
                            <div class="bg-rose-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-rose-100 text-rose-800">{{ $ods->clave }}</span>
                                    <span class="text-xs text-gray-500">ODS {{ $ods->objetivo->numero }}</span>
                                    <span class="text-sm text-gray-700">{{ Str::limit($ods->descripcion, 50) }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Programas Derivados --}}
                @if($cadena['programas_objetivos']->isNotEmpty())
                    <div class="border-l-4 border-purple-500 pl-4 ml-16 mt-4">
                        <div class="text-xs text-gray-500 uppercase tracking-wider mb-2">Programas Derivados Vinculados</div>
                        @foreach($cadena['programas_objetivos'] as $prog)
                            <div class="bg-purple-50 rounded p-2 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">{{ $prog->clave_completa }}</span>
                                    <span class="text-xs text-gray-500">{{ $prog->programa->tipo->label() }}</span>
                                    <span class="text-sm text-gray-700">{{ $prog->programa->nombre }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($cadena['pnd_objetivos']->isEmpty() && $cadena['programas_objetivos']->isEmpty())
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 ml-12 mt-4">
                        <p class="text-sm text-yellow-700">
                            Esta Línea de Acción no tiene alineaciones registradas con PND ni Programas Derivados.
                        </p>
                    </div>
                @endif
            </div>
        @endif
    </x-slot>

    <x-slot name="footer">
        <x-secondary-button wire:click="closeModal">
            Cerrar
        </x-secondary-button>
    </x-slot>
</x-dialog-modal>
