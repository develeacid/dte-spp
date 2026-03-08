# Issues de Linear — Sprint 7: Evaluación y Reportes

## Proyecto

| Campo | Valor |
|-------|-------|
| **Nombre** | Sprint 7: Evaluación y Reportes |
| **Equipo** | DTE |
| **Estado** | Backlog |
| **Descripción** | Evaluación de programas al cierre, paneles transversales (PED, ODS, UR, Anexos), exportación PDF/Excel/datos abiertos, análisis IA de lógica vertical. |

---

## Issue 1: Migraciones y modelos para evaluación

| Campo | Valor |
|-------|-------|
| **Título** | Migraciones y modelos para evaluación |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 1 (Urgent) — blocker de todo el sprint |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: db`, `stack: backend`, `mod: evaluacion` |
| **Bloqueado por** | — |

**Descripción:**

```markdown
## Contexto
Crear tablas del dominio Evaluation: evaluaciones_programa (índice eficacia + desglose JSONB), anexos_transversales, pivote indicador_anexo_transversal. Modelos en app/Models/Evaluation/. Seeder con 4 anexos base.

**Rama:** `feat/S7-T1-migraciones-evaluacion`

## Esquema
- `evaluaciones_programa`: programa_id, ejercicio_fiscal, indice_eficacia, desglose_niveles (JSONB), conteo_semaforos (JSONB), indicadores_evaluados, indicadores_no_evaluados, configuracion_calculo (JSONB), analisis_ia, calculado_por, timestamps. Unique [programa_id, ejercicio_fiscal].
- `anexos_transversales`: nombre, clave (unique), descripcion, activo, orden, timestamps.
- `indicador_anexo_transversal`: indicador_id FK, anexo_transversal_id FK, timestamps. Unique compuesto.

## Criterios de aceptación
- [ ] Migración create_evaluaciones_programa_table con JSONB
- [ ] Migración create_anexos_transversales_table
- [ ] Migración create_indicador_anexo_transversal_table (pivote)
- [ ] Modelo EvaluacionPrograma con casts y relaciones
- [ ] Modelo AnexoTransversal con belongsToMany(Indicador)
- [ ] Indicador::anexosTransversales() agregado
- [ ] Seeder AnexosTransversalesSeeder (4 registros)
- [ ] Tests: modelos, relaciones, seeder (~6 tests)
```

---

## Issue 2: Etiquetado de indicadores con Anexos Transversales

| Campo | Valor |
|-------|-------|
| **Título** | Etiquetado de indicadores con Anexos Transversales |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 3 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: evaluacion` |
| **Bloqueado por** | S7-T1 |

**Descripción:**

```markdown
## Contexto
Checkboxes de anexos transversales en la ficha del indicador. Guardado M:M con sync(). Badges visibles en MIR y PanelSeguimiento.

**Rama:** `feat/S7-T2-etiquetado-anexos-transversales`

## Criterios de aceptación
- [ ] Checkboxes en ficha del indicador
- [ ] Guardado con sync() en pivote
- [ ] Badges en vista MIR
- [ ] Badges en PanelSeguimiento
- [ ] Tests: sync, display (~3 tests)
```

---

## Issue 3: Cálculo del Índice de Eficacia

| Campo | Valor |
|-------|-------|
| **Título** | Cálculo del Índice de Eficacia |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) — ruta crítica |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `mod: evaluacion` |
| **Bloqueado por** | S7-T1 |

**Descripción:**

```markdown
## Contexto
Comando artisan evaluacion:calcular-indice que calcula índice 0-100 por programa al cierre. Promedio ponderado por nivel MIR con pesos configurables.

**Rama:** `feat/S7-T3-indice-eficacia`

## Pesos (config/evaluation.php)
- Fin: 40%, Propósito: 30%, Componentes: 20%, Actividades: 10%

## Criterios de aceptación
- [ ] Comando evaluacion:calcular-indice calcula y guarda
- [ ] Conteo de semáforos (verde, amarillo, rojo, sin_dato)
- [ ] Pesos en config/evaluation.php
- [ ] Solo indicadores con activo_seguimiento=true
- [ ] Excluye indicadores sin capturas → indicadores_no_evaluados
- [ ] Tests: cálculo, pesos, exclusiones (~6 tests)
```

---

## Issue 4: Evaluación por programa — Vista de cierre

| Campo | Valor |
|-------|-------|
| **Título** | Evaluación por programa — Vista de cierre |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: evaluacion` |
| **Bloqueado por** | S7-T3 |

**Descripción:**

```markdown
## Contexto
Pantalla Livewire de evaluación integral con 5 secciones: resumen ejecutivo, tablero semáforos, comparativa vs ejercicio anterior, desviaciones, indicadores crónicos.

**Rama:** `feat/S7-T4-evaluacion-programa`

## Criterios de aceptación
- [ ] Vista con 5 secciones completas
- [ ] Tendencia por indicador: mejoró / empeoró / estable
- [ ] Indicadores crónicos (rojo 2+ de últimos 3 ejercicios) destacados
- [ ] Solo con exportar_reportes
- [ ] routes/web/evaluation.php creado
- [ ] Tests: render, tendencias, crónicos, permisos (~5 tests)
```

---

## Issue 5: Validación de lógica vertical al cierre [REQUIERE_API_IA]

| Campo | Valor |
|-------|-------|
| **Título** | Validación de lógica vertical al cierre |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 3 |
| **Labels** | `type: feature`, `stack: backend`, `stack: ai`, `mod: evaluacion` |
| **Bloqueado por** | S7-T3 |

**Descripción:**

```markdown
## Contexto
IA analiza semáforos por nivel MIR y detecta rupturas causales. Usa LlmService::suggest() con mock cuando no hay API key.

**Rama:** `feat/S7-T5-logica-vertical-cierre`

## [REQUIERE_API_IA]
Mock: retorna análisis placeholder estático. Ver docs/plans/tickets/pendientes-ia.md.

## Criterios de aceptación
- [ ] Prompt analizar-rupturas.blade.php
- [ ] Detecta rupturas (verde inferior + rojo superior = diseño)
- [ ] Sugerencia: ejecución vs diseño
- [ ] IA sin juicios sobre UR
- [ ] Mock para API key vacío
- [ ] Tests con mock LlmService (~4 tests)
```

---

## Issue 6: Paneles de evaluación transversal

| Campo | Valor |
|-------|-------|
| **Título** | Paneles de evaluación transversal |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `mod: evaluacion` |
| **Bloqueado por** | S7-T2, S7-T3 |

**Descripción:**

```markdown
## Contexto
4 paneles transversales con tabs: Eje PED, ODS, UR, Anexo Transversal. Ranking por índice. Advertencia si programas sin alineación.

**Rama:** `feat/S7-T6-evaluacion-transversal`

## Criterios de aceptación
- [ ] 4 vistas con filtros (tabs)
- [ ] Ranking por índice
- [ ] Datos de Matriz de Alineación
- [ ] Advertencia para programas sin alineación
- [ ] Solo con exportar_reportes
- [ ] Tests: render, datos, aislamiento (~5 tests)
```

---

## Issue 7: Exportación a PDF y Excel

| Campo | Valor |
|-------|-------|
| **Título** | Exportación a PDF y Excel |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 2 (High) |
| **Estimación** | 8 |
| **Labels** | `type: feature`, `stack: backend`, `stack: frontend`, `stack: infra`, `mod: evaluacion` |
| **Bloqueado por** | S7-T4, S7-T6 |

**Descripción:**

```markdown
## Contexto
Reportes PDF y Excel: MIR oficial, fichas técnicas, avance trimestral, evaluación anual, transversal. Jobs para reportes pesados.

**Rama:** `feat/S7-T7-exportacion-pdf-excel`

## Dependencias
- barryvdh/laravel-dompdf para PDF
- maatwebsite/excel para Excel

## Criterios de aceptación
- [ ] Sello de tiempo y período en cada reporte
- [ ] PDF con encabezado institucional configurable
- [ ] Excel con hojas separadas
- [ ] Jobs + notificación para reportes pesados
- [ ] Solo con exportar_reportes
- [ ] Tests: PDF, Excel, job dispatch (~6 tests)
```

---

## Issue 8: Exportación de datos abiertos con Diccionario

| Campo | Valor |
|-------|-------|
| **Título** | Exportación de datos abiertos con Diccionario |
| **Equipo** | DTE |
| **Proyecto** | Sprint 7: Evaluación y Reportes |
| **Estado** | Backlog |
| **Prioridad** | 3 (Medium) |
| **Estimación** | 5 |
| **Labels** | `type: feature`, `stack: backend`, `mod: evaluacion` |
| **Bloqueado por** | S7-T3 |

**Descripción:**

```markdown
## Contexto
Exportar CSV y JSON con diccionario de datos, empaquetado en ZIP. Cumple Ley General de Transparencia.

**Rama:** `feat/S7-T8-datos-abiertos`

## Criterios de aceptación
- [ ] CSV UTF-8
- [ ] JSON estructurado
- [ ] Diccionario de datos automático
- [ ] ZIP: datos + diccionario
- [ ] Cumple requisitos transparencia
- [ ] Tests: CSV, JSON, ZIP, diccionario (~4 tests)
```

---

## Resumen

| ID | Ticket | Prioridad | Est. | Labels | Bloqueado por |
|---|---|---|---|---|---|
| — | Migraciones y modelos evaluación | Urgent (1) | 5 | feature, db, backend, evaluacion | — |
| — | Etiquetado anexos transversales | Medium (3) | 3 | feature, backend, frontend, evaluacion | T1 |
| — | Cálculo Índice de Eficacia | High (2) | 5 | feature, backend, evaluacion | T1 |
| — | Evaluación por programa — cierre | High (2) | 8 | feature, backend, frontend, evaluacion | T3 |
| — | Validación lógica vertical [IA] | Medium (3) | 3 | feature, backend, ai, evaluacion | T3 |
| — | Paneles evaluación transversal | Medium (3) | 8 | feature, backend, frontend, evaluacion | T2, T3 |
| — | Exportación PDF y Excel | High (2) | 8 | feature, backend, frontend, infra, evaluacion | T4, T6 |
| — | Datos abiertos con Diccionario | Medium (3) | 5 | feature, backend, evaluacion | T3 |

**Total puntos:** 45
**Ruta crítica:** T1 → T3 → T4 → T7 (26 pts)
**Paralelizable:** T2 ∥ T3 después de T1; T5 ∥ T8 después de T3
**Tests estimados:** ~39 nuevos
