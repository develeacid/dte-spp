<div>
    <x-page.header :title="'Documentos Normativos — ' . $programa->clave" />

    <x-page.container>
        @if (session('message'))
            <div class="mb-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-900/20 dark:text-green-300">{{ session('message') }}</div>
        @endif

        {{-- Formulario de upload --}}
        <x-forms.section title="Subir documento" description="Suba archivos PDF de reglas de operación, leyes o reglamentos (máx {{ config('juridico.max_upload_size_mb', 10) }}MB).">
            <div class="col-span-6 sm:col-span-3">
                <x-label for="tipo_documento" value="Tipo de documento" />
                <select id="tipo_documento" wire:model="tipo_documento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand focus:ring-brand dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Seleccionar...</option>
                    @foreach ($tiposDocumento as $t)
                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                    @endforeach
                </select>
                @error('tipo_documento') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="nombre" value="Nombre del documento" />
                <x-input id="nombre" type="text" class="mt-1 block w-full" wire:model="nombre" placeholder="Ej: ROP Programa Fomento Productivo 2026" />
                @error('nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="fecha_publicacion" value="Fecha de publicación" />
                <x-input id="fecha_publicacion" type="date" class="mt-1 block w-full" wire:model="fecha_publicacion" />
                @error('fecha_publicacion') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6 sm:col-span-3">
                <x-label for="fecha_vigencia" value="Fecha de vigencia" />
                <x-input id="fecha_vigencia" type="date" class="mt-1 block w-full" wire:model="fecha_vigencia" />
                @error('fecha_vigencia') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-6">
                <x-label for="archivo" value="Archivo PDF" />
                <input id="archivo" type="file" wire:model="archivo" accept=".pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-brand file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-brand-dark dark:text-gray-400" />
                @error('archivo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <div wire:loading wire:target="archivo" class="mt-2 text-sm text-gray-500">
                    Subiendo archivo...
                </div>
            </div>

            <div class="col-span-6">
                <x-ui.button.primary wire:click="upload" wire:loading.attr="disabled">Subir documento</x-ui.button.primary>
            </div>
        </x-forms.section>

        {{-- Lista de documentos existentes --}}
        <x-forms.section title="Documentos registrados" description="Documentos normativos asociados a este programa.">
            <div class="col-span-6">
                @forelse ($documentos as $doc)
                    <div class="mb-2 flex items-center justify-between rounded-md border border-gray-200 p-3 dark:border-gray-700">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $doc->nombre }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $doc->tipo_documento->label() }} — {{ $doc->archivo_size_humano }}
                                @if ($doc->fecha_publicacion) — Publicado: {{ $doc->fecha_publicacion->format('d/m/Y') }} @endif
                                @if ($doc->fecha_vigencia) — Vigencia: {{ $doc->fecha_vigencia->format('d/m/Y') }} @endif
                            </p>
                            <p class="text-xs text-gray-400">
                                Por {{ $doc->registrador?->name }}
                                @if ($doc->verificado)
                                    — <span class="text-green-600">Verificado por {{ $doc->verificador?->name }}</span>
                                @else
                                    — <span class="text-yellow-600">Pendiente de verificación</span>
                                @endif
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('juridico.documento.download', $doc) }}" class="text-sm text-brand hover:text-brand-dark">Descargar</a>
                            @if (!$doc->verificado)
                                <button wire:click="verificar({{ $doc->id }})" class="text-sm text-green-600 hover:text-green-800">Verificar</button>
                            @endif
                            <button wire:click="eliminar({{ $doc->id }})" wire:confirm="Eliminar este documento?" class="text-sm text-red-600 hover:text-red-800">Eliminar</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No hay documentos registrados.</p>
                @endforelse
            </div>
        </x-forms.section>

        <x-page.form-footer>
            <x-ui.button.secondary href="{{ route('juridico.programa', $programa) }}">Volver al programa</x-ui.button.secondary>
        </x-page.form-footer>
    </x-page.container>
</div>
