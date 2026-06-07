<div>
    <x-page.header :title="$evaluacionExterna?->exists ? 'Editar evaluación externa' : 'Nueva evaluación externa'" />

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'Evaluaciones externas', 'url' => route('evaluation.externas.index')],
        ['label' => $evaluacionExterna?->exists ? 'Editar' : 'Nueva'],
    ]">
        <form wire:submit="save" class="space-y-6">
            <x-forms.section title="Identificación" description="Programa, ejercicio fiscal y tipo de evaluación">
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Programa presupuestario</span>
                    <select wire:model.live="form.programa_presupuestario_id" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach($programas as $programa)
                            <option value="{{ $programa->id }}">{{ $programa->clave }} — {{ $programa->nombre }}</option>
                        @endforeach
                    </select>
                    @error('form.programa_presupuestario_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Ejercicio fiscal</span>
                    <input type="number" min="2020" max="2050" wire:model.live="form.ejercicio_fiscal" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.ejercicio_fiscal') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Tipo de evaluación</span>
                    <select wire:model="form.tipo" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach(\App\Enums\TipoEvaluacionExterna::cases() as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    @error('form.tipo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Evaluador" description="Instancia externa y periodo de la evaluación">
                <label class="block col-span-6">
                    <span class="text-sm font-medium">Evaluador externo</span>
                    <input type="text" wire:model="form.evaluador_externo" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.evaluador_externo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Fecha de inicio</span>
                    <input type="date" wire:model="form.fecha_inicio" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.fecha_inicio') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Fecha de fin</span>
                    <input type="date" wire:model="form.fecha_fin" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.fecha_fin') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Estado y vínculo" description="Estado de la evaluación y cálculo interno asociado">
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Estado</span>
                    <select wire:model="form.estado" class="mt-1 block w-full rounded border-gray-300">
                        @foreach(\App\Enums\EstadoEvaluacionExterna::cases() as $estado)
                            <option value="{{ $estado->value }}">{{ $estado->label() }}</option>
                        @endforeach
                    </select>
                    @error('form.estado') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Vincular cálculo interno (opcional)</span>
                    <select wire:model="form.evaluacion_programa_id" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">— Sin vínculo —</option>
                        @foreach($evaluacionesPrograma as $ep)
                            <option value="{{ $ep->id }}">Cálculo #{{ $ep->id }} — {{ $ep->ejercicio_fiscal }}</option>
                        @endforeach
                    </select>
                    @error('form.evaluacion_programa_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('evaluation.externas.index') }}">Cancelar</x-ui.button.secondary>
                <x-ui.button.primary type="submit">Guardar</x-ui.button.primary>
            </x-page.form-footer>
        </form>
    </x-page.container>
</div>
