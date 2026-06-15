<div>
    <x-page.header>
        <x-slot name="title">Programas</x-slot>
        <x-slot name="subtitle">Programas presupuestarios de tu Unidad Responsable</x-slot>
    </x-page.header>

    <x-page.container>
        @if ($programas->isEmpty())
            <div class="py-12 text-center text-sm text-gray-500">
                No hay programas asociados a tu Unidad Responsable.
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($programas as $programa)
                    <div class="flex flex-col rounded-lg border border-gray-200 bg-white p-5 shadow-sm transition hover:border-indigo-300 hover:shadow">
                        <div class="flex items-start justify-between gap-2">
                            <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-semibold text-indigo-700">
                                {{ $programa->clave }}
                            </span>
                            <span class="text-xs text-gray-400">{{ $programa->mir_niveles_count }} niveles MIR</span>
                        </div>

                        <h3 class="mt-3 line-clamp-2 text-sm font-medium text-gray-900">
                            {{ $programa->nombre }}
                        </h3>

                        <div class="mt-4 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-3 text-sm">
                            <a href="{{ route('tracking.dashboard-indicadores', $programa) }}"
                               wire:navigate
                               class="font-medium text-indigo-600 hover:text-indigo-500">
                                Dashboard MIR
                            </a>
                            @can('ver_padron')
                                <a href="{{ route('mml.padron', $programa) }}"
                                   wire:navigate
                                   class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Padrón
                                </a>
                                <a href="{{ route('mml.cobertura', $programa) }}"
                                   wire:navigate
                                   class="font-medium text-indigo-600 hover:text-indigo-500">
                                    Cobertura
                                </a>
                            @endcan
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-page.container>
</div>
