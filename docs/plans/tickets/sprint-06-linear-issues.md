# Issues de Linear — Sprint 6: Seguimiento y Captura Periódica

## Proyecto

| Campo | Valor |
|-------|-------|
| **Nombre** | Sprint 6: Seguimiento y Captura Periódica |
| **Equipo** | DTE |
| **Estado** | Backlog |
| **Descripción** | Ciclo completo de seguimiento: captura de avance, semáforo automático, justificaciones IA, evidencia, flujo de aprobación, panel de planeador. |

---

## Issue 1: Migraciones y modelos para seguimiento

| Campo | Valor |
|-------|-------|
| **Título** | Migraciones y modelos para seguimiento |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 1 (Urgent) — blocker de todo el sprint |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: db`, `stack: backend`, `mod: seguimiento` |
| **Bloqueado por** | — (depende de S5-T4 ya completado) |

**Descripción:**

```markdown
## Contexto
Crear tablas de seguimiento: avances, avance_variables, avance_evidencias, desbloqueos. Extender metas_periodo (ya existe desde S5-T4) con campos de calendario. Modelos en app/Models/Tracking/.

**Rama:** `feat/S6-T1-migraciones-seguimiento`

## Esquema
- Extender `metas_periodo` con fecha_apertura, fecha_cierre
- `avances`: meta_periodo_id, indicador_id, resultado, semáforo, justificaciones, estado (JSONB historial), congelado_at
- `avance_variables`: avance_id, indicador_variable_id, valor, valor_acumulado (unique constraint)
- `avance_evidencias`: avance_id, nombre_archivo, ruta, mime_type, hash SHA-256, metadata
- `desbloqueos`: avance_id, motivo, solicitado_por, resuelto_por, estado
- Enums: EstadoAvance (5 estados), ComportamientoVariable (2 valores)

## Criterios de aceptacion
- [ ] Migración add_calendario_to_metas_periodo (fecha_apertura, fecha_cierre)
- [ ] Migración create_avances_table con JSONB historial_observaciones
- [ ] Migración create_avance_variables_table con unique constraint
- [ ] Migración create_avance_evidencias_table con hash_archivo string(64)
- [ ] Migración create_desbloqueos_table con FK a avance
- [ ] Enums EstadoAvance y ComportamientoVariable
- [ ] Modelos Avance, AvanceVariable, AvanceEvidencia, Desbloqueo con relaciones
- [ ] Relaciones inversas: Indicador::avances(), MetaPeriodo::avance()
- [ ] migrate:fresh --seed sin errores
- [ ] Tests: modelos, relaciones, casts, enum values (~8 tests)
```

---

## Issue 2: Calendario de captura y notificaciones

| Campo | Valor |
|-------|-------|
| **Título** | Calendario de captura y notificaciones |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) — ruta crítica |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `mod: seguimiento` |
| **Bloqueado por** | S6-T1 |

**Descripción:**

```markdown
## Contexto
Comandos artisan schedulables para gestionar ciclo de vida de períodos: abrir períodos, crear avances vacíos, notificar operadores, cerrar vencidos.

**Rama:** `feat/S6-T2-calendario-notificaciones`

## Componentes
- CalendarioService::calcularFechas() — genera fechas apertura/cierre por frecuencia
- Comando mir:abrir-periodos — crea avances EN_CAPTURA y notifica
- Comando mir:cerrar-vencidos — marca avances como VENCIDO
- PeriodoAbiertoNotification, AvanceVencidoNotification (database channel)
- Componentes: MisIndicadoresPendientes, IndicadoresVencidos
- Rutas en routes/web/tracking.php

## Criterios de aceptacion
- [ ] CalendarioService::calcularFechas() genera fechas por frecuencia y ejercicio
- [ ] Comando mir:abrir-periodos crea avances y notifica
- [ ] Comando mir:cerrar-vencidos marca como VENCIDO
- [ ] PeriodoAbiertoNotification con canal database
- [ ] AvanceVencidoNotification con canal database
- [ ] Componente MisIndicadoresPendientes para operador
- [ ] Componente IndicadoresVencidos para planeador
- [ ] Rutas en routes/web/tracking.php
- [ ] Schedule registrado
- [ ] Tests: apertura, cierre, no duplica, notificaciones (~8 tests)
```

---

## Issue 3: Formulario de captura de avance

| Campo | Valor |
|-------|-------|
| **Título** | Formulario de captura de avance |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) — ruta crítica |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: seguimiento` |
| **Bloqueado por** | S6-T1, S6-T2 |

**Descripción:**

```markdown
## Contexto
Componente Livewire para captura de valores de variables por indicador. Evalúa fórmula, calcula semáforo, diferencia acumulables/continuas.

**Rama:** `feat/S6-T3-formulario-captura-avance`

## Componentes
- FormulaEvaluatorService: evalúa fórmulas MIR con variables simbólicas
- SemaforoService: calcula semáforo respetando sentido (asc/desc/regular)
- CapturaAvance Livewire: campos dinámicos por variable, cálculo en tiempo real
- Instalar symfony/expression-language para evaluación
- Middleware: can:capturar_avance + activo_seguimiento + estado EN_CAPTURA

## Criterios de aceptacion
- [ ] FormulaEvaluatorService::evaluar() evalúa fórmulas con variables
- [ ] SemaforoService::calcular() respeta sentido ascendente/descendente/regular
- [ ] CapturaAvance con campos dinámicos por variable
- [ ] Acumulables: muestra valor acumulado anterior
- [ ] Cálculo en tiempo real (wire:change)
- [ ] Semáforo visual con posibilidad de ajuste + justificación
- [ ] Amarillo/rojo: justificación obligatoria
- [ ] Guarda AvanceVariable + Avance.resultado + semaforo_calculado
- [ ] Solo con capturar_avance y activo_seguimiento=true
- [ ] Solo editable si estado=EN_CAPTURA
- [ ] Tests: fórmulas, semáforo, acumulables, permisos (~10 tests)
```

---

## Issue 4: Generación de justificaciones con IA

| Campo | Valor |
|-------|-------|
| **Título** | Generación de justificaciones con IA |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 3 |
| **Labels** | `type: feature`, `stack: backend`, `stack: ai`, `mod: seguimiento` |
| **Bloqueado por** | S6-T3 |

**Descripción:**

```markdown
## Contexto
IA genera borrador de justificación cuando indicador cae en amarillo/rojo, basado en supuestos MIR, desviación numérica e historial.

**Rama:** `feat/S6-T4-justificaciones-ia`

## Componentes
- Prompt justificar-avance.blade.php con datos MIR + contexto numérico
- Reutiliza LlmService::suggest()
- Guarda justificacion_ia (auditoría) + justificacion_final (editada)

## Criterios de aceptacion
- [ ] Prompt justificar-avance.blade.php con datos MIR y desviación
- [ ] Borrador generado automáticamente al detectar amarillo/rojo
- [ ] Cita explícitamente supuestos de la MIR
- [ ] Sin supuestos: mensaje + textarea libre
- [ ] avances.justificacion_ia guarda borrador original
- [ ] avances.justificacion_final guarda versión editada
- [ ] IA nunca inventa contexto externo
- [ ] Tests: con/sin supuestos, guardado dual, mock LlmService (~5 tests)
```

---

## Issue 5: Adjuntar medios de verificación (evidencia)

| Campo | Valor |
|-------|-------|
| **Título** | Adjuntar medios de verificación (evidencia) |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) — paralelizable con T2 |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `stack: infra`, `mod: seguimiento` |
| **Bloqueado por** | S6-T1 |

**Descripción:**

```markdown
## Contexto
Upload de archivos de evidencia por avance. Almacenamiento en disco privado con hash SHA-256 para integridad.

**Rama:** `feat/S6-T5-adjuntar-evidencia`

## Componentes
- Upload Livewire (WithFileUploads) max 10MB
- Hash SHA-256 post-upload
- Controller para descarga con permisos
- Validación de correspondencia con MedioVerificacion de la MIR
- Almacenamiento: storage/app/private/evidencias/{avance_id}/

## Criterios de aceptacion
- [ ] Upload PDF, Excel, imágenes, Word (max 10MB)
- [ ] Hash SHA-256 generado y almacenado
- [ ] Campos: nombre_documento, area_generadora, fecha_documento
- [ ] Disco privado (no público)
- [ ] Controller descarga con verificación de permisos
- [ ] Advertencia si nombre no coincide con medios MIR
- [ ] Eliminar solo si avance no congelado
- [ ] Tests: upload, hash, descarga, permisos, advertencia (~6 tests)
```

---

## Issue 6: Máquina de estados del reporte de avance

| Campo | Valor |
|-------|-------|
| **Título** | Máquina de estados del reporte de avance |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) — ruta crítica |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: seguimiento` |
| **Bloqueado por** | S6-T3, S6-T5 |

**Descripción:**

```markdown
## Contexto
Flujo de estados del avance: EN_CAPTURA → EN_REVISION → OBSERVADO/APROBADO, con historial JSONB, notificaciones y congelamiento.

**Rama:** `feat/S6-T6-maquina-estados-avance`

## Transiciones
- EN_CAPTURA → EN_REVISION (operador envía)
- EN_REVISION → OBSERVADO (planeador rechaza)
- EN_REVISION → APROBADO (planeador aprueba, congelado_at = now)
- OBSERVADO → EN_CAPTURA (operador corrige)
- EN_CAPTURA/EN_REVISION → VENCIDO (automático)

## Componentes
- AvanceEstadoService::transicionar() — valida, actualiza, historial
- Historial JSONB append-only con schema definido
- Notificaciones: AvanceObservadoNotification, AvanceEnRevisionNotification
- Timeline visual de observaciones
- Botones contextuales por rol

## Criterios de aceptacion
- [ ] AvanceEstadoService::transicionar() valida y ejecuta
- [ ] Transiciones inválidas lanzan excepción
- [ ] Historial JSONB con schema definido, append-only
- [ ] congelado_at al aprobar
- [ ] Post-aprobación: todo inmutable
- [ ] AvanceObservadoNotification y AvanceEnRevisionNotification
- [ ] Timeline visual de observaciones
- [ ] Botones contextuales por rol
- [ ] Tests: transiciones, historial, congelamiento, permisos (~10 tests)
```

---

## Issue 7: Congelamiento y desbloqueo excepcional

| Campo | Valor |
|-------|-------|
| **Título** | Congelamiento y desbloqueo excepcional |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: seguimiento` |
| **Bloqueado por** | S6-T6 |

**Descripción:**

```markdown
## Contexto
Inmutabilidad post-aprobación y flujo de desbloqueo excepcional con auditoría.

**Rama:** `feat/S6-T7-congelamiento-desbloqueo`

## Componentes
- Avance::estaCongelado() — check congelado_at
- SolicitarDesbloqueo componente (operador)
- GestionarDesbloqueos componente (admin)
- Al aprobar: congelado_at=null, estado=EN_CAPTURA, historial

## Criterios de aceptacion
- [ ] Avance::estaCongelado() funcional
- [ ] Avance congelado: campos inmutables
- [ ] SolicitarDesbloqueo con motivo
- [ ] GestionarDesbloqueos para admin
- [ ] Aprobar: descongelar + estado EN_CAPTURA + historial
- [ ] Rechazar: registro con resolución
- [ ] Solo administrar_usuarios puede aprobar
- [ ] Tests: congelamiento, solicitar, aprobar, rechazar (~7 tests)
```

---

## Issue 8: Vista de seguimiento para planeadores

| Campo | Valor |
|-------|-------|
| **Título** | Vista de seguimiento para planeadores |
| **Equipo** | DTE |
| **Proyecto** | Sprint 6: Seguimiento y Captura Periódica |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: frontend`, `stack: backend`, `mod: seguimiento` |
| **Bloqueado por** | S6-T3, S6-T6 |

**Descripción:**

```markdown
## Contexto
Panel consolidado para planeador: programas con indicadores, metas, avances, semáforos. Aislamiento Multi-UR via team_id.

**Rama:** `feat/S6-T8-vista-seguimiento-planeador`

## Componentes
- PanelSeguimiento Livewire con tabla expandible
- Filtros: programa, estado avance, semáforo
- Aislamiento Multi-UR (team_id en mir_niveles)
- Enlace "Seguimiento" en navegación
- Ruta en routes/web/tracking.php

## Criterios de aceptacion
- [ ] PanelSeguimiento: Programa | Nivel | Indicador | Meta | Avance | Semáforo | Estado
- [ ] Expandible: historial, variables, justificación, evidencia
- [ ] Filtros por programa, estado, semáforo
- [ ] Aislamiento Multi-UR
- [ ] Semáforo visual (verde/amarillo/rojo/gris)
- [ ] Enlace en navegación
- [ ] Solo con revisar_avance
- [ ] Tests: aislamiento, filtros, datos (~5 tests)
```

---

## Resumen

| ID | Ticket | Prioridad | Est. | Labels | Bloqueado por |
|---|---|---|---|---|---|
| — | Migraciones y modelos seguimiento | Urgent (1) | 5 | feature, db, backend, seguimiento | — |
| — | Calendario de captura y notificaciones | High (2) | 5 | feature, backend, seguimiento | T1 |
| — | Formulario de captura de avance | High (2) | 8 | feature, backend, frontend, seguimiento | T1, T2 |
| — | Generación de justificaciones con IA | Medium (3) | 3 | feature, backend, ai, seguimiento | T3 |
| — | Adjuntar evidencia | Medium (3) | 5 | feature, backend, frontend, infra, seguimiento | T1 |
| — | Máquina de estados | High (2) | 8 | feature, backend, frontend, seguimiento | T3, T5 |
| — | Congelamiento y desbloqueo | Medium (3) | 5 | feature, backend, frontend, seguimiento | T6 |
| — | Vista seguimiento planeador | Medium (3) | 5 | feature, frontend, backend, seguimiento | T3, T6 |

**Total puntos:** 44
**Ruta crítica:** T1 → T2 → T3 → T6 → T7 (31 pts)
**Paralelizable:** T2 ∥ T5 después de T1; T4 después de T3
**Tests estimados:** ~59 nuevos
