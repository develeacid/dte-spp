<x-page.container>
    <x-page.header
        title="Cobertura geográfica"
        subtitle="{{ $programa->clave }} — {{ $programa->nombre }}"
    />

    <div class="space-y-6">
        @if ($estado === 'inactivo')
            <div class="rounded-md bg-amber-50 border border-amber-200 p-4">
                <p class="text-sm text-amber-800">
                    El padrón de este programa no está activo en GeoBase.
                    Activarlo en el tab Padrón para ver cobertura.
                </p>
            </div>
        @endif
    </div>
</x-page.container>
