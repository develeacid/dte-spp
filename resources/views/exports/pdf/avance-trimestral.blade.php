<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Avance Trimestral - {{ $programa->clave }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 9px; }
        .semaforo-verde { background-color: #c6f6d5; }
        .semaforo-amarillo { background-color: #fefcbf; }
        .semaforo-rojo { background-color: #fed7d7; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Reporte de Avance Trimestral</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
        <p>Ejercicio Fiscal: {{ $ejercicioFiscal }} | Trimestre: {{ $trimestre }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th>Nivel</th>
                <th>Indicador</th>
                <th>Meta Anual</th>
                <th>Meta Trim.</th>
                <th>Resultado</th>
                <th>Avance %</th>
                <th>Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($niveles as $nivel)
                @foreach($nivel->indicadores as $indicador)
                    @php
                        $meta = $indicador->metasPeriodo->first();
                        $avance = $indicador->avances->first();
                        $porcentaje = ($meta?->meta_periodo > 0 && $avance?->resultado !== null)
                            ? round(($avance->resultado / $meta->meta_periodo) * 100, 2)
                            : null;
                        $semaforo = $avance?->semaforo_calculado ?? 'sin dato';
                    @endphp
                    <tr>
                        <td>{{ $nivel->tipo_nivel->label() }}</td>
                        <td>{{ $indicador->nombre }}</td>
                        <td>{{ $indicador->meta }}</td>
                        <td>{{ $meta?->meta_periodo ?? 'N/A' }}</td>
                        <td>{{ $avance?->resultado ?? 'N/A' }}</td>
                        <td>{{ $porcentaje !== null ? $porcentaje . '%' : 'N/A' }}</td>
                        <td class="semaforo-{{ $semaforo }}">{{ ucfirst($semaforo) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
