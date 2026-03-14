<div>
    <x-page.header title="Importar Presupuesto" />

    <x-page.container>
        @if ($paso === 'upload')
            <x-forms.section title="Carga de Archivo CSV" description="Formato requerido: clave_programa, clave_partida, descripcion, monto_aprobado, monto_modificado (opcional).">
                <div class="col-span-6 sm:col-span-3">
                    <x-label for="ejercicioFiscal" value="Ejercicio Fiscal" />
                    <select id="ejercicioFiscal" wire:model="ejercicioFiscal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-span-6">
                    <x-label for="archivo" value="Archivo CSV" />
                    <input id="archivo" type="file" wire:model="archivo" accept=".csv,.txt" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-brand-dark dark:text-gray-400" />
                    @error('archivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </x-forms.section>

            @if (!empty($errores))
                <div class="mt-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-400">Errores de validación:</h4>
                    <ul class="mt-2 list-inside list-disc text-sm text-red-700 dark:text-red-300">
                        @foreach ($errores as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('presupuesto.partidas') }}">Cancelar</x-ui.button.secondary>
                <x-ui.button.primary wire:click="procesar" wire:loading.attr="disabled">Procesar CSV</x-ui.button.primary>
            </x-page.form-footer>

        @elseif ($paso === 'preview')
            <div class="mb-4 rounded-md bg-blue-50 p-4 dark:bg-blue-900/20">
                <p class="text-sm text-blue-800 dark:text-blue-300">
                    Se encontraron <strong>{{ count($preview) }}</strong> registros.
                    @if (!empty($errores))
                        <span class="text-red-600">{{ count($errores) }} con errores (se omitirán).</span>
                    @endif
                </p>
            </div>

            @if (!empty($errores))
                <div class="mb-4 rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <ul class="list-inside list-disc text-sm text-red-700 dark:text-red-300">
                        @foreach ($errores as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Programa</th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Partida</th>
                            <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">Descripción</th>
                            <th class="px-4 py-2 text-right text-xs font-medium uppercase text-gray-500">Aprobado</th>
                            <th class="px-4 py-2 text-center text-xs font-medium uppercase text-gray-500">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @foreach ($preview as $item)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $item['clave_programa'] }}</td>
                                <td class="px-4 py-2 text-sm font-mono">{{ $item['clave_partida'] }}</td>
                                <td class="px-4 py-2 text-sm">{{ Str::limit($item['descripcion'], 40) }}</td>
                                <td class="px-4 py-2 text-right text-sm font-mono">${{ number_format($item['monto_aprobado'], 2) }}</td>
                                <td class="px-4 py-2 text-center">
                                    <span @class([
                                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                        'bg-green-100 text-green-800' => $item['accion'] === 'crear',
                                        'bg-yellow-100 text-yellow-800' => $item['accion'] === 'actualizar',
                                    ])>{{ ucfirst($item['accion']) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <x-page.form-footer>
                <x-ui.button.secondary wire:click="reiniciar">Cancelar</x-ui.button.secondary>
                <x-ui.button.primary wire:click="confirmar" wire:confirm="¿Confirmar la importación de {{ count($preview) }} registros?">
                    Confirmar Importación
                </x-ui.button.primary>
            </x-page.form-footer>

        @elseif ($paso === 'resultado')
            <div class="rounded-lg border border-gray-200 bg-white p-8 text-center dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Importación Completada</h3>
                <div class="mt-4 flex justify-center gap-8">
                    <div>
                        <p class="text-2xl font-bold text-green-600">{{ $creados }}</p>
                        <p class="text-sm text-gray-500">Creados</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-yellow-600">{{ $actualizados }}</p>
                        <p class="text-sm text-gray-500">Actualizados</p>
                    </div>
                    @if ($erroresCount > 0)
                        <div>
                            <p class="text-2xl font-bold text-red-600">{{ $erroresCount }}</p>
                            <p class="text-sm text-gray-500">Errores</p>
                        </div>
                    @endif
                </div>
                <div class="mt-6 flex justify-center gap-4">
                    <x-ui.button.secondary wire:click="reiniciar">Nueva Importación</x-ui.button.secondary>
                    <x-ui.button.primary href="{{ route('presupuesto.partidas') }}">Ver Partidas</x-ui.button.primary>
                </div>
            </div>
        @endif
    </x-page.container>
</div>
