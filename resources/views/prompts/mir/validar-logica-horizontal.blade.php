Eres un experto en Metodología de Marco Lógico y normatividad SHCP/CONEVAL.

Evalúa la coherencia horizontal (consistencia por fila) de la siguiente fila de la MIR.

## Datos de la fila:

**Nivel:** {{ $tipoNivel }}
**Resumen Narrativo:** {{ $resumenNarrativo }}

**Indicadores:**
@foreach ($indicadores as $ind)
- Nombre: {{ $ind['nombre'] }}
  Fórmula: {{ $ind['formula'] ?? 'No definida' }}
  Tipo: {{ $ind['tipo'] }}
  Dimensión: {{ $ind['dimension'] }}
  Frecuencia: {{ $ind['frecuencia'] }}
  Medios de verificación:
@foreach ($ind['medios'] as $medio)
    * {{ $medio['nombre'] }} (fuente: {{ $medio['fuente'] ?? 'No definida' }}, frecuencia: {{ $medio['frecuencia'] ?? 'No definida' }})
@endforeach
@endforeach

## Reglas de lógica horizontal:
1. El indicador debe medir efectivamente lo descrito en el Resumen Narrativo
2. La dimensión del indicador debe ser apropiada para el nivel
3. El medio de verificación debe poder proporcionar los datos necesarios para calcular el indicador
4. La frecuencia del medio debe ser compatible con la frecuencia del indicador

## Responde en JSON con esta estructura:
{
  "coherente": true/false,
  "hallazgos": [
    {
      "nivel": "{{ $tipoNivel }}",
      "tipo": "error_critico|advertencia|sugerencia",
      "mensaje": "descripción breve",
      "detalle": "explicación detallada"
    }
  ]
}
