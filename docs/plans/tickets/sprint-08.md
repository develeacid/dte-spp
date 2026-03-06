## Sprint 8: Orquestacion IA (Transversal)

---

### S8-T1: Consolidar y Refactorizar LlmService con Arquitectura Resiliente

**Tipo:** refactor
**Rama:** `refactor/S8-T1-llm-service-avanzado`
**Depende de:** S3-T8, S4-T5, S4-T6, S4-T7, S4-T9, S6-T4, S7-T5

**Descripcion:**
Consolidar y refactorizar el `LlmService` (creado en S3-T8 y extendido en S4-S7) para unificar todos los métodos de dominio bajo una arquitectura robusta. Se implementan patrones de resiliencia, control de costos y versionado de prompts.

**Decisiones técnicas:**

- **Modo Degradado (Fallback):** El servicio operará en modo no bloqueante. Si el API de IA falla, retornará una respuesta "pendiente" que no impide al usuario continuar, en lugar de lanzar una excepción.
- **Prompts Versionados:** Los prompts se gestionarán en `resources/prompts/` con un `manifest.json` para auditoría y control de versiones.
- **Caché de Respuestas:** Se implementará una caché (`llm_cache`) para almacenar respuestas de consultas idénticas y reducir costos.
- **Configuración Centralizada:** `config/llm.php` gestionará modelos por tarea, costos y comportamiento de fallback.

**Metodos consolidados (alcance exclusivo de la IA):**

- `suggestNarrativeSyntax($nivel, $texto)` — Validación sintáctica SHCP (S4-T5)
- `validateCremaa($indicador)` — Validación CREMAA (S4-T6)
- `validateVerticalLogic($mir)` — Congruencia causal (S4-T7)
- `validateHorizontalLogic($nivel)` — Consistencia por fila (S4-T7)
- `extractVariables($formula)` — Extracción de variables (S4-T9)
- `generateJustification($avance, ...)` — Borradores de justificación (S6-T4)
- `suggestAlignment($texto, $nivel)` — Sugerencias de alineación (S4-T8)
- `detectCausalBreaks($evaluacion)` — Detección de rupturas al cierre (S7-T5)

**Lo que la IA ya NO valida (delegado al sistema en S4-T11):**

- Tipo, Dimensión y Frecuencia de indicadores (responsabilidad del motor Poka-Yoke).

**Criterios de aceptacion:**

- [ ] Todos los métodos de dominio están centralizados en `LlmService`.
- [ ] Implementado el modo degradado: en caso de fallo del API, el sistema no se bloquea.
- [ ] Implementada la caché de respuestas con TTL configurable.
- [ ] Los prompts se cargan desde `resources/prompts/` y se gestionan con un `manifest.json`.
- [ ] Los prompts NO incluyen instrucciones para validar Tipo, Dimensión o Frecuencia.
- [ ] Tests unitarios con mocks para cada método, incluyendo pruebas para el modo degradado y la caché.

---

### S8-T2: Pipeline de embeddings batch

**Tipo:** feat
**Rama:** `feat/S8-T2-embeddings-batch`
**Depende de:** S2-T10

**Descripcion:**
Comando Artisan para generar/regenerar embeddings en batch. Este pipeline es crucial para la carga inicial de datos y la recuperación de embeddings fallidos, e implementa manejo de rate limits y errores.

**Decisiones técnicas:**

- **Rate Limiting:** Se introduce un delay configurable entre chunks para no saturar el API.
- **Reintentos:** Se implementa una lógica de reintentos con backoff exponencial para manejar errores transitorios del API.
- **Priorización:** El comando procesa las tablas en un orden lógico (PED, ODS, PND) para que los datos más críticos para la alineación estén disponibles primero.

**Criterios de aceptacion:**

- [ ] Comando `app:embeddings-generate` procesa registros con `embedding IS NULL`.
- [ ] Procesamiento en chunks (`--chunk-size`) con delay configurable (`--delay`).
- [ ] Reintenta hasta 3 veces con backoff exponencial en caso de fallo.
- [ ] Reporte al finalizar: N generados, M fallidos, X omitidos.
- [ ] Flag `--force` para regenerar todos los embeddings.
- [ ] Schedulable para ejecución nocturna.

---

### S8-T3: Monitoreo y metricas de uso de IA

**Tipo:** feat
**Rama:** `feat/S8-T3-monitoreo-ia`
**Depende de:** S3-T8, S8-T1

**Descripcion:**
Dashboard administrativo para monitorear el consumo, costos y rendimiento del servicio de IA, con un sistema de presupuestos y alertas.

**Decisiones técnicas:**

- Se define un schema completo para la tabla `llm_logs` para una auditoría detallada.
- Se crea la tabla `llm_budgets` para gestionar presupuestos mensuales (global, por UR o por usuario).
- Se implementan alertas para umbrales de uso, costo y tasa de error.
- Se define una política de retención de datos para controlar el crecimiento de la BD.

**Metricas:**

- Llamadas por dia/semana/mes
- Tokens consumidos y **costo estimado en USD**.
- Tiempo promedio de respuesta
- Tasa de error
- Uso por tipo de operacion (validacion, sugerencia, justificacion)
- Uso por usuario/UR

**Criterios de aceptacion:**

- [ ] Migraciones para `llm_logs` (actualizada) y `llm_budgets` creadas.
- [ ] Dashboard con gráficas de uso, costos y rendimiento.
- [ ] Sistema de alertas que notifica al admin si se superan umbrales de costo o tasa de error.
- [ ] Política de retención implementada (ej. logs detallados por 90 días, métricas agregadas por 2 años).
- [ ] Solo accesible para el rol `admin`.
