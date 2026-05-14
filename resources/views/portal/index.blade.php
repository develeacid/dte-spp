@extends('layouts.portal')
@section('title', 'Datasets')
@section('content')
    <h1 class="text-3xl font-bold mb-2">Catálogo de datos abiertos</h1>
    <p class="text-portal-muted mb-8">{{ $catalogo->count() }} dataset(s) publicado(s).</p>

    @if($catalogo->isEmpty())
        <div class="bg-portal-surface border border-portal-border rounded-lg p-8 text-center text-portal-muted">
            Aún no hay datasets publicados.
        </div>
    @else
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($catalogo as $ds)
                <article class="bg-portal-surface border border-portal-border rounded-lg p-5">
                    <div class="flex items-start justify-between mb-2">
                        <span class="text-xs font-mono text-portal-muted">{{ $ds->codigo }}</span>
                        @if($ds->fecha_publicacion)
                            <span class="text-xs text-portal-muted">{{ $ds->fecha_publicacion->isoFormat('D MMM YYYY') }}</span>
                        @endif
                    </div>
                    <h2 class="font-semibold mb-2">{{ $ds->titulo }}</h2>
                    <p class="text-sm text-portal-muted mb-4 line-clamp-3">{{ $ds->descripcion }}</p>
                    @if($ds->codigo === 'DS-01')
                        <span class="text-xs text-portal-accent italic">Disponible</span>
                    @else
                        <span class="text-xs text-portal-muted italic">Próximamente</span>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
@endsection
