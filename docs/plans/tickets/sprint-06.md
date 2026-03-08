## Sprint 6: Seguimiento y Captura Periódica

**Objetivo:** Implementar el ciclo completo de seguimiento de indicadores: captura de avance por operadores, cálculo automático de semáforo, justificaciones con IA, evidencia documental, flujo de aprobación con máquina de estados, y panel de planeador.

**Baseline técnico al iniciar Sprint 6:**
- 288 tests passing, 7 skipped
- Tabla `metas_periodo` ya creada en S5-T4 (indicador_id, periodo, meta_periodo, ejercicio_fiscal, activo). **NO crear de nuevo.**
- Modelo `MetaPeriodo` con relación `indicador()` y `Indicador::metasPeriodo()` ya implementados
- `CalendarizacionService` ya genera y persiste metas por período según `FrecuenciaMedicion`
- `IndicadorVariable.comportamiento` (string 20, nullable) — columna existe pero no tiene enum asociado ni valores poblados
- `Indicador.activo_seguimiento` (boolean) — controla qué indicadores participan en seguimiento
- `Indicador.formula_texto` — texto libre, **no hay evaluador matemático instalado**
- Semáforo: campos `rango_verde_min/max`, `rango_amarillo_min/max`, `rango_rojo_min/max` en `indicadores`
- `SentidoIndicador` enum: ASCENDENTE, DESCENDENTE, REGULAR
- Permisos Spatie ya definidos: `capturar_avance`, `revisar_avance`, `aprobar_avance`, `administrar_usuarios`
- No hay clases Notification — es área nueva
- Storage: disco `local` (`storage/app/private`) disponible para evidencia
- `LlmService` con `suggest()`, `validate()`, `transform()`, `renderPrompt()`
- Rutas de seguimiento van en `routes/web/tracking.php` (archivo nuevo, dominio `Tracking/`)

**Dependencias externas:**
- `mossadal/math-php` o `symfony/expression-language` para evaluación de fórmulas (instalar en S6-T3)
- Ninguna dependencia cruzada con otros sprints

---

### S6-T1: Migraciones y modelos para seguimiento

**Tipo:** feat
**Rama:** `feat/S6-T1-migraciones-seguimiento`
**Depende de:** S5-T4 (metas_periodo ya existe)

**Descripción:**
Crear las tablas que soportan el ciclo de seguimiento: `avances`, `avance_variables`, `avance_evidencias` y `desbloqueos`. La tabla `metas_periodo` **ya existe** desde Sprint 5 — se extiende con campos de calendario si es necesario.

**Decisiones técnicas:**

- **Tabla `metas_periodo` — extensión:** Agregar `fecha_apertura` (date nullable), `fecha_cierre` (date nullable) via migración ALTER. Estos campos los usa S6-T2 para el calendario de captura. No recrear la tabla.
- **`avances`:** Un registro por indicador+periodo+ejercicio. Campos: `meta_periodo_id` (FK), `indicador_id` (FK, desnormalizado para queries), `resultado` (decimal 12,4 nullable), `semaforo_calculado` (string 10: verde/amarillo/rojo nullable), `semaforo_ajustado` (string 10 nullable), `justificacion_ia` (text nullable), `justificacion_final` (text nullable), `estado` (string 20), `historial_observaciones` (JSONB default '[]'), `congelado_at` (timestamp nullable), `capturado_por` (FK users), timestamps.
- **`avance_variables`:** Valor capturado por variable: `avance_id` (FK), `indicador_variable_id` (FK), `valor` (decimal 12,4), `valor_acumulado` (decimal 12,4 nullable — para acumulables, suma hasta el período actual). Unique: [avance_id, indicador_variable_id].
- **`avance_evidencias`:** `avance_id` (FK), `nombre_archivo` (string), `ruta_archivo` (string), `mime_type` (string 50), `tamano_bytes` (bigint), `hash_archivo` (string 64 — SHA-256), `nombre_documento` (string — nombre descriptivo), `area_generadora` (string nullable), `fecha_documento` (date nullable), `subido_por` (FK users), timestamps.
- **`desbloqueos`:** `avance_id` (FK), `motivo` (text), `solicitado_por` (FK users), `resuelto_por` (FK users nullable), `estado` (string 20: pendiente/aprobado/rechazado), `resolucion` (text nullable), `resuelto_at` (timestamp nullable), timestamps.
- **`EstadoAvance` enum:** EN_CAPTURA, EN_REVISION, OBSERVADO, APROBADO, VENCIDO (sin PENDIENTE_APERTURA — el avance se crea cuando se abre el período).
- **`ComportamientoVariable` enum:** ACUMULABLE, CONTINUA (nuevo, para tipar `indicador_variables.comportamiento`).
- Modelos: `Avance`, `AvanceVariable`, `AvanceEvidencia`, `Desbloqueo` en `app/Models/Tracking/`.

**Criterios de aceptación:**

- [ ] Migración `add_calendario_to_metas_periodo` — agrega `fecha_apertura`, `fecha_cierre` a tabla existente.
- [ ] Migración `create_avances_table` con JSONB `historial_observaciones` y todos los campos.
- [ ] Migración `create_avance_variables_table` con unique constraint.
- [ ] Migración `create_avance_evidencias_table` con `hash_archivo` string(64).
- [ ] Migración `create_desbloqueos_table` con FK a avance.
- [ ] Enums: `EstadoAvance` (5 estados), `ComportamientoVariable` (2 valores).
- [ ] Modelos con fillable, casts, relaciones.
- [ ] `Avance::metaPeriodo()`, `Avance::indicador()`, `Avance::variables()`, `Avance::evidencias()`, `Avance::desbloqueos()`.
- [ ] `Indicador::avances()` HasMany agregado.
- [ ] `MetaPeriodo::avance()` HasOne agregado.
- [ ] `migrate:fresh --seed` sin errores.
- [ ] Tests: modelos, relaciones, casts, enum values (~8 tests).

---

### S6-T2: Calendario de captura y notificaciones

**Tipo:** feat
**Rama:** `feat/S6-T2-calendario-notificaciones`
**Depende de:** S6-T1

**Descripción:**
Comando artisan schedulable que gestiona el ciclo de vida de los períodos de captura: abre períodos, crea registros de avance vacíos, notifica operadores, y marca como vencidos los atrasados.

**Decisiones técnicas:**

- **`AbrirPeriodosCaptura` command:** Recorre `metas_periodo` donde `fecha_apertura <= hoy` y `activo = true` y no tiene `avance` asociado. Crea `Avance` con estado EN_CAPTURA y notifica al operador de la UR.
- **`CerrarPeriodosVencidos` command:** Recorre avances donde `meta_periodo.fecha_cierre < hoy` y estado NOT IN (APROBADO). Cambia a VENCIDO.
- **`CalendarioService::calcularFechas()`:** Al confirmar calendarización (o como método nuevo), calcula `fecha_apertura` y `fecha_cierre` por período según frecuencia. Ej: trimestral período 1 → apertura 1/abril, cierre 15/abril.
- **Notificaciones:** `PeriodoAbiertoNotification`, `AvanceVencidoNotification` — database + mail channels.
- Operador = usuario con permiso `capturar_avance` en el team del nivel MIR (via `team_id` en `mir_niveles` o el team del programa).

**Criterios de aceptación:**

- [ ] `CalendarioService::calcularFechas()` genera fechas apertura/cierre por frecuencia y ejercicio.
- [ ] Comando `mir:abrir-periodos` crea avances y notifica operadores.
- [ ] Comando `mir:cerrar-vencidos` marca avances como VENCIDO.
- [ ] `PeriodoAbiertoNotification` con canal database.
- [ ] `AvanceVencidoNotification` con canal database.
- [ ] Componente `MisIndicadoresPendientes` — lista de indicadores en EN_CAPTURA para el operador actual.
- [ ] Componente `IndicadoresVencidos` — lista de vencidos para el planeador.
- [ ] Rutas en `routes/web/tracking.php`.
- [ ] Schedule registrado en `app/Console/Kernel.php` (o `routes/console.php` si Laravel 12).
- [ ] Tests: apertura de períodos, cierre vencidos, no duplica avances, notificaciones (~8 tests).

---

### S6-T3: Formulario de captura de avance

**Tipo:** feat
**Rama:** `feat/S6-T3-formulario-captura-avance`
**Depende de:** S6-T1, S6-T2

**Descripción:**
Componente Livewire para que el operador capture los valores de las variables de un indicador. Calcula resultado aplicando la fórmula y determina el semáforo.

**Decisiones técnicas:**

- **Evaluador de fórmulas:** Instalar `symfony/expression-language` (ya disponible como dependencia transitiva, solo declarar explícitamente). Mapea símbolos de variables (A, B, C) a valores capturados y evalúa `formula_texto`. Convertir notación MIR `(A/B) x 100` a expresión válida: reemplazar `x` por `*`.
- **`FormulaEvaluatorService`:** `evaluar(string $formula, array $variables): ?float` — parsea, sanitiza, evalúa. Retorna null si fórmula inválida.
- **`SemaforoService`:** `calcular(float $resultado, Indicador $indicador): string` — compara resultado contra rangos del indicador respetando `sentido`:
  - ASCENDENTE: verde si resultado >= rango_verde_min
  - DESCENDENTE: verde si resultado <= rango_verde_max
  - REGULAR: verde si resultado entre rango_verde_min y rango_verde_max
- **Variables acumulables:** `AvanceVariable.valor_acumulado` = suma de valores del mismo indicador+variable en períodos anteriores del ejercicio + valor actual. El cálculo de fórmula usa `valor_acumulado` para acumulables, `valor` para continuas.
- **Middleware:** `can:capturar_avance` + verificar que indicador.activo_seguimiento=true + avance.estado=EN_CAPTURA.

**Criterios de aceptación:**

- [ ] `FormulaEvaluatorService::evaluar()` — evalúa fórmulas MIR con variables simbólicas.
- [ ] `SemaforoService::calcular()` — respeta sentido ascendente/descendente/regular.
- [ ] Componente `CapturaAvance` con campos dinámicos por variable (nombre, símbolo, input numérico).
- [ ] Para acumulables: muestra valor acumulado anterior como referencia.
- [ ] Cálculo en tiempo real al capturar valores (`wire:change`).
- [ ] Semáforo visual (verde/amarillo/rojo) con posibilidad de ajuste + justificación.
- [ ] Si amarillo/rojo: campo de justificación obligatorio antes de guardar.
- [ ] Guarda `AvanceVariable` por cada variable + `Avance.resultado` + `Avance.semaforo_calculado`.
- [ ] Solo accesible con `capturar_avance` y `activo_seguimiento=true`.
- [ ] Solo editable si avance.estado=EN_CAPTURA.
- [ ] Tests: fórmula evaluator, semáforo por sentido, acumulables vs continuas, permisos (~10 tests).

---

### S6-T4: Generación de justificaciones con IA

**Tipo:** feat
**Rama:** `feat/S6-T4-justificaciones-ia`
**Depende de:** S6-T3

**Descripción:**
Cuando un indicador cae en amarillo o rojo, la IA genera un borrador de justificación basado exclusivamente en los supuestos de la MIR, la desviación numérica, y el historial.

**Decisiones técnicas:**

- Reutilizar `LlmService::suggest()` con prompt Blade en `resources/views/prompts/tracking/justificar-avance.blade.php`.
- El prompt recibe: resumen narrativo del nivel, supuestos, meta del período, resultado, porcentaje de desviación, semáforo, historial de períodos anteriores (si existen).
- Si `mirNivel.supuestos` es null/vacío → el prompt lo indica y sugiere al usuario escribir manualmente.
- `justificacion_ia` se guarda como referencia de auditoría; `justificacion_final` es lo que el usuario confirma.

**Criterios de aceptación:**

- [ ] Prompt `justificar-avance.blade.php` con datos MIR y contexto numérico.
- [ ] `generarJustificacion(Avance $avance): string` en `JustificacionService` (o directamente en el componente).
- [ ] Borrador generado automáticamente al detectar amarillo/rojo en CapturaAvance.
- [ ] Borrador cita explícitamente los supuestos de la MIR.
- [ ] Si no hay supuestos: mensaje "No se encontraron supuestos definidos" + textarea libre.
- [ ] `avances.justificacion_ia` guarda el borrador original.
- [ ] `avances.justificacion_final` guarda la versión editada por el usuario.
- [ ] La IA nunca inventa contexto externo (instrucción explícita en prompt).
- [ ] Tests: generación con supuestos, sin supuestos, guardado dual, mock LlmService (~5 tests).

---

### S6-T5: Adjuntar medios de verificación (evidencia)

**Tipo:** feat
**Rama:** `feat/S6-T5-adjuntar-evidencia`
**Depende de:** S6-T1

**Descripción:**
Funcionalidad para adjuntar archivos de evidencia a un avance. Los archivos se almacenan en disco privado con hash SHA-256 para integridad.

**Decisiones técnicas:**

- Almacenamiento en disco `local` (storage/app/private) bajo ruta `evidencias/{avance_id}/{filename}`.
- Upload via Livewire `WithFileUploads` — max 10MB por archivo, tipos: pdf, xlsx, xls, jpg, png, doc, docx.
- Hash SHA-256 calculado con `hash_file('sha256', $path)` post-upload.
- Servir archivos via controller con `can:revisar_avance` o propio operador — `Storage::download()`.
- Validación de correspondencia con MIR: comparar `nombre_documento` con `MedioVerificacion.nombre` del indicador — si no matchea, mostrar advertencia (no bloquear).

**Criterios de aceptación:**

- [ ] Upload de archivos (PDF, Excel, imágenes, Word) con max 10MB.
- [ ] Hash SHA-256 generado y almacenado en `avance_evidencias.hash_archivo`.
- [ ] Campos: nombre_documento, area_generadora, fecha_documento en formulario.
- [ ] Almacenamiento en disco privado (no público).
- [ ] Controller o ruta para descargar con verificación de permisos.
- [ ] Advertencia visual si nombre_documento no coincide con medios de verificación registrados en la MIR.
- [ ] Listado de evidencias adjuntas con opción de eliminar (solo si avance no congelado).
- [ ] Tests: upload, hash, descarga, permisos, advertencia MIR (~6 tests).

---

### S6-T6: Máquina de estados del reporte de avance

**Tipo:** feat
**Rama:** `feat/S6-T6-maquina-estados-avance`
**Depende de:** S6-T3, S6-T5

**Descripción:**
Implementar el flujo de estados del avance con transiciones controladas, historial de observaciones en JSONB, y notificaciones por transición.

**Decisiones técnicas:**

- **`AvanceEstadoService`:** Centraliza transiciones válidas. No usar paquete externo de state machine — es suficiente con un servicio simple:
  ```
  EN_CAPTURA   → EN_REVISION  (operador envía)
  EN_REVISION  → OBSERVADO    (planeador rechaza con comentario)
  EN_REVISION  → APROBADO     (planeador aprueba)
  OBSERVADO    → EN_CAPTURA   (operador corrige y re-abre)
  EN_CAPTURA   → VENCIDO      (comando automático)
  EN_REVISION  → VENCIDO      (comando automático)
  ```
- Cada transición agrega entrada a `historial_observaciones` JSONB con schema:
  `{fecha, usuario_id, usuario_nombre, rol, accion, estado_anterior, estado_nuevo, observacion}`
- Al aprobar: `congelado_at = now()` — trigger de inmutabilidad.
- Notificaciones: `AvanceObservadoNotification` (→ operador), `AvanceEnRevisionNotification` (→ planeador).
- Permisos: operador puede enviar/corregir (`capturar_avance`), planeador puede observar/aprobar (`aprobar_avance`).

**Criterios de aceptación:**

- [ ] `AvanceEstadoService::transicionar(Avance, EstadoAvance, User, ?string $observacion)` — valida transición, actualiza estado, escribe historial.
- [ ] Transiciones inválidas lanzan excepción.
- [ ] Historial JSONB con schema definido, append-only.
- [ ] `congelado_at` se establece al aprobar.
- [ ] Post-aprobación: variables, justificación y archivos inmutables (validación en modelo o servicio).
- [ ] `AvanceObservadoNotification` y `AvanceEnRevisionNotification`.
- [ ] Timeline visual de observaciones en la vista del avance.
- [ ] Botones contextuales: "Enviar a revisión" (operador), "Observar"/"Aprobar" (planeador).
- [ ] Tests: transiciones válidas/inválidas, historial, congelamiento, permisos (~10 tests).

---

### S6-T7: Congelamiento y desbloqueo excepcional

**Tipo:** feat
**Rama:** `feat/S6-T7-congelamiento-desbloqueo`
**Depende de:** S6-T6

**Descripción:**
Inmutabilidad post-aprobación y flujo de desbloqueo excepcional con auditoría completa.

**Decisiones técnicas:**

- Inmutabilidad via `Avance::estaCongelado(): bool` — check `congelado_at !== null`.
- El componente de captura y evidencia consulta `estaCongelado()` antes de permitir edición.
- Desbloqueo: operador solicita → admin (`administrar_usuarios`) aprueba/rechaza.
- Al aprobar desbloqueo: `congelado_at = null`, estado vuelve a EN_CAPTURA, entrada en historial.
- Solo un desbloqueo activo (pendiente) por avance a la vez.

**Criterios de aceptación:**

- [ ] `Avance::estaCongelado()` retorna true si `congelado_at` tiene valor.
- [ ] Avance congelado: todos los campos inmutables (CapturaAvance y evidencia respetan esto).
- [ ] Componente `SolicitarDesbloqueo` con campo motivo.
- [ ] Componente `GestionarDesbloqueos` para admin — lista de solicitudes pendientes.
- [ ] Al aprobar: `congelado_at = null`, estado = EN_CAPTURA, entrada en historial.
- [ ] Al rechazar: registro con resolución, avance permanece congelado.
- [ ] Solo `administrar_usuarios` puede aprobar desbloqueos.
- [ ] Tests: congelamiento bloquea edición, solicitar, aprobar, rechazar, historial (~7 tests).

---

### S6-T8: Vista de seguimiento para planeadores

**Tipo:** feat
**Rama:** `feat/S6-T8-vista-seguimiento-planeador`
**Depende de:** S6-T3, S6-T6

**Descripción:**
Panel consolidado donde el planeador ve todos los programas de su UR con indicadores, metas, avances y semáforos. Respeta aislamiento Multi-UR via `team_id` en `mir_niveles`.

**Decisiones técnicas:**

- Query principal: programas del team actual → mir_niveles → indicadores → metasPeriodo → avance.
- Para UR Coadyuvante: incluir niveles donde `mir_niveles.team_id = currentTeam` (componentes/actividades asignados).
- Semáforo agregado por programa: peor semáforo de sus indicadores en el período actual.
- Ruta en `routes/web/tracking.php`, enlace en navegación principal.

**Criterios de aceptación:**

- [ ] Componente `PanelSeguimiento` con tabla: Programa | Nivel | Indicador | Meta período | Avance | Semáforo | Estado.
- [ ] Expandible: historial de capturas, variables, justificación, evidencia.
- [ ] Filtros: por programa, por estado de avance, por semáforo.
- [ ] Aislamiento Multi-UR: solo programas del team actual + niveles con team_id = currentTeam.
- [ ] Semáforo visual con colores (verde/amarillo/rojo/gris para sin datos).
- [ ] Enlace "Seguimiento" en menú de navegación.
- [ ] Solo accesible con `revisar_avance`.
- [ ] Tests: aislamiento por team, filtros, datos correctos (~5 tests).

---

## Orden de ejecución recomendado

```
S6-T1 → S6-T2 → S6-T3 → S6-T4
                    ↓
S6-T1 → S6-T5 → S6-T6 → S6-T7
                    ↓
              S6-T8 (después de T3 y T6)
```

- **T1** bloquea todo (modelos base)
- **T2** y **T5** pueden paralelizarse después de T1
- **T3** requiere T1+T2
- **T4** requiere T3
- **T5** solo requiere T1
- **T6** requiere T3+T5
- **T7** requiere T6
- **T8** requiere T3+T6

## Notas de integración con Sprint 5

| Recurso de Sprint 5 | Reutilizado en Sprint 6 |
|---|---|
| `metas_periodo` tabla + modelo | Base para avances (FK), calendario (fechas) |
| `MetaPeriodo::avance()` | Relación directa al avance del período |
| `CalendarizacionService` | Extender con `calcularFechas()` en S6-T2 |
| `Indicador.activo_seguimiento` | Filtro de qué indicadores participan en seguimiento |
| `Indicador.formula_texto` | Input para FormulaEvaluatorService en S6-T3 |
| `IndicadorVariable.comportamiento` | Diferencia acumulable/continua en S6-T3 |
| `Indicador` rangos semáforo | Input para SemaforoService en S6-T3 |
| `SentidoIndicador` enum | Lógica de comparación en SemaforoService |
| `LlmService::suggest()` | Generación de justificaciones en S6-T4 |
| `MirNivel.supuestos` | Contexto para prompt de justificación |
| `MirNivel.team_id` | Aislamiento Multi-UR en S6-T8 |
| Permisos Spatie | `capturar_avance`, `revisar_avance`, `aprobar_avance`, `administrar_usuarios` |

## Cambios respecto al plan original

| Aspecto | Plan original | Plan mejorado | Razón |
|---|---|---|---|
| `metas_periodo` | Crear en S6-T1 | Ya existe (S5-T4), solo extender | Evitar migración duplicada |
| `PENDIENTE_APERTURA` estado | En enum | Eliminado | El avance se crea al abrir período, no necesita estado pre-existente |
| `desbloqueos` polimórfica | Relación polimórfica | FK directa a avance | Solo desbloquea avances, polimorfismo innecesario |
| MathExecutor | Librería sugerida | `symfony/expression-language` | Ya disponible como dep transitiva |
| `ComportamientoVariable` | Sin enum | Nuevo enum | Tipar el campo existente para validación |
| Fechas en `metas_periodo` | No contemplado | `fecha_apertura`, `fecha_cierre` | Necesario para calendario de captura |
| Modelos en `Models/Tracking/` | Implícito | Explícito | Respetar arquitectura por dominio |
| Vista comparativa | En T8 | Diferida a Sprint 7 | Sprint 6 ya tiene 8 tickets, T8 es suficientemente grande |
