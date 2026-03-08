<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MIR - {{ $programa->clave }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; }
        th { background-color: #2d3748; color: white; font-size: 9px; }
        .nivel-fin { background-color: #ebf4ff; }
        .nivel-proposito { background-color: #e6fffa; }
        .nivel-componente { background-color: #fffbeb; }
        .nivel-actividad { background-color: #f5f3ff; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Matriz de Indicadores para Resultados (MIR)</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
        <p>Ejercicio Fiscal: {{ $ejercicioFiscal }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Nivel</th>
                <th style="width: 22%;">Resumen Narrativo</th>
                <th style="width: 18%;">Indicador(es)</th>
                <th style="width: 18%;">Medios de Verificación</th>
                <th style="width: 15%;">Supuestos</th>
                <th style="width: 8%;">Meta</th>
                <th style="width: 9%;">Fórmula</th>
            </tr>
        </thead>
        <tbody>
            @foreach($niveles as $nivel)
                @php
                    $rowClass = 'nivel-' . $nivel->tipo_nivel->value;
                @endphp
                <tr class="{{ $rowClass }}">
                    <td><strong>{{ $nivel->tipo_nivel->label() }}</strong></td>
                    <td>{{ $nivel->resumen_narrativo }}</td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            <div>{{ $ind->nombre }}</div>
                            @if(!$loop->last)<hr style="margin: 2px 0;">@endif
                        @endforeach
                    </td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            @foreach($ind->mediosVerificacion as $mv)
                                <div>{{ $mv->descripcion }}</div>
                            @endforeach
                            @if(!$loop->last)<hr style="margin: 2px 0;">@endif
                        @endforeach
                    </td>
                    <td>{{ $nivel->supuestos }}</td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            <div>{{ $ind->meta }}</div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($nivel->indicadores as $ind)
                            <div>{{ $ind->formula_texto }}</div>
                        @endforeach
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
