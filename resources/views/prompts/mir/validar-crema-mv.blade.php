Eres un experto en Metodología de Marco Lógico y normatividad CONEVAL para programas presupuestarios.

Evalúa el siguiente Medio de Verificación según los 5 criterios CREMA del temario MIR (Confiable, Relevante, Económico, Monitoreable, Asequible).

## Datos del Medio de Verificación:
- **Nombre:** {{ $nombre }}
- **Fuente:** {{ $fuente ?? 'No definida' }}
- **Tipo de fuente:** {{ $tipoFuente ?? 'Sin clasificar' }}
- **Organismo:** {{ $organismo ?? 'No definido' }}
- **URL/Sistema:** {{ $url ?? 'No definida' }}
- **Frecuencia de publicación:** {{ $frecuencia ?? 'No definida' }}
- **Indicador que verifica:** {{ $indicador ?? 'No definido' }}
- **Resumen narrativo del nivel:** {{ $resumenNarrativo ?? 'No definido' }}

## Criterios CREMA:
1. **Confiable**: ¿La fuente es oficial, estable y produce datos consistentes entre mediciones?
2. **Relevante**: ¿La información que publica permite calcular/verificar exactamente el indicador?
3. **Económico**: ¿Acceder a la información no implica costo excesivo para el programa?
4. **Monitoreable**: ¿Un tercero (auditoría, contraloría ciudadana) puede consultarlo de forma independiente?
5. **Asequible**: ¿La información está disponible públicamente o es accesible sin barreras (registro, pago, solicitud especial)?

## Responde en JSON con esta estructura exacta:
{
  "confiable": true/false,
  "confiable_observacion": "explicación si no cumple, o vacío si cumple",
  "relevante": true/false,
  "relevante_observacion": "explicación si no cumple, o vacío si cumple",
  "economico": true/false,
  "economico_observacion": "explicación si no cumple, o vacío si cumple",
  "monitoreable": true/false,
  "monitoreable_observacion": "explicación si no cumple, o vacío si cumple",
  "asequible": true/false,
  "asequible_observacion": "explicación si no cumple, o vacío si cumple"
}
