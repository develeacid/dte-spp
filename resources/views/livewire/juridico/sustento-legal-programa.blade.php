<div>
    <x-page.header :title="'Sustento Legal — ' . $programa->clave">
        <x-slot:actions>
            @can('gestionar_sustento_legal')
                <x-ui.button.primary href="{{ route('juridico.fundamento.create', $programa) }}">Agregar fundamento</x-ui.button.primary>
            @endcan
        </x-slot:actions>
    </x-page.header>

    <x-page.container>
        @if (session('message'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">{{ session('message') }}</div>
        @endif

        {{-- Sección 1: Fundamentos jurídicos --}}
        <x-forms.section title="Fundamentos jurídicos" description="Ordenamientos legales que sustentan este programa presupuestario.">
            @forelse ($sustentos as $tipo => $grupo)
                <div class="col-span-6">
                    <h4 class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                        {{ \App\Enums\TipoSustentoLegal::from($tipo)->label() }}
                    </h4>
                    <div class="space-y-2">
                        @foreach ($grupo as $sustento)
                            <div class="flex items-start justify-between rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $sustento->cita_completa }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $sustento->nivel_jerarquia->label() }}
                                        @if (!$sustento->vigente) — <span class="text-red-500">No vigente</span> @endif
                                    </p>
                                    @if ($sustento->descripcion)
                                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $sustento->descripcion }}</p>
                                    @endif
                                    <p class="mt-1 text-xs text-gray-400">Registrado por {{ $sustento->registrador?->name }}</p>
                                </div>
                                @can('gestionar_sustento_legal')
                                    <div class="flex gap-2">
                                        <a href="{{ route('juridico.fundamento.edit', [$programa, $sustento]) }}" class="text-sm text-brand hover:text-brand-dark">Editar</a>
                                        <button wire:click="eliminarFundamento({{ $sustento->id }})" wire:confirm="Eliminar este fundamento?" class="text-sm text-red-600 hover:text-red-800">Eliminar</button>
                                    </div>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="col-span-6">
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay fundamentos registrados.</p>
                </div>
            @endforelse
        </x-forms.section>

        {{-- Sección 2: Documentos normativos --}}
        <x-forms.section title="Documentos normativos" description="Archivos PDF de ROP, leyes y reglamentos aplicables.">
            <div class="col-span-6">
                @if ($documentos->isNotEmpty())
                    <div class="space-y-2">
                        @foreach ($documentos as $doc)
                            <div class="flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-gray-700">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->nombre }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $doc->tipo_documento->label() }} — {{ $doc->archivo_size_humano }}
                                        @if ($doc->verificado) — <span class="text-green-600">Verificado</span> @else — <span class="text-yellow-600">Pendiente</span> @endif
                                    </p>
                                </div>
                                <a href="{{ route('juridico.documento.download', $doc) }}" class="text-sm text-brand hover:text-brand-dark">Descargar</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay documentos subidos.</p>
                @endif

                @can('gestionar_reglas_operacion')
                    <div class="mt-3">
                        <a href="{{ route('juridico.documentos', $programa) }}" class="text-sm text-brand hover:text-brand-dark">Gestionar documentos &rarr;</a>
                    </div>
                @endcan
            </div>
        </x-forms.section>

        {{-- Sección 3: Estado de validación --}}
        <x-forms.section title="Estado de validación" description="Checklist automático y estado de la validación jurídica.">
            <div class="col-span-6">
                @if ($validacion)
                    <div class="space-y-3">
                        {{-- Checklist --}}
                        <div class="flex gap-4">
                            <span class="inline-flex items-center gap-1 text-sm {{ $validacion->tiene_facultad_ur ? 'text-green-600' : 'text-red-500' }}">
                                {!! $validacion->tiene_facultad_ur ? '&#10003;' : '&#10007;' !!} Facultad UR
                            </span>
                            <span class="inline-flex items-center gap-1 text-sm {{ $validacion->tiene_mandato_gasto ? 'text-green-600' : 'text-red-500' }}">
                                {!! $validacion->tiene_mandato_gasto ? '&#10003;' : '&#10007;' !!} Mandato gasto
                            </span>
                            @if ($validacion->tiene_rop === null)
                                <span class="inline-flex items-center gap-1 text-sm text-gray-400">— ROP (N/A)</span>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm {{ $validacion->tiene_rop ? 'text-green-600' : 'text-red-500' }}">
                                    {!! $validacion->tiene_rop ? '&#10003;' : '&#10007;' !!} ROP
                                </span>
                            @endif
                        </div>

                        {{-- Estado --}}
                        <div>
                            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $validacion->estado->colorClass() }}">
                                {{ $validacion->estado->label() }}
                            </span>
                        </div>

                        @if ($validacion->observaciones)
                            <div class="rounded-md bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                <strong>Observaciones:</strong> {{ $validacion->observaciones }}
                            </div>
                        @endif

                        @can('validar_sustento_legal')
                            <div class="mt-2">
                                <a href="{{ route('juridico.validacion', $programa) }}" class="text-sm text-brand hover:text-brand-dark">Ir a validación &rarr;</a>
                            </div>
                        @endcan
                    </div>
                @else
                    <p class="text-sm text-gray-500 dark:text-gray-400">Sin registro de validación. Agregue fundamentos para iniciar el proceso.</p>
                @endif
            </div>
        </x-forms.section>
    </x-page.container>
</div>
