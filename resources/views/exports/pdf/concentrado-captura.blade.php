<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Concentrado de Captura</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 9px; }
        td.num { text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] ?? 'Gobierno del Estado' }}</h2>
        <h3>{{ $encabezado['dependencia'] ?? '' }}</h3>
        <h3>Concentrado de Captura</h3>
        @if($fechaDesde || $fechaHasta)
            <p>Periodo: {{ $fechaDesde ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') : '—' }} al {{ $fechaHasta ? \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : '—' }}</p>
        @endif
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Programa</th>
                <th>Indicador</th>
                <th style="text-align: center;">Total</th>
                <th style="text-align: center;">Aprobados</th>
                <th style="text-align: center;">En Revision</th>
                <th style="text-align: center;">En Captura</th>
                <th style="text-align: center;">Observados</th>
            </tr>
        </thead>
        <tbody>
            @forelse($agrupado as $fila)
                <tr>
                    <td>{{ $fila['programa_clave'] }}</td>
                    <td>{{ $fila['indicador'] }}</td>
                    <td class="num">{{ $fila['total'] }}</td>
                    <td class="num">{{ $fila['aprobados'] }}</td>
                    <td class="num">{{ $fila['en_revision'] }}</td>
                    <td class="num">{{ $fila['en_captura'] }}</td>
                    <td class="num">{{ $fila['observados'] }}</td>
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
