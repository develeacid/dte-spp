{{--
    Sección de texto con guardado individual.
    Props: $campo (whitelist), $valor (string|null), $puedeGestionar (bool)
--}}
<div class="col-span-6" wire:key="seccion-{{ $campo }}">
    @if($puedeGestionar)
        <textarea
            id="seccion-{{ $campo }}"
            rows="5"
            wire:model="secciones.{{ $campo }}"
            class="block w-full rounded border-gray-300 text-sm"
        >{{ $valor }}</textarea>
        <div class="mt-2">
            <button
                type="button"
                wire:click="guardarSeccion('{{ $campo }}', $wire.secciones.{{ $campo }})"
                class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700"
            >
                Guardar sección
            </button>
        </div>
    @else
        <p class="whitespace-pre-line text-sm text-gray-800">{{ $valor ?: '—' }}</p>
    @endif
</div>
