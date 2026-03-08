<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Evaluación Anual - {{ $programa->clave }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background-color: #2d3748; color: white; }
        .section-title { background-color: #edf2f7; padding: 8px; font-weight: bold; font-size: 12px; margin-top: 20px; border-left: 4px solid #2d3748; }
        .indice { font-size: 24px; font-weight: bold; text-align: center; margin: 15px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Evaluación Anual del Programa</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
        <p>Ejercicio Fiscal: {{ $evaluacion->ejercicio_fiscal }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <div class="indice">Índice de Eficacia: {{ number_format($evaluacion->indice_eficacia, 2) }}%</div>

    <div class="section-title">Desglose por Nivel</div>
    <table>
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Peso</th>
                <th>Promedio Eficacia</th>
                <th>Indicadores Evaluados</th>
                <th>Indicadores No Evaluados</th>
            </tr>
        </thead>
        <tbody>
            @foreach(['fin', 'proposito', 'componente', 'actividad'] as $nv)
                @php $data = $evaluacion->desglose_niveles[$nv] ?? []; @endphp
                <tr>
                    <td>{{ ucfirst($nv) }}</td>
                    <td>{{ ($data['peso'] ?? 0) * 100 }}%</td>
                    <td>{{ isset($data['promedio']) ? number_format($data['promedio'], 2) . '%' : 'N/A' }}</td>
                    <td>{{ $data['indicadores_evaluados'] ?? 0 }}</td>
                    <td>{{ $data['indicadores_no_evaluados'] ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Semáforos</div>
    <table>
        <tr>
            <th>Verde</th><td>{{ $evaluacion->conteo_semaforos['verde'] ?? 0 }}</td>
            <th>Amarillo</th><td>{{ $evaluacion->conteo_semaforos['amarillo'] ?? 0 }}</td>
            <th>Rojo</th><td>{{ $evaluacion->conteo_semaforos['rojo'] ?? 0 }}</td>
            <th>Sin dato</th><td>{{ $evaluacion->conteo_semaforos['sin_dato'] ?? 0 }}</td>
        </tr>
    </table>

    @if($evaluacion->analisis_ia)
        <div class="section-title">Análisis IA</div>
        <p>{{ $evaluacion->analisis_ia }}</p>
    @endif
</body>
</html>
