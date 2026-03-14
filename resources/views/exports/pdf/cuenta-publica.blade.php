<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cuenta Pública {{ $ejercicio }}</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #333; }
        h1 { font-size: 16px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 12px; margin-top: 20px; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        h3 { font-size: 10px; margin-top: 15px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header p { margin: 2px 0; font-size: 10px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 4px 6px; text-align: left; }
        th { background-color: #f5f5f5; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        td.num { text-align: right; font-family: monospace; }
        td.center { text-align: center; }
        .verde { color: #16a34a; font-weight: bold; }
        .amarillo { color: #ca8a04; font-weight: bold; }
        .rojo { color: #dc2626; font-weight: bold; }
        .section { page-break-inside: avoid; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Reporte de Cuenta Pública — Ejercicio {{ $ejercicio }}</h1>
        <p>{{ $team->name }}</p>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Sección 1: Alineación Estratégica --}}
    <div class="section">
        <h2>1. Alineación Estratégica</h2>
        <table>
            <thead>
                <tr>
                    <th>Programa</th>
                    <th>Eje PED</th>
                    <th>Objetivo Estratégico</th>
                    <th>PND</th>
                    <th>ODS</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos as $item)
                    <tr>
                        <td>{{ $item['programa']->clave }} — {{ Str::limit($item['programa']->nombre, 35) }}</td>
                        <td>{{ $item['alineacion']['ped_eje'] ?? '—' }}</td>
                        <td>{{ Str::limit($item['alineacion']['ped_objetivo'] ?? '—', 40) }}</td>
                        <td>{{ implode(', ', array_map(fn($o) => Str::limit($o, 30), $item['alineacion']['pnd_objetivos'])) ?: '—' }}</td>
                        <td>{{ implode(', ', array_map(fn($m) => Str::limit($m, 25), $item['alineacion']['ods_metas'])) ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Sección 2: Cruce Físico-Financiero --}}
    <div class="section">
        <h2>2. Cruce Avance Físico — Financiero</h2>
        <table>
            <thead>
                <tr>
                    <th>Programa</th>
                    <th>Aprobado</th>
                    <th>Modificado</th>
                    <th>Ejercido</th>
                    <th>% Ejercicio</th>
                    <th>Eficiencia</th>
                    <th>Sem. Físico</th>
                    <th>Sem. Financiero</th>
                    <th>Combinado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($datos as $item)
                    <tr>
                        <td>{{ $item['programa']->clave }}</td>
                        <td class="num">${{ number_format($item['financiero']->aprobado, 2) }}</td>
                        <td class="num">${{ number_format($item['financiero']->modificado, 2) }}</td>
                        <td class="num">${{ number_format($item['financiero']->pagado, 2) }}</td>
                        <td class="center">{{ $item['financiero']->pct_ejercido }}%</td>
                        <td class="center">{{ $item['eficiencia'] ?? '—' }}</td>
                        <td class="center {{ $item['semaforo']['fisico'] }}">{{ ucfirst($item['semaforo']['fisico']) }}</td>
                        <td class="center {{ $item['semaforo']['financiero'] }}">{{ ucfirst($item['semaforo']['financiero']) }}</td>
                        <td class="center {{ $item['semaforo']['combinado'] }}">{{ ucfirst($item['semaforo']['combinado']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Sección 3: Resumen por Eje PED --}}
    @if ($resumenEjes->isNotEmpty())
        <div class="section">
            <h2>3. Resumen por Eje PED</h2>
            <table>
                <thead>
                    <tr>
                        <th>Eje</th>
                        <th># Programas</th>
                        <th>Total Aprobado</th>
                        <th>Total Ejercido</th>
                        <th>% Ejercido</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($resumenEjes as $eje)
                        <tr>
                            <td>{{ $eje->eje }}</td>
                            <td class="center">{{ count($eje->programas) }}</td>
                            <td class="num">${{ number_format($eje->total_aprobado, 2) }}</td>
                            <td class="num">${{ number_format($eje->total_ejercido, 2) }}</td>
                            <td class="center">{{ $eje->pct_ejercido }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="footer">
        DTE-SPP — Sistema de Planeación para Programas Presupuestales | Cuenta Pública {{ $ejercicio }}
    </div>
</body>
</html>
