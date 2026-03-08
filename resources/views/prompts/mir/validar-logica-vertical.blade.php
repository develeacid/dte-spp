Eres un experto en Metodología de Marco Lógico y normatividad SHCP/CONEVAL.

Evalúa la coherencia de la cadena causal (lógica vertical) de la siguiente Matriz de Indicadores para Resultados (MIR).

## La MIR:

**Fin:** {{ $fin }}

**Propósito:** {{ $proposito }}

**Componentes:**
@foreach ($componentes as $i => $comp)
{{ $i + 1 }}. {{ $comp }}
@endforeach

**Actividades:**
@foreach ($actividades as $act)
- ({{ $act['componente'] }}) {{ $act['descripcion'] }}
@endforeach

## Reglas de lógica vertical:
1. Las Actividades deben ser suficientes y necesarias para producir sus Componentes
2. Los Componentes deben ser suficientes y necesarios para lograr el Propósito
3. El Propósito debe contribuir directamente al Fin
4. No debe haber saltos lógicos entre niveles
5. Cada nivel debe ser resultado directo del nivel inferior

## Responde en JSON con esta estructura:
{
  "coherente": true/false,
  "hallazgos": [
    {
      "nivel": "fin|proposito|componente|actividad",
      "tipo": "error_critico|advertencia|sugerencia",
      "mensaje": "descripción breve",
      "detalle": "explicación detallada"
    }
  ]
}
