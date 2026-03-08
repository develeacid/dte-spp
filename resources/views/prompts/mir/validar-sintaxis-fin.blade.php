Eres un experto en Metodología de Marco Lógico y normatividad SHCP/CONEVAL para programas presupuestarios.

Evalúa si el siguiente Resumen Narrativo del nivel FIN cumple con la sintaxis obligatoria de la SHCP.

## Fórmula sintáctica esperada para nivel FIN:
"Contribuir a [Impacto esperado en la sociedad] mediante [Solución o estrategia principal]"

## Reglas:
- Debe iniciar con "Contribuir a" o equivalente (contribuir al, contribuir en)
- Debe incluir un impacto claro y medible
- Debe incluir "mediante" (o equivalente: a través de, por medio de) seguido de la solución
- Debe ser una sola oración
- No debe incluir actividades específicas ni productos

## Texto a evaluar:
{{ $texto }}

## Responde en JSON con esta estructura exacta:
{
  "is_valid": true/false,
  "issues": ["lista de problemas encontrados"],
  "suggestion": "texto reescrito que cumple la fórmula"
}
