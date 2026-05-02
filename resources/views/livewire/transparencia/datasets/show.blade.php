<x-page.container :title="$dataset->dataset_clave . ' — ' . $dataset->nombre" :subtitle="$dataset->periodo ? 'Periodo: ' . $dataset->periodo : 'Plantilla del catálogo'">

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <x-forms.section title="Metadatos">
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Clave</dt><dd class="text-sm font-mono">{{ $dataset->dataset_clave }}</dd></div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Sistema origen</dt><dd class="text-sm">{{ $dataset->sistema_origen }}</dd></div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Periodo</dt><dd class="text-sm">{{ $dataset->periodo ?? 'Plantilla' }}</dd></div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Status</dt>
                <dd class="text-sm">
                    <span class="inline-block rounded px-2 py-1 text-xs {{ match($dataset->status->value) {
                        'borrador' => 'bg-gray-100 text-gray-700',
                        'revision' => 'bg-yellow-100 text-yellow-800',
                        'aprobado' => 'bg-blue-100 text-blue-800',
                        'publicado' => 'bg-green-100 text-green-800',
                        'retirado' => 'bg-red-100 text-red-800',
                        default => 'bg-gray-100',
                    } }}">{{ $dataset->status->value }}</span>
                </dd>
            </div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Autor</dt><dd class="text-sm">{{ $dataset->creadoPor?->name ?? '—' }}</dd></div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Aprobado por</dt><dd class="text-sm">{{ $dataset->aprobadoPor?->name ?? '—' }} {{ $dataset->aprobado_en?->format('Y-m-d H:i') }}</dd></div>
            <div><dt class="text-xs font-semibold text-gray-500 uppercase">Publicado en</dt><dd class="text-sm">{{ $dataset->publicado_en?->format('Y-m-d H:i') ?? '—' }}</dd></div>
        </dl>
        <div class="mt-4">
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-1">Descripción</h4>
            <p class="text-sm">{{ $dataset->descripcion }}</p>
        </div>
        @if($dataset->motivo_cambio_estado)
            <div class="mt-4 p-3 bg-yellow-50 border-l-4 border-yellow-400">
                <p class="text-sm"><strong>Último motivo de cambio:</strong> {{ $dataset->motivo_cambio_estado }}</p>
            </div>
        @endif
    </x-forms.section>

    <x-forms.section title="Acciones">
        <div class="flex flex-wrap gap-2">
            @can('enviarARevision', $dataset)
                <button wire:click="enviarARevision" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                    Enviar a revisión
                </button>
            @endcan

            @can('aprobar', $dataset)
                <button wire:click="aprobar"
                        wire:confirm="¿Aprobar este dataset para publicación?"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Aprobar
                </button>
            @endcan

            @can('publicar', $dataset)
                <button wire:click="publicar"
                        wire:confirm="¿Publicar este dataset? Pasará a estado público."
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Publicar
                </button>
            @endcan

            @can('update', $dataset)
                <a href="{{ route('transparencia.datos-abiertos.edit', $dataset) }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                    Editar borrador
                </a>
            @endcan

            @if($dataset->periodo === null)
                @can('editarPlantilla', $dataset)
                    <a href="{{ route('transparencia.datos-abiertos.editar-plantilla', $dataset) }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded">
                        Editar plantilla
                    </a>
                @endcan
                @can('crearEntrega', $dataset)
                    <a href="{{ route('transparencia.datos-abiertos.crear-entrega', $dataset) }}"
                       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Crear entrega
                    </a>
                @endcan
            @endif
        </div>

        @can('rechazar', $dataset)
            <div class="mt-4 p-3 border-t pt-4">
                <label for="motivo_rechazo" class="block text-sm font-medium text-gray-700 mb-1">Motivo de rechazo</label>
                <textarea id="motivo_rechazo" wire:model="motivo" maxlength="500" rows="3"
                          class="w-full rounded border-gray-300 text-sm"
                          placeholder="Describe por qué se rechaza (mínimo 5 caracteres)..."></textarea>
                @error('motivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                <button wire:click="rechazar"
                        wire:confirm="¿Rechazar este dataset y devolverlo a borrador?"
                        class="mt-2 bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">
                    Rechazar
                </button>
            </div>
        @endcan

        @can('retirar', $dataset)
            <div class="mt-4 p-3 border-t pt-4">
                <label for="motivo_retiro" class="block text-sm font-medium text-gray-700 mb-1">Motivo de retiro</label>
                <textarea id="motivo_retiro" wire:model="motivo" maxlength="500" rows="3"
                          class="w-full rounded border-gray-300 text-sm"
                          placeholder="Describe por qué se retira de publicación (mínimo 5 caracteres)..."></textarea>
                @error('motivo') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                <button wire:click="retirar"
                        wire:confirm="¿Retirar este dataset de publicación?"
                        class="mt-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded">
                    Retirar
                </button>
            </div>
        @endcan
    </x-forms.section>

    <x-forms.section title="DCAT Metadata">
        <pre class="text-xs bg-gray-50 p-3 rounded overflow-x-auto">{{ json_encode($dataset->dcat_metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </x-forms.section>

    <x-forms.section title="Actividad reciente">
        <ul class="divide-y divide-gray-100">
            @forelse($actividad as $log)
                <li class="py-2 text-sm">
                    <span class="font-medium">{{ $log->description }}</span>
                    <span class="text-gray-500"> — {{ $log->created_at?->format('Y-m-d H:i') }}</span>
                    @if($log->causer)
                        <span class="text-gray-500"> por {{ $log->causer->name }}</span>
                    @endif
                </li>
            @empty
                <li class="py-2 text-sm text-gray-500">Sin actividad registrada.</li>
            @endforelse
        </ul>
    </x-forms.section>

    <div class="mt-4">
        <a href="{{ route('transparencia.datos-abiertos.index') }}" class="text-sm text-blue-600 hover:underline">← Volver a la lista</a>
    </div>
</x-page.container>
