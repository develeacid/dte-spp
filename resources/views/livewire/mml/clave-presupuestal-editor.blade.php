<div>
    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => $programa->nombre, 'url' => route('dashboard')],
        ['label' => 'Clave presupuestal'],
    ]">
        <x-page.header
            title="Clave presupuestal canónica"
            :subtitle="$programa->nombre . ' · Estructura SEFIP/CONAC'" />

        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
        @endif
        @error('clave_presupuestal')
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">{{ $message }}</div>
        @enderror

        {{-- Importar clave SEFIP completa (32) ya en uso --}}
        <div class="mb-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-sm font-semibold text-gray-800">Importar clave SEFIP completa</p>
            <p class="mb-3 text-xs text-gray-500">¿El programa ya tiene su clave de 32 caracteres? Pégala para segmentarla y rellenar los campos. Validación informativa, no bloquea.</p>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                <input type="text" wire:model="clave_sefip" placeholder="Ej: 10100114402000001313303AEBAA0125"
                    class="block w-full font-mono border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm sm:flex-1" />
                <button type="button" wire:click="segmentarClave"
                    class="inline-flex items-center justify-center rounded-lg bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                    Segmentar
                </button>
            </div>

            @if ($avisosSegmentacion)
                <ul class="mt-3 space-y-1 text-xs text-amber-700">
                    @foreach ($avisosSegmentacion as $aviso)
                        <li>⚠ {{ $aviso }}</li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($desglose['segmentos']))
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-100">
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-gray-100">
                            <tr class="bg-gray-50">
                                <td class="px-3 py-1.5 font-medium text-gray-600">Administrativa (6)</td>
                                <td class="px-3 py-1.5 font-mono text-gray-900">
                                    Grupo {{ $desglose['segmentos']['grupo'] ?? '—' }} ·
                                    UR {{ $desglose['segmentos']['unidad_responsable'] ?? '—' }} ·
                                    UE {{ $desglose['segmentos']['unidad_ejecutora'] ?? '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 font-medium text-gray-600">Programática (11)</td>
                                <td class="px-3 py-1.5 font-mono text-gray-900">
                                    Prog {{ $desglose['segmentos']['programa_clave'] ?? '—' }} ·
                                    Subprog {{ $desglose['segmentos']['subprograma'] ?? '—' }} ·
                                    Proy {{ $desglose['segmentos']['proyecto'] ?? '—' }} ·
                                    Act {{ $desglose['segmentos']['actividad'] ?? '—' }}
                                </td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-3 py-1.5 font-medium text-gray-600">Objeto del Gasto (6)</td>
                                <td class="px-3 py-1.5 font-mono text-gray-900">{{ $desglose['informativos']['objeto_del_gasto'] ?? '—' }} <span class="text-gray-400">(nivel partida)</span></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-1.5 font-medium text-gray-600">Financiamiento (9)</td>
                                <td class="px-3 py-1.5 font-mono text-gray-900">{{ $desglose['informativos']['financiamiento'] ?? '—' }} <span class="text-gray-400">(nivel transacción)</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Preview en vivo de la clave compuesta --}}
        <div class="mb-6 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
            <p class="text-xs uppercase tracking-wider text-indigo-400">Clave canónica (Administrativa + Programática)</p>
            <p class="font-mono text-lg font-semibold text-indigo-900">
                {{ $this->previewClave ?? 'Incompleta — captura los 7 segmentos administrativos/programáticos' }}
            </p>
        </div>

        <form wire:submit="guardar">
            <x-forms.section
                title="Clasificación Administrativa"
                description="¿Quién ejerce el gasto? Grupo (1 díg), Unidad Responsable (2 díg), Unidad Ejecutora (3 díg).">
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="grupo" value="Grupo" />
                    <x-input id="grupo" type="number" min="0" max="9" class="mt-1 block w-full" wire:model="grupo" />
                    <x-input-error for="grupo" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="unidad_responsable" value="Unidad Responsable" />
                    <x-input id="unidad_responsable" type="number" min="0" max="99" class="mt-1 block w-full" wire:model="unidad_responsable" />
                    <x-input-error for="unidad_responsable" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="unidad_ejecutora" value="Unidad Ejecutora" />
                    <x-input id="unidad_ejecutora" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="unidad_ejecutora" />
                    <x-input-error for="unidad_ejecutora" class="mt-1" />
                </div>
            </x-forms.section>

            <x-forms.section
                title="Clasificación Programática"
                description="¿Bajo qué estructura? Programa (3), Subprograma (2), Proyecto (3), Actividad (3).">
                <div class="col-span-6 sm:col-span-3 lg:col-span-1">
                    <x-label for="programa_clave" value="Programa" />
                    <x-input id="programa_clave" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="programa_clave" />
                    <x-input-error for="programa_clave" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-3 lg:col-span-1">
                    <x-label for="subprograma" value="Subprograma" />
                    <x-input id="subprograma" type="number" min="0" max="99" class="mt-1 block w-full" wire:model="subprograma" />
                    <x-input-error for="subprograma" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-3 lg:col-span-1">
                    <x-label for="proyecto" value="Proyecto" />
                    <x-input id="proyecto" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="proyecto" />
                    <x-input-error for="proyecto" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-3 lg:col-span-1">
                    <x-label for="actividad" value="Actividad" />
                    <x-input id="actividad" type="number" min="0" max="999" class="mt-1 block w-full" wire:model="actividad" />
                    <x-input-error for="actividad" class="mt-1" />
                </div>
            </x-forms.section>

            <x-forms.section
                title="Clasificación Funcional (CONAC)"
                description="¿Para qué se gasta? Finalidad → Función → Subfunción (catálogo oficial CONAC).">
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="finalidad_id" value="Finalidad" />
                    <select id="finalidad_id" wire:model.live="finalidad_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">— Selecciona —</option>
                        @foreach ($this->finalidadesDisponibles as $finalidad)
                            <option value="{{ $finalidad->id }}">{{ $finalidad->clave }} · {{ $finalidad->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="finalidad_id" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="funcion_id" value="Función" />
                    <select id="funcion_id" wire:model.live="funcion_id" @disabled($finalidad_id === null)
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100">
                        <option value="">— Selecciona —</option>
                        @foreach ($this->funcionesDisponibles as $funcion)
                            <option value="{{ $funcion->id }}">{{ $funcion->clave }} · {{ $funcion->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="funcion_id" class="mt-1" />
                </div>
                <div class="col-span-6 sm:col-span-2">
                    <x-label for="subfuncion_id" value="Subfunción" />
                    <select id="subfuncion_id" wire:model.live="subfuncion_id" @disabled($funcion_id === null)
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm disabled:bg-gray-100">
                        <option value="">— Selecciona —</option>
                        @foreach ($this->subfuncionesDisponibles as $subfuncion)
                            <option value="{{ $subfuncion->id }}">{{ $subfuncion->clave }} · {{ $subfuncion->nombre }}</option>
                        @endforeach
                    </select>
                    <x-input-error for="subfuncion_id" class="mt-1" />
                </div>
            </x-forms.section>

            <div class="flex justify-end">
                <x-button type="submit">Guardar clave presupuestal</x-button>
            </div>
        </form>
    </x-page.container>
</div>
