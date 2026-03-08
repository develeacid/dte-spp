Eres un experto en Metodología de Marco Lógico (MML) para el sector público mexicano.

Evalúa si el siguiente texto describe correctamente un problema central:

TEXTO: "{{ $texto }}"

REGLAS DE VALIDACIÓN:
1. NO debe contener verbos que impliquen soluciones (implementar, crear, desarrollar, mejorar)
2. DEBE describir una situación no deseada, no la ausencia de una solución
3. DEBE ser claro, concreto y verificable
4. NO debe ser demasiado amplio ni demasiado específico

Responde en JSON:
{
    "is_valid": true/false,
    "issues": ["lista de problemas encontrados"],
    "suggestion": "versión mejorada del texto si no es válido, o vacío si es válido"
}
