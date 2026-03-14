<div>
    <x-page.header :title="'Validación Jurídica — ' . $programa->clave" />

    <x-page.container>
        @if (session('message'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">{{ session('message') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-300">{{ session('error') }}</div>
        @endif

        {{-- Checklist automático --}}
        <x-forms.section title="Checklist de validación" description="Estado automático basado en los fundamentos y documentos registrados.">
            <div class="col-span-6">
                <div class="space-y-3 rounded-md border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <span class="text-lg {{ $validacion->tiene_facultad_ur ? 'text-green-600' : 'text-red-500' }}">{!! $validacion->tiene_facultad_ur ? '&#10003;' : '&#10007;' !!}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Facultad de la UR</p>
                            <p class="text-xs text-gray-500">Artículo de la Ley Orgánica que faculta a la UR</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-lg {{ $validacion->tiene_mandato_gasto ? 'text-green-600' : 'text-red-500' }}">{!! $validacion->tiene_mandato_gasto ? '&#10003;' : '&#10007;' !!}</span>
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Mandato de gasto</p>
                            <p class="text-xs text-gray-500">Ley o norma que obliga al Estado a gastar en esta materia</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @if ($validacion->tiene_rop === null)
                            <span class="text-lg text-gray-400">—</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Reglas de Operación</p>
                                <p class="text-xs text-gray-500">No aplica para este programa</p>
                            </div>
                        @else
                            <span class="text-lg {{ $validacion->tiene_rop ? 'text-green-600' : 'text-red-500' }}">{!! $validacion->tiene_rop ? '&#10003;' : '&#10007;' !!}</span>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">Reglas de Operación</p>
                                <p class="text-xs text-gray-500">ROP publicadas y verificadas</p>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Estado actual --}}
                <div class="mt-4">
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium {{ $validacion->estado->colorClass() }}">
                        {{ $validacion->estado->label() }}
                    </span>
                </div>
            </div>
        </x-forms.section>

        {{-- Fundamentos detallados --}}
        <x-forms.section title="Fundamentos registrados" description="Detalle de los ordenamientos legales.">
            <div class="col-span-6 space-y-2">
                @forelse ($sustentos as $sustento)
                    <div class="rounded-md border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300">{{ $sustento->tipo->label() }}</span>
                                <p class="mt-1 text-sm font-medium text-gray-900 dark:text-white">{{ $sustento->cita_completa }}</p>
                                @if ($sustento->descripcion)
                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $sustento->descripcion }}</p>
                                @endif
                            </div>
                            <span class="{{ $sustento->vigente ? 'text-green-600' : 'text-red-500' }} text-xs">{{ $sustento->vigente ? 'Vigente' : 'No vigente' }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay fundamentos registrados.</p>
                @endforelse
            </div>
        </x-forms.section>

        {{-- Documentos --}}
        <x-forms.section title="Documentos normativos" description="PDFs adjuntos con estado de verificación.">
            <div class="col-span-6 space-y-2">
                @forelse ($documentos as $doc)
                    <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-gray-700">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->nombre }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $doc->tipo_documento->label() }}
                                — @if ($doc->verificado) <span class="text-green-600">Verificado</span> @else <span class="text-yellow-600">Pendiente</span> @endif
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay documentos adjuntos.</p>
                @endforelse
            </div>
        </x-forms.section>

        {{-- Acciones de validación --}}
        <x-forms.section title="Acción de validación" description="Observaciones y decisión final sobre el sustento legal del programa.">
            <div class="col-span-6">
                <x-label for="observaciones" value="Observaciones" />
                <textarea id="observaciones" wire:model="observaciones" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Observaciones (obligatorias para rechazar)..."></textarea>
                @error('observaciones') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </x-forms.section>

        <x-page.form-footer>
            <x-ui.button.secondary href="{{ route('juridico.programa', $programa) }}">Cancelar</x-ui.button.secondary>
            <button wire:click="marcarEnRevision" class="inline-flex items-center rounded-md border border-yellow-300 bg-yellow-50 px-4 py-2 text-sm font-semibold text-yellow-800 hover:bg-yellow-100">En revisión</button>
            <button wire:click="rechazar" class="inline-flex items-center rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100">Rechazar</button>
            <x-ui.button.primary wire:click="validar">Validar jurídicamente</x-ui.button.primary>
        </x-page.form-footer>
    </x-page.container>
</div>
