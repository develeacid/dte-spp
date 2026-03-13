<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sabana de Captura</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 9px; }
        .estado-pendiente { background-color: #edf2f7; }
        .estado-en_captura { background-color: #ebf8ff; }
        .estado-en_revision { background-color: #fefcbf; }
        .estado-aprobado { background-color: #c6f6d5; }
        .estado-observado { background-color: #feebc8; }
        .estado-vencido { background-color: #fed7d7; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] ?? 'Gobierno del Estado' }}</h2>
        <h3>{{ $encabezado['dependencia'] ?? '' }}</h3>
        <h3>Sabana de Captura</h3>
        @if($filtroTrimestre)
            <p>Trimestre: T{{ $filtroTrimestre }}</p>
        @endif
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Indicador</th>
                <th>T</th>
                <th>Meta</th>
                <th>Estado</th>
                <th>Operador</th>
                <th>Cierre</th>
            </tr>
        </thead>
        <tbody>
            @forelse($filas as $fila)
                @php
                    $estadoLabel = match($fila['estado']) {
                        'pendiente' => 'Pendiente',
                        'en_captura' => 'En captura',
                        'en_revision' => 'En revision',
                        'aprobado' => 'Aprobado',
                        'observado' => 'Observado',
                        'vencido' => 'Vencido',
                        default => $fila['estado'],
                    };
                @endphp
                <tr class="estado-{{ $fila['estado'] }}">
                    <td>{{ $fila['programa_clave'] }}</td>
                    <td>{{ $fila['indicador'] }}</td>
                    <td style="text-align: center;">T{{ $fila['periodo'] }}</td>
                    <td style="text-align: center;">{{ $fila['meta_periodo'] }}</td>
                    <td>{{ $estadoLabel }}</td>
                    <td>{{ $fila['operador'] }}</td>
                    <td>{{ $fila['fecha_cierre'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #999;">Sin registros</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
