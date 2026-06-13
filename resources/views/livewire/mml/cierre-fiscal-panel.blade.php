<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Cierre fiscal'],
    ]">
        <x-page.header title="Cierre fiscal" :subtitle="$programa->nombre . ' · Ejercicio ' . $ejercicio" />

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            {{-- Stepper de 4 fases --}}
            <ol class="flex items-center w-full mb-6">
                @foreach ($fases as $i => $fase)
                    @php $activa = $fase->orden() <= $cierre->estado->orden(); @endphp
                    <li @class(['flex items-center', 'w-full' => ! $loop->last])>
                        <span @class([
                            'flex items-center justify-center w-8 h-8 rounded-full shrink-0 text-xs font-bold',
                            'bg-indigo-600 text-white' => $activa,
                            'bg-gray-100 text-gray-400' => ! $activa,
                        ])>{{ $fase->orden() }}</span>
                        <span @class(['ml-2 text-sm font-medium', 'text-gray-900' => $activa, 'text-gray-400' => ! $activa])>
                            {{ $fase->label() }}
                        </span>
                        @unless ($loop->last)
                            <span @class(['flex-1 h-0.5 mx-3', 'bg-indigo-600' => $fase->orden() < $cierre->estado->orden(), 'bg-gray-200' => $fase->orden() >= $cierre->estado->orden()])></span>
                        @endunless
                    </li>
                @endforeach
            </ol>

            <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                <div>
                    <p class="text-xs uppercase tracking-wider text-gray-400">Fase actual</p>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold {{ $cierre->estado->colorClass() }}">
                        {{ $cierre->estado->label() }}
                    </span>
                </div>

                @can('gestionar_cierre_fiscal')
                    @unless ($cierre->estado->esTerminal())
                        <button
                            wire:click="avanzar"
                            wire:confirm="¿Avanzar a la fase '{{ $cierre->estado->siguiente()?->label() }}'?"
                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Avanzar a {{ $cierre->estado->siguiente()?->label() }} →
                        </button>
                    @else
                        <span class="text-sm text-gray-500">Ejercicio cerrado.</span>
                    @endunless
                @endcan
            </div>

            <p class="mt-4 text-xs text-gray-400">
                La transición a <b>Firma</b> exige el IAFF del 4º trimestre firmado. <b>Cerrado</b> congela la captura de avances del ejercicio.
            </p>
        </div>
    </x-page.container>
</div>
