<div>
    <x-page.header :title="$partida && $partida->exists ? 'Editar Partida' : 'Nueva Partida Presupuestal'" />

    <x-page.container>
        <x-forms.section title="Datos de la Partida" description="Información básica de la partida presupuestal según el Clasificador por Objeto del Gasto (COG).">
            <div class="col-span-6 sm:col-span-3">
                <x-label for="programa_presupuestario_id" value="Programa Presupuestario" />
                <select id="programa_presupuestario_id" wire:model="programa_presupuestario_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Seleccionar programa...</option>
                    @foreach ($programas as $programa)
                        <option value="{{ $programa->id }}">{{ $programa->clave }} — {{ $programa->nombre }}</option>
                    @endforeach
                </select>
                @error('programa_presupuestario_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="ejercicio_fiscal" value="Ejercicio Fiscal" />
                <select id="ejercicio_fiscal" wire:model.live="ejercicio_fiscal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    @for ($y = now()->year + 1; $y >= now()->year - 2; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                @error('ejercicio_fiscal') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-2">
                <x-label for="clave_partida" value="Clave de Partida (COG)" />
                <x-input id="clave_partida" type="text" class="mt-1 block w-full" wire:model="clave_partida" placeholder="Ej: 1000" />
                @error('clave_partida') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="descripcion" value="Descripción" />
                <x-input id="descripcion" type="text" class="mt-1 block w-full" wire:model="descripcion" placeholder="Ej: Servicios Personales" />
                @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="monto_aprobado" value="Monto Aprobado" />
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                    <x-input id="monto_aprobado" type="number" step="0.01" min="0" class="block w-full pl-7" wire:model="monto_aprobado" />
                </div>
                @error('monto_aprobado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="monto_modificado" value="Monto Modificado (opcional)" />
                <div class="relative mt-1">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">$</span>
                    <x-input id="monto_modificado" type="number" step="0.01" min="0" class="block w-full pl-7" wire:model="monto_modificado" placeholder="Después de adecuaciones" />
                </div>
                @error('monto_modificado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </x-forms.section>

        <x-page.form-footer>
            <x-ui.button.secondary href="{{ route('presupuesto.partidas') }}">Cancelar</x-ui.button.secondary>
            <x-ui.button.primary wire:click="save">
                {{ $partida && $partida->exists ? 'Guardar Cambios' : 'Crear Partida' }}
            </x-ui.button.primary>
        </x-page.form-footer>
    </x-page.container>
</div>
