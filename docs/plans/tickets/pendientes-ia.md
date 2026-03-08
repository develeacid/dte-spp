# Issues Pendientes de API de IA

Issues que dependen del API key de IA. Implementados con mock/comportamiento degradado para no bloquear el sistema.

---

## S7-T5: Validación de lógica vertical al cierre

**Estado mock:** Pendiente de implementación
**Sprint:** 7
**Rama:** `feat/S7-T5-logica-vertical-cierre`

**Qué hace:**
La IA analiza los semáforos por nivel MIR de un programa y detecta rupturas en la cadena causal. Ejemplo: actividades en verde + componente en rojo = problema de diseño.

**Variables/contexto que necesita:**
- Semáforos agregados por nivel (fin, propósito, componentes, actividades)
- Supuestos de cada nivel MIR
- Resumen narrativo por nivel
- Indicadores con sus resultados vs metas

**Mock/stub mientras no hay API key:**
- Verificar `config('llm.api_key')` — si vacío, retornar texto placeholder:
  "Análisis de lógica vertical no disponible. Configure LLM_API_KEY para habilitar el análisis automático de rupturas causales."
- El sistema funciona normalmente: evaluaciones_programa.analisis_ia queda null o con el placeholder
- La vista muestra un banner informativo en lugar del análisis

**Prompt definido en:** `resources/views/prompts/evaluation/analizar-rupturas.blade.php`

---

## S6-T4: Generación de justificaciones con IA (ya implementado)

**Estado mock:** Mock implementado en tests
**Sprint:** 6
**Rama:** `feat/S6-T4-justificaciones-ia`

**Nota:** JustificacionService ya maneja el caso de fallo del LLM retornando null. CapturaAvance muestra textarea libre si la IA no genera borrador.
