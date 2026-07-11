<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha Técnica - {{ $indicador->nombre }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h2 { margin: 2px 0; font-size: 14px; }
        .header h3 { margin: 2px 0; font-size: 12px; }
        .meta { font-size: 9px; color: #666; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f7fafc; width: 30%; font-weight: bold; }
        .section-title { background-color: #2d3748; color: white; padding: 6px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $encabezado['institucion'] }}</h2>
        <h3>{{ $encabezado['dependencia'] }}</h3>
        <h3>Ficha Técnica del Indicador</h3>
        <p><strong>{{ $programa->clave }}</strong> — {{ $programa->nombre }}</p>
    </div>
    <div class="meta">Generado: {{ $generadoEn }}</div>

    <div class="section-title">Datos Generales</div>
    <table>
        <tr><th>Nivel MIR</th><td>{{ $nivel->tipo_nivel->label() }}</td></tr>
        <tr><th>Código</th><td>{{ $nivel->codigoMir() }}</td></tr>
        <tr><th>Resumen Narrativo</th><td>{{ $nivel->resumen_narrativo }}</td></tr>
        <tr><th>Nombre del Indicador</th><td>{{ $indicador->nombre }}</td></tr>
        <tr><th>Definición</th><td>{{ $indicador->definicion }}</td></tr>
        <tr><th>Fórmula</th><td>{{ $indicador->formula_texto }}</td></tr>
        <tr><th>Tipo</th><td>{{ $indicador->tipo?->value }}</td></tr>
        <tr><th>Dimensión</th><td>{{ $indicador->dimension?->value }}</td></tr>
        <tr><th>Frecuencia</th><td>{{ $indicador->frecuencia?->value }}</td></tr>
        <tr><th>Sentido</th><td>{{ $indicador->sentido?->value }}</td></tr>
        <tr><th>Unidad de Medida</th><td>{{ $indicador->unidadMedida?->nombre ?? 'N/A' }}</td></tr>
    </table>

    <div class="section-title">Metas y Línea Base</div>
    <table>
        <tr><th>Meta Anual</th><td>{{ $indicador->meta }}</td></tr>
        <tr><th>Línea Base</th><td>{{ $indicador->linea_base }}</td></tr>
    </table>

    <div class="section-title">Semáforos</div>
    <table>
        <tr><th>Verde</th><td>{{ $indicador->rango_verde_min }} — {{ $indicador->rango_verde_max }}</td></tr>
        <tr><th>Amarillo</th><td>{{ $indicador->rango_amarillo_min }} — {{ $indicador->rango_amarillo_max }}</td></tr>
        <tr><th>Rojo</th><td>{{ $indicador->rango_rojo_min }} — {{ $indicador->rango_rojo_max }}</td></tr>
    </table>

    @if($indicador->variables->isNotEmpty())
        <div class="section-title">Variables</div>
        <table>
            <tr><th>Variable</th><th>Descripción</th></tr>
            @foreach($indicador->variables as $var)
                <tr><td>{{ $var->nombre }}</td><td>{{ $var->descripcion ?? '' }}</td></tr>
            @endforeach
        </table>
    @endif

    @if($indicador->mediosVerificacion->isNotEmpty())
        <div class="section-title">Medios de Verificación</div>
        <table>
            @foreach($indicador->mediosVerificacion as $mv)
                <tr><td>{{ $mv->descripcion }}</td></tr>
            @endforeach
        </table>
    @endif
</body>
</html>
