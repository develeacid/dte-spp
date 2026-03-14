<div>
    <x-page.header :title="$fundamento && $fundamento->exists ? 'Editar Fundamento' : 'Nuevo Fundamento Jurídico'" />

    <x-page.container>
        <x-forms.section title="Datos del fundamento" description="Registre el ordenamiento legal que sustenta este programa presupuestario.">
            <div class="col-span-6 sm:col-span-3">
                <x-label for="tipo" value="Tipo de sustento" />
                <select id="tipo" wire:model="tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Seleccionar...</option>
                    @foreach ($tiposSustento as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
                @error('tipo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="nivel_jerarquia" value="Nivel de jerarquía" />
                <select id="nivel_jerarquia" wire:model="nivel_jerarquia" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Seleccionar...</option>
                    @foreach ($nivelesJerarquia as $n)
                        <option value="{{ $n->value }}">{{ $n->label() }}</option>
                    @endforeach
                </select>
                @error('nivel_jerarquia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="catalogo_ordenamiento_id" value="Ordenamiento (catálogo)" />
                <select id="catalogo_ordenamiento_id" wire:model.live="catalogo_ordenamiento_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">— Seleccionar del catálogo o escribir abajo —</option>
                    @foreach ($catalogoOrdenamientos as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->abreviatura }} — {{ $cat->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <x-label for="ordenamiento" value="Nombre del ordenamiento" />
                <x-input id="ordenamiento" type="text" class="mt-1 block w-full" wire:model="ordenamiento" placeholder="Ej: Ley Orgánica del Poder Ejecutivo del Estado de Oaxaca" />
                @error('ordenamiento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-2">
                <x-label for="articulo" value="Artículo(s)" />
                <x-input id="articulo" type="text" class="mt-1 block w-full" wire:model="articulo" placeholder="Ej: Art. 45, Frac. III" />
                @error('articulo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6">
                <x-label for="descripcion" value="Descripción (cómo faculta al programa)" />
                <textarea id="descripcion" wire:model="descripcion" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white" placeholder="Explique cómo este ordenamiento faculta o sustenta el programa..."></textarea>
                @error('descripcion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" wire:model="vigente" class="rounded border-gray-300 text-brand focus:ring-brand dark:border-gray-600">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Vigente</span>
                </label>
            </div>
        </x-forms.section>

        <x-page.form-footer>
            <x-ui.button.secondary href="{{ route('juridico.programa', $programa) }}">Cancelar</x-ui.button.secondary>
            <x-ui.button.primary wire:click="save">
                {{ $fundamento && $fundamento->exists ? 'Guardar Cambios' : 'Registrar Fundamento' }}
            </x-ui.button.primary>
        </x-page.form-footer>
    </x-page.container>
</div>
