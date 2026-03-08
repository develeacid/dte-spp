# Sprint 6 Retrospective — Seguimiento y Captura Periódica

**Fecha:** 2026-03-08
**Baseline inicial:** 288 tests, 7 skipped
**Baseline final:** 349 tests, 7 skipped (61 nuevos)
**Ramas completadas:** 8/8

---

## Resumen de Ejecución

| Task | Rama | Tests | Estado |
|------|------|:---:|--------|
| T1: Migraciones y modelos | `feat/S6-T1-migraciones-seguimiento` | 10 | Completado |
| T2: Calendario y notificaciones | `feat/S6-T2-calendario-notificaciones` | 8 | Completado |
| T3: Formulario de captura | `feat/S6-T3-formulario-captura-avance` | 10 | Completado |
| T4: Justificaciones IA | `feat/S6-T4-justificaciones-ia` | 5 | Completado |
| T5: Adjuntar evidencia | `feat/S6-T5-adjuntar-evidencia` | 6 | Completado |
| T6: Máquina de estados | `feat/S6-T6-maquina-estados-avance` | 10 | Completado |
| T7: Congelamiento y desbloqueo | `feat/S6-T7-congelamiento-desbloqueo` | 7 | Completado |
| T8: Vista de seguimiento planeador | `feat/S6-T8-vista-seguimiento-planeador` | 5 | Completado |

---

## Criterios de Aceptación — Cumplimiento

### T1: Migraciones y modelos
- [x] Migración add_calendario_to_metas_periodo (fecha_apertura, fecha_cierre)
- [x] Migración create_avances_table con JSONB historial_observaciones
- [x] Migración create_avance_variables_table con unique constraint
- [x] Migración create_avance_evidencias_table con hash_archivo string(64)
- [x] Migración create_desbloqueos_table con FK a avance
- [x] Enums EstadoAvance (5 estados), ComportamientoVariable (2 valores)
- [x] Modelos con fillable, casts, relaciones completas
- [x] Relaciones inversas: Indicador::avances(), MetaPeriodo::avance()
- [x] Migración adicional: create_notifications_table (requerida por T2)
- [x] Migración adicional: make_capturado_por_nullable_on_avances (requerida por T2)

### T2: Calendario y notificaciones
- [x] CalendarioService::calcularFechas() por frecuencia y ejercicio
- [x] Comando mir:abrir-periodos crea avances y notifica
- [x] Comando mir:cerrar-vencidos marca como VENCIDO
- [x] PeriodoAbiertoNotification (database channel)
- [x] AvanceVencidoNotification (database channel)
- [x] MisIndicadoresPendientes (operador)
- [x] IndicadoresVencidos (planeador)
- [x] routes/web/tracking.php creado
- [x] Schedule registrado en routes/console.php

### T3: Formulario de captura
- [x] FormulaEvaluatorService con symfony/expression-language
- [x] SemaforoService respetando sentido (ascendente/descendente/regular)
- [x] CapturaAvance con campos dinámicos por variable
- [x] Cálculo en tiempo real (wire:change)
- [x] Justificación obligatoria para amarillo/rojo
- [x] Guarda AvanceVariable + Avance.resultado + semaforo_calculado

### T4: Justificaciones IA
- [x] Prompt justificar-avance.blade.php con datos MIR
- [x] JustificacionService usando LlmService::suggest()
- [x] Borrador auto-generado al detectar amarillo/rojo
- [x] Cita supuestos de la MIR
- [x] Sin supuestos: mensaje indicativo
- [x] Guardado dual: justificacion_ia + justificacion_final
- [x] IA no inventa contexto externo (instrucción en prompt)

### T5: Adjuntar evidencia
- [x] Upload PDF, Excel, imágenes, Word (max 10MB)
- [x] Hash SHA-256 generado y almacenado
- [x] Disco privado (local)
- [x] Controller descarga con permisos
- [x] Eliminar solo si no congelado
- [ ] Advertencia si nombre no coincide con medios MIR (omitida — Indicador no tiene relación mediosVerificacion directa)

### T6: Máquina de estados
- [x] AvanceEstadoService::transicionar() valida y ejecuta
- [x] Transiciones inválidas lanzan TransicionInvalidaException
- [x] Historial JSONB append-only con schema definido
- [x] congelado_at al aprobar
- [x] AvanceObservadoNotification y AvanceEnRevisionNotification
- [x] FlujosAvance con timeline visual y botones contextuales

### T7: Congelamiento y desbloqueo
- [x] Avance::estaCongelado() funcional
- [x] CapturaAvance y EvidenciaAvance verifican congelamiento
- [x] SolicitarDesbloqueo (operador)
- [x] GestionarDesbloqueos (admin)
- [x] Aprobar: descongelar + EN_CAPTURA + historial
- [x] Rechazar: registro con resolución
- [x] Solo administrar_usuarios puede gestionar

### T8: Vista seguimiento planeador
- [x] PanelSeguimiento con tabla expandible
- [x] Filtros por programa, estado, semáforo
- [x] Aislamiento Multi-UR (team_id)
- [x] Semáforo visual
- [x] Enlace "Seguimiento" en navegación (desktop + responsive)
- [x] Solo accesible con revisar_avance

---

## Decisiones Técnicas Relevantes

1. **capturado_por nullable:** T2 necesitó hacer `capturado_por` nullable en avances porque el comando `mir:abrir-periodos` puede no encontrar un usuario con `capturar_avance` para cada indicador.

2. **Bypass del state machine para desbloqueo:** El flujo APROBADO → EN_CAPTURA no está en la tabla de transiciones normales. GestionarDesbloqueos actualiza directamente el avance sin pasar por AvanceEstadoService.

3. **MIR medio verification matching omitido:** El Indicador no tiene una relación directa `mediosVerificacion`. La advertencia de coincidencia con medios MIR se omitió en T5 por falta de modelo/relación.

4. **symfony/expression-language v8.0:** Instalado para evaluación de fórmulas MIR. Maneja notación `(A/B) x 100` correctamente.

---

## Deuda Técnica

1. **Medios de verificación MIR:** No hay modelo ni relación para los medios de verificación de indicadores. Cuando se implemente, agregar la advertencia en EvidenciaAvance.
2. **Mail channel en notificaciones:** Las notificaciones solo usan canal `database`. Agregar canal `mail` cuando se configure el servicio de correo.
3. **Permisos granulares en FlujosAvance:** El componente debería verificar `aprobar_avance` para aprobar/observar, pero actualmente usa `revisar_avance`. Alinear con los permisos Spatie definidos.
4. **Vista comparativa entre ejercicios:** Diferida de T8 a Sprint 7 para no sobrecargar el sprint.

---

## Esquema de BD al cierre del Sprint 6

**Tablas nuevas:** notifications, avances, avance_variables, avance_evidencias, desbloqueos
**Tablas modificadas:** metas_periodo (+fecha_apertura, +fecha_cierre), avances (capturado_por nullable)
**Enums nuevos:** EstadoAvance, ComportamientoVariable
**Modelos nuevos:** Tracking/Avance, Tracking/AvanceVariable, Tracking/AvanceEvidencia, Tracking/Desbloqueo
