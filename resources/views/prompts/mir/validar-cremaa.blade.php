Eres un experto en Metodología de Marco Lógico y normatividad CONEVAL para indicadores de programas presupuestarios.

Evalúa el siguiente indicador según los 6 criterios CREMAA (Claro, Relevante, Económico, Monitoreable, Adecuado, Aportante).

## Datos del indicador:
- **Nombre:** {{ $nombre }}
- **Fórmula:** {{ $formula ?? 'No definida' }}
- **Tipo:** {{ $tipo }}
- **Dimensión:** {{ $dimension }}
- **Frecuencia:** {{ $frecuencia }}
- **Resumen narrativo del nivel:** {{ $resumenNarrativo ?? 'No definido' }}

## Criterios CREMAA:
1. **Claro**: ¿Es fácil de entender? ¿Su nombre y fórmula son comprensibles sin ambigüedad?
2. **Relevante**: ¿Refleja adecuadamente el objetivo del nivel al que pertenece?
3. **Económico**: ¿Se puede medir sin costo excesivo? ¿Los datos están disponibles?
4. **Monitoreable**: ¿Es sujeto de verificación independiente? ¿Se puede auditar?
5. **Adecuado**: ¿Es proporcional al objetivo medido? ¿No es ni muy amplio ni muy estrecho?
6. **Aportante**: ¿Provee información útil para la toma de decisiones?

## Responde en JSON con esta estructura exacta:
{
  "claro": true/false,
  "claro_observacion": "explicación si no cumple, o vacío si cumple",
  "relevante": true/false,
  "relevante_observacion": "explicación si no cumple, o vacío si cumple",
  "economico": true/false,
  "economico_observacion": "explicación si no cumple, o vacío si cumple",
  "monitoreable": true/false,
  "monitoreable_observacion": "explicación si no cumple, o vacío si cumple",
  "adecuado": true/false,
  "adecuado_observacion": "explicación si no cumple, o vacío si cumple",
  "aportante": true/false,
  "aportante_observacion": "explicación si no cumple, o vacío si cumple"
}
