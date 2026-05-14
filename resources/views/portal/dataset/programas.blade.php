@extends('layouts.portal')
@section('title', $dataset->titulo)
@section('content')
    <div class="mb-6">
        <a href="{{ route('portal.index') }}" class="text-sm text-portal-muted hover:text-portal-text">← Volver al catálogo</a>
    </div>

    <div class="flex items-start justify-between mb-6">
        <div>
            <span class="text-xs font-mono text-portal-muted">{{ $dataset->codigo }}</span>
            <h1 class="text-3xl font-bold">{{ $dataset->titulo }}</h1>
            <p class="text-portal-muted mt-2">{{ $dataset->descripcion }}</p>
        </div>
        <a href="{{ route('portal.dataset.download', $dataset->codigo) }}"
           class="bg-portal-accent text-white px-4 py-2 rounded text-sm font-medium hover:opacity-90">
            Descargar CSV
        </a>
    </div>

    @if($programas->isEmpty())
        <div class="bg-portal-surface border border-portal-border rounded-lg p-8 text-center text-portal-muted">
            No hay registros en este dataset todavía.
        </div>
    @else
        <div class="bg-portal-surface border border-portal-border rounded-lg overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-portal-border text-portal-muted">
                    <tr>
                        <th class="text-left px-4 py-3">Ejercicio</th>
                        <th class="text-left px-4 py-3">Clave</th>
                        <th class="text-left px-4 py-3">Programa</th>
                        <th class="text-left px-4 py-3">Unidad responsable</th>
                        <th class="text-left px-4 py-3">Modalidad</th>
                        <th class="text-left px-4 py-3">Activo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-portal-border">
                    @foreach($programas as $p)
                        <tr>
                            <td class="px-4 py-3">{{ $p->ejercicio_fiscal }}</td>
                            <td class="px-4 py-3 font-mono">{{ $p->programa_clave }}</td>
                            <td class="px-4 py-3">{{ $p->programa_nombre }}</td>
                            <td class="px-4 py-3 text-portal-muted">{{ $p->unidad_responsable }}</td>
                            <td class="px-4 py-3">{{ $p->modalidad ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $p->activo ? 'Sí' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $programas->links() }}</div>
    @endif
@endsection
