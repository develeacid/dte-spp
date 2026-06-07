<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>FMyE - {{ $programa->clave }}</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 4px 6px; text-align: left; vertical-align: top; font-size: 9px; }
        th { background-color: #2d3748; color: white; font-size: 9px; }

        .section-title {
            background-color: #2d3748;
            color: white;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 0;
        }

        .nivel-fin { background-color: #dbeafe; }
        .nivel-proposito { background-color: #d1fae5; }
        .nivel-componente { background-color: #fef3c7; }
        .nivel-actividad { background-color: #ede9fe; }

        .semaforo-verde { background-color: #c6f6d5; }
        .semaforo-amarillo { background-color: #fefcbf; }
        .semaforo-rojo { background-color: #fed7d7; }
        .semaforo-rojo_alto { background-color: #e9d5ff; }

        .indice-grande {
            font-size: 36px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
        }

        .sin-datos {
            text-align: center;
            color: #999;
            padding: 15px;
            font-style: italic;
        }

        .barra-semaforo {
            display: table;
            width: 100%;
            margin-top: 8px;
        }
        .barra-semaforo .segmento {
            display: table-cell;
            text-align: center;
            padding: 4px;
            font-weight: bold;
            font-size: 10px;
        }
    </style>
</head>
<body>
    {{-- HEADER --}}
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $dependencia }}</h3>
        <h3>Ficha de Monitoreo y Evaluación (FMyE)</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
        <p>Ejercicio Fiscal: {{ $ejercicioFiscal }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    {{-- SECCIÓN 1: DATOS GENERALES --}}
    <div class="section-title">1. Datos Generales</div>
    <table>
        <tr>
            <th style="width: 25%;">Clave</th>
            <td>{{ $programa->clave }}</td>
        </tr>
        <tr>
            <th>Nombre</th>
            <td>{{ $programa->nombre }}</td>
        </tr>
        <tr>
            <th>Unidad Responsable</th>
            <td>{{ $team->name }} ({{ $team->clave_ur }})</td>
        </tr>
        <tr>
            <th>Titular</th>
            <td>{{ $team->titular }}</td>
        </tr>
        <tr>
            <th>Ejercicio Fiscal</th>
            <td>{{ $ejercicioFiscal }}</td>
        </tr>
    </table>

    {{-- SECCIÓN 2: ALINEACIÓN ESTRATÉGICA --}}
    <div class="section-title">2. Alineación Estratégica</div>
    @if(count($alineacion) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">PED Objetivo Estratégico</th>
                    <th style="width: 30%;">PND Objetivo</th>
                    <th style="width: 30%;">ODS Meta</th>
                </tr>
            </thead>
            <tbody>
                @foreach($alineacion as $entry)
                    <tr>
                        <td>{{ $entry['ped'] }}</td>
                        <td>
                            @if(count($entry['pnd']) > 0)
                                @foreach($entry['pnd'] as $pnd)
                                    {{ $pnd }}<br>
                                @endforeach
                            @else
                                <span style="color: #999;">—</span>
                            @endif
                        </td>
                        <td>
                            @if(count($entry['ods']) > 0)
                                @foreach($entry['ods'] as $ods)
                                    {{ $ods }}<br>
                                @endforeach
                            @else
                                <span style="color: #999;">—</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sin-datos">Sin alineaciones registradas</div>
    @endif

    {{-- SECCIÓN 3: RESUMEN MIR --}}
    <div class="section-title">3. Resumen MIR</div>
    <table>
        <thead>
            <tr>
                <th style="width: 15%;">Nivel</th>
                <th style="width: 35%;">Resumen Narrativo</th>
                <th style="width: 30%;">Indicador</th>
                <th style="width: 20%;">Semáforo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($niveles as $nivel)
                @php
                    $nivelClass = 'nivel-' . $nivel->tipo_nivel->value;
                @endphp
                @if($nivel->indicadores->isEmpty())
                    <tr>
                        <td class="{{ $nivelClass }}">{{ $nivel->tipo_nivel->label() }}</td>
                        <td>{{ $nivel->resumen_narrativo }}</td>
                        <td colspan="2" style="color: #999; text-align: center;">Sin indicadores</td>
                    </tr>
                @else
                    @foreach($nivel->indicadores as $i => $indicador)
                        @php
                            $lastAvance = $indicador->avances->sortByDesc('id')->first();
                            $semaforo = $lastAvance?->semaforo_calculado ?? 'sin dato';
                        @endphp
                        <tr>
                            @if($i === 0)
                                <td class="{{ $nivelClass }}" rowspan="{{ $nivel->indicadores->count() }}">{{ $nivel->tipo_nivel->label() }}</td>
                                <td rowspan="{{ $nivel->indicadores->count() }}">{{ $nivel->resumen_narrativo }}</td>
                            @endif
                            <td>{{ $indicador->nombre }}</td>
                            <td class="semaforo-{{ $semaforo }}" style="text-align: center;">{{ $semaforo === 'rojo_alto' ? 'Rojo alto' : ucfirst($semaforo) }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
        </tbody>
    </table>

    {{-- SECCIÓN 4: ÍNDICE DE EFICACIA --}}
    <div class="section-title">4. Índice de Eficacia</div>
    @if($evaluacion)
        <div class="indice-grande">
            {{ number_format($evaluacion->indice_eficacia, 2) }}
        </div>

        @if($evaluacion->desglose_niveles)
            <table>
                <thead>
                    <tr>
                        <th>Nivel</th>
                        <th>Peso</th>
                        <th>Promedio</th>
                        <th>Evaluados</th>
                        <th>No Evaluados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evaluacion->desglose_niveles as $nivelKey => $desglose)
                        <tr>
                            <td>{{ ucfirst($nivelKey) }}</td>
                            <td>{{ isset($desglose['peso']) ? number_format($desglose['peso'] * 100, 0) . '%' : '—' }}</td>
                            <td>{{ isset($desglose['promedio']) ? number_format($desglose['promedio'], 2) : '—' }}</td>
                            <td>{{ $desglose['evaluados'] ?? 0 }}</td>
                            <td>{{ $desglose['no_evaluados'] ?? 0 }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($evaluacion->conteo_semaforos)
            <div class="barra-semaforo">
                <div class="segmento semaforo-verde" style="width: {{ max(($evaluacion->conteo_semaforos['verde'] ?? 0) * 10, 20) }}px;">
                    {{ $evaluacion->conteo_semaforos['verde'] ?? 0 }} Verde
                </div>
                <div class="segmento semaforo-amarillo" style="width: {{ max(($evaluacion->conteo_semaforos['amarillo'] ?? 0) * 10, 20) }}px;">
                    {{ $evaluacion->conteo_semaforos['amarillo'] ?? 0 }} Amarillo
                </div>
                <div class="segmento semaforo-rojo" style="width: {{ max(($evaluacion->conteo_semaforos['rojo'] ?? 0) * 10, 20) }}px;">
                    {{ $evaluacion->conteo_semaforos['rojo'] ?? 0 }} Rojo
                </div>
                <div class="segmento semaforo-rojo_alto" style="width: {{ max(($evaluacion->conteo_semaforos['rojo_alto'] ?? 0) * 10, 20) }}px;">
                    {{ $evaluacion->conteo_semaforos['rojo_alto'] ?? 0 }} Rojo alto
                </div>
            </div>
        @endif
    @else
        <div class="sin-datos">Evaluación no calculada</div>
    @endif

    {{-- SECCIÓN 5: SEMÁFORO HISTÓRICO --}}
    <div class="section-title">5. Semáforo Histórico</div>
    @if(count($semaforoHistorico) > 0)
        <table>
            <thead>
                <tr>
                    <th>Trimestre</th>
                    <th>Verde</th>
                    <th>Amarillo</th>
                    <th>Rojo</th>
                    <th>Rojo alto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semaforoHistorico as $trimestre => $conteos)
                    <tr>
                        <td>{{ $trimestre }}</td>
                        <td class="semaforo-verde" style="text-align: center;">{{ $conteos['verde'] }}</td>
                        <td class="semaforo-amarillo" style="text-align: center;">{{ $conteos['amarillo'] }}</td>
                        <td class="semaforo-rojo" style="text-align: center;">{{ $conteos['rojo'] }}</td>
                        <td class="semaforo-rojo_alto" style="text-align: center;">{{ $conteos['rojo_alto'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="sin-datos">Sin datos históricos</div>
    @endif

    {{-- SECCIÓN 6: VO.BO. --}}
    @include('exports.pdf.partials.vobo', [
        'titular' => $titular,
        'dependencia' => $dependencia,
        'fecha' => $fecha,
    ])
</body>
</html>
