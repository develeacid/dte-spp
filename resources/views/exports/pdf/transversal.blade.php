<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Transversal</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 9px; }
        .anexo-header { background-color: #edf2f7; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Reporte Transversal — {{ ucfirst($tipo) }}</h3>
        <p>Ejercicio Fiscal: {{ $ejercicioFiscal }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Anexo Transversal</th>
                <th>Programa</th>
                <th>Nivel</th>
                <th>Indicador</th>
                <th>Meta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($datos as $anexo)
                @foreach($anexo->indicadores as $indicador)
                    <tr>
                        <td>{{ $anexo->nombre }} ({{ $anexo->clave }})</td>
                        <td>{{ $indicador->mirNivel?->programa?->clave }}</td>
                        <td>{{ $indicador->mirNivel?->tipo_nivel?->label() }}</td>
                        <td>{{ $indicador->nombre }}</td>
                        <td>{{ $indicador->meta }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
