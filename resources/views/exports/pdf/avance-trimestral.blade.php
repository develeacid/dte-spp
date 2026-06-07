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
        .semaforo-rojo_alto { background-color: #e9d5ff; }
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
                        <td class="semaforo-{{ $semaforo }}">{{ $semaforo === 'rojo_alto' ? 'Rojo alto' : ucfirst($semaforo) }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    @isset($partidas)
        @if($partidas->count() > 0)
            <div style="page-break-before: always;"></div>
            <h2 style="font-size: 14pt; margin-top: 20px;">Componente Financiero — T{{ $trimestre }}</h2>
            <table style="width:100%; border-collapse: collapse; font-size: 9pt;">
                <thead>
                    <tr>
                        <th>Clave</th>
                        <th>Descripción</th>
                        <th>Aprobado</th>
                        <th>Modificado</th>
                        <th>Comprometido</th>
                        <th>Devengado</th>
                        <th>Pagado</th>
                        <th>% Ejercido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partidas as $partida)
                        <tr>
                            <td>{{ $partida['clave_partida'] }}</td>
                            <td>{{ $partida['descripcion'] }}</td>
                            <td style="text-align: right;">{{ number_format($partida['monto_aprobado'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($partida['monto_modificado'] ?? $partida['monto_aprobado'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($partida['monto_comprometido'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($partida['monto_devengado'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($partida['monto_pagado'], 2) }}</td>
                            <td style="text-align: right;">{{ number_format($partida['porcentaje_ejercido'], 2) }}%</td>
                        </tr>
                    @endforeach
                    <tr style="font-weight: bold; background: #f0f0f0;">
                        <td>TOTAL</td>
                        <td></td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['aprobado'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['modificado'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['comprometido'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['devengado'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['pagado'], 2) }}</td>
                        <td style="text-align: right;">{{ number_format($totalesFinancieros['porcentaje_ejercido'], 2) }}%</td>
                    </tr>
                </tbody>
            </table>
        @endif
    @endisset

    @if (! empty($evidenciasPadron))
        <h3 style="margin-top: 24px;">Evidencia de Padrón (GeoBase)</h3>
        <table>
            <thead>
                <tr>
                    <th>Componente</th>
                    <th>Resumen narrativo</th>
                    <th>Snapshot ID</th>
                    <th>Hash SHA-256</th>
                    <th>Fecha de corte</th>
                    <th>Origen</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($evidenciasPadron as $row)
                    <tr>
                        <td>{{ $row['componente'] }}</td>
                        <td>{{ $row['narrativa'] }}</td>
                        <td>{{ $row['snapshot_id'] }}</td>
                        <td style="font-family: monospace; font-size: 8px;">{{ $row['hash'] }}</td>
                        <td>{{ $row['fecha'] }}</td>
                        <td>{{ $row['origen'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @include('exports.pdf.partials.vobo', [
        'titular' => $titular,
        'dependencia' => $dependencia,
        'fecha' => $fecha,
    ])
</body>
</html>
