@props([
    'modoFuente' => 'snapshot',
    'modoVivoDisponible' => false,
    'snapshot' => null,
])

<div @class([
    'rounded-lg border p-3 text-sm',
    'bg-blue-50 border-blue-200 text-blue-900' => $modoFuente === 'snapshot',
    'bg-yellow-50 border-yellow-200 text-yellow-900' => $modoFuente === 'vivo',
])>
    <div class="flex items-start justify-between gap-3">
        <div>
            @if ($modoFuente === 'snapshot' && $snapshot)
                <p>
                    <strong>🔒 Fuente:</strong>
                    snapshot {{ \Illuminate\Support\Str::of($snapshot['cutoff_date'] ?? '')->limit(10, '') }}
                    <span class="font-mono text-xs">
                        ({{ \Illuminate\Support\Str::substr($snapshot['snapshot_hash'] ?? '', 0, 8) }}…)
                    </span>
                </p>
            @elseif ($modoFuente === 'snapshot')
                <p><strong>🔒 Fuente:</strong> sin snapshot disponible para este Componente</p>
            @else
                <p><strong>⚡ Fuente:</strong> consulta en vivo ({{ now()->format('H:i') }})</p>
            @endif
            @unless ($modoVivoDisponible)
                <p class="text-xs opacity-75 mt-1">Modo "vivo" disponible próximamente.</p>
            @endunless
        </div>
    </div>
</div>
