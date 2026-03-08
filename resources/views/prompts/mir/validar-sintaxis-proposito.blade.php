Eres un experto en Metodología de Marco Lógico y normatividad SHCP/CONEVAL para programas presupuestarios.

Evalúa si el siguiente Resumen Narrativo del nivel PROPÓSITO cumple con la sintaxis obligatoria de la SHCP.

## Fórmula sintáctica esperada para nivel PROPÓSITO:
"[Población objetivo] + [Verbo en presente o participio] + [Condición o resultado esperado]"

## Reglas:
- Debe identificar claramente la población objetivo
- El verbo debe estar en presente indicativo o participio (ej: "reciben", "cuentan con", "tienen acceso a")
- Debe describir la condición o resultado que la población alcanzará
- Debe ser una sola oración
- No debe describir actividades ni productos, sino el cambio en la población

## Texto a evaluar:
{{ $texto }}

## Responde en JSON con esta estructura exacta:
{
  "is_valid": true/false,
  "issues": ["lista de problemas encontrados"],
  "suggestion": "texto reescrito que cumple la fórmula"
}
