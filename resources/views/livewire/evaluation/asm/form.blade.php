<div>
    <x-page.header :title="$asm?->exists ? 'Editar ASM' : 'Nuevo ASM'" />

    <x-page.container :breadcrumbs="[
        ['label' => 'Inicio', 'url' => route('dashboard')],
        ['label' => 'ASM', 'url' => route('evaluation.asms.index')],
        ['label' => $asm?->exists ? 'Editar' : 'Nuevo'],
    ]">
        <form wire:submit="save" class="space-y-6">
            <x-forms.section title="Identificación" description="Programa y evaluación de origen">
                <label class="block col-span-6">
                    <span class="text-sm font-medium">Programa presupuestario</span>
                    <select wire:model="form.programa_presupuestario_id" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach(\App\Models\ProgramaPresupuestario::orderBy('clave')->get() as $programa)
                            <option value="{{ $programa->id }}">{{ $programa->clave }} — {{ $programa->nombre }}</option>
                        @endforeach
                    </select>
                    @error('form.programa_presupuestario_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>

                <label class="block col-span-6">
                    <span class="text-sm font-medium">Recomendación de origen <span class="text-gray-400">(opcional)</span></span>
                    <select wire:model="form.recomendacion_id" class="mt-1 block w-full rounded border-gray-300" @disabled($recomendaciones->isEmpty())>
                        <option value="">— Sin vínculo —</option>
                        @foreach($recomendaciones as $r)
                            <option value="{{ $r->id }}">{{ $r->etiqueta }}</option>
                        @endforeach
                    </select>
                    @if($recomendaciones->isEmpty())
                        <p class="text-xs text-gray-400">Sin recomendaciones de evaluación externa para el programa seleccionado.</p>
                    @endif
                    @error('form.recomendacion_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Descripción del aspecto y acción de mejora">
                <label class="block col-span-6">
                    <span class="text-sm font-medium">Descripción del aspecto</span>
                    <textarea wire:model="form.descripcion_aspecto" rows="4" class="mt-1 block w-full rounded border-gray-300"></textarea>
                    @error('form.descripcion_aspecto') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6">
                    <span class="text-sm font-medium">Acción de mejora</span>
                    <textarea wire:model="form.accion_mejora" rows="4" class="mt-1 block w-full rounded border-gray-300"></textarea>
                    @error('form.accion_mejora') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Clasificación">
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Tipo de plazo</span>
                    <select wire:model="form.tipo_plazo" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach(\App\Enums\TipoPlazoAsm::cases() as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    @error('form.tipo_plazo') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Tipo de acción</span>
                    <select wire:model="form.tipo_accion" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach(\App\Enums\TipoAccionAsm::cases() as $tipo)
                            <option value="{{ $tipo->value }}">{{ $tipo->label() }}</option>
                        @endforeach
                    </select>
                    @error('form.tipo_accion') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Responsable y plazo">
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Responsable</span>
                    <select wire:model="form.responsable_id" class="mt-1 block w-full rounded border-gray-300">
                        <option value="">—</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>
                        @endforeach
                    </select>
                    @error('form.responsable_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Área responsable</span>
                    <input type="text" wire:model="form.area_responsable" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.area_responsable') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Fecha compromiso</span>
                    <input type="date" wire:model="form.fecha_compromiso" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.fecha_compromiso') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-forms.section title="Seguimiento">
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Status</span>
                    <select wire:model="form.status" class="mt-1 block w-full rounded border-gray-300">
                        @foreach(\App\Enums\StatusAsm::cases() as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">% avance</span>
                    <input type="number" min="0" max="100" wire:model="form.porcentaje_avance" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.porcentaje_avance') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6">
                    <span class="text-sm font-medium">Observación último avance</span>
                    <textarea wire:model="form.observacion_ultimo_avance" rows="3" class="mt-1 block w-full rounded border-gray-300"></textarea>
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">Fecha cumplimiento (si cumplido)</span>
                    <input type="date" wire:model="form.fecha_cumplimiento" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.fecha_cumplimiento') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
                <label class="block col-span-6 sm:col-span-3">
                    <span class="text-sm font-medium">URL de evidencia</span>
                    <input type="url" wire:model="form.evidencia_url" class="mt-1 block w-full rounded border-gray-300">
                    @error('form.evidencia_url') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </label>
            </x-forms.section>

            <x-page.form-footer>
                <x-ui.button.secondary href="{{ route('evaluation.asms.index') }}">Cancelar</x-ui.button.secondary>
                <x-ui.button.primary type="submit">Guardar</x-ui.button.primary>
            </x-page.form-footer>
        </form>
    </x-page.container>
</div>
