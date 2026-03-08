## Sprint 7: Evaluación y Reportes

**Objetivo:** Implementar evaluación de programas al cierre del ejercicio, paneles transversales (PED, ODS, UR, Anexos), exportación PDF/Excel/datos abiertos, y análisis de lógica vertical con IA.

**Baseline técnico al iniciar Sprint 7:**
- 349 tests passing, 7 skipped
- Dominio Tracking completo: Avance, AvanceVariable, AvanceEvidencia, Desbloqueo
- FormulaEvaluatorService, SemaforoService, AvanceEstadoService, JustificacionService
- LlmService con suggest(), validate(), transform() + llm_logs
- config/llm.php configurado (model, rate_limit, queue, logging)
- Permiso `exportar_reportes` ya definido en SystemPermission enum y asignado a PLANEADOR y OPERADOR
- Modelos de alineación: PedPlan → PedEje → PedTema → PedEstrategia → PedLineaAccion, OdsObjetivo → OdsMeta, PndEje → PndEstrategia → PndObjetivo
- MirNivel tiene ped_objetivo_estrategico_id, ped_linea_accion_id para alineación
- TipoNivelMir enum: FIN(1), PROPOSITO(2), COMPONENTE(3), ACTIVIDAD(4) con método orden()
- Indicador.activo_seguimiento (boolean) para filtrar indicadores evaluables
- No existe routes/web/evaluation.php ni app/Models/Evaluation/ — área nueva

**Dependencias externas:**
- `barryvdh/laravel-dompdf` o `spatie/laravel-pdf` para PDF
- `maatwebsite/excel` para Excel/CSV
- Ninguna dependencia cruzada fuera del dominio

---

### S7-T1: Migraciones y modelos para evaluación

**Tipo:** feat
**Rama:** `feat/S7-T1-migraciones-evaluacion`

**Descripción:**
Crear tablas y modelos del dominio Evaluation: `evaluaciones_programa`, `anexos_transversales`, pivote `indicador_anexo_transversal`. Seeder con los 4 anexos transversales base.

**Decisiones técnicas:**

- **`evaluaciones_programa`:** programa_presupuestario_id (FK), ejercicio_fiscal (int), indice_eficacia (decimal 8,4), desglose_niveles (JSONB: {fin: %, proposito: %, componentes: %, actividades: %}), conteo_semaforos (JSONB: {verde, amarillo, rojo, sin_dato}), indicadores_evaluados (int), indicadores_no_evaluados (int), configuracion_calculo (JSONB: pesos usados), analisis_ia (text nullable — rupturas verticales), calculado_por (FK users), timestamps. Unique: [programa_presupuestario_id, ejercicio_fiscal].
- **`anexos_transversales`:** id, nombre, clave (string unique), descripcion (text nullable), activo (boolean), orden (int), timestamps.
- **`indicador_anexo_transversal`:** indicador_id FK, anexo_transversal_id FK, timestamps. Unique: [indicador_id, anexo_transversal_id].
- Modelos en `app/Models/Evaluation/`.
- Seeder: Género, Niñas Niños y Adolescentes, Cambio Climático, Anticorrupción.

**Criterios de aceptación:**

- [ ] Migración create_evaluaciones_programa_table con JSONB desglose_niveles y conteo_semaforos
- [ ] Migración create_anexos_transversales_table
- [ ] Migración create_indicador_anexo_transversal_table (pivote)
- [ ] Modelo EvaluacionPrograma con fillable, casts, relaciones
- [ ] Modelo AnexoTransversal con fillable, relación belongsToMany(Indicador)
- [ ] Indicador::anexosTransversales() relación belongsToMany agregada
- [ ] Seeder AnexosTransversalesSeeder con 4 registros base
- [ ] migrate:fresh --seed sin errores
- [ ] Tests: modelos, relaciones, seeder (~6 tests)

---

### S7-T2: Etiquetado de indicadores con Anexos Transversales

**Tipo:** feat
**Rama:** `feat/S7-T2-etiquetado-anexos-transversales`
**Depende de:** S7-T1

**Descripción:**
Agregar checkboxes de anexos transversales en la ficha del indicador (MirEditor de Sprint 4). Persistir en tabla pivote. Mostrar etiquetas en MIR y panel de seguimiento.

**Decisiones técnicas:**

- Integrar en el componente existente de ficha de indicador (buscar en MirEditor o IndicadorEditor)
- Usar `sync()` para la relación M:M
- Mostrar badges con nombre del anexo en las vistas de MIR y PanelSeguimiento

**Criterios de aceptación:**

- [ ] Checkboxes de anexos transversales en ficha del indicador
- [ ] Guardado con sync() en tabla pivote
- [ ] Badges visibles en la vista MIR
- [ ] Badges visibles en PanelSeguimiento
- [ ] Tests: sync, display (~3 tests)

---

### S7-T3: Cálculo del Índice de Eficacia

**Tipo:** feat
**Rama:** `feat/S7-T3-indice-eficacia`
**Depende de:** S7-T1

**Descripción:**
Comando artisan `evaluacion:calcular-indice {programa} {ejercicio}` que calcula el índice de eficacia 0-100. Promedio ponderado del porcentaje de avance por nivel MIR.

**Decisiones técnicas:**

- Pesos por nivel (configurables en config/evaluation.php):
  - FIN: 40%, PROPOSITO: 30%, COMPONENTES: 20%, ACTIVIDADES: 10%
- Por cada nivel: promedio de (resultado/meta * 100) de sus indicadores con activo_seguimiento=true
- Indicadores sin avances aprobados en el ejercicio → excluidos, contados como no_evaluados
- Semáforo por indicador: último avance aprobado del ejercicio
- Guarda en evaluaciones_programa con desglose completo

**Criterios de aceptación:**

- [ ] Comando evaluacion:calcular-indice calcula y guarda
- [ ] Conteo de semáforos (verde, amarillo, rojo, sin_dato)
- [ ] Pesos en config/evaluation.php, no hardcodeados
- [ ] Solo indicadores con activo_seguimiento=true
- [ ] Excluye indicadores sin capturas → indicadores_no_evaluados
- [ ] Tests: cálculo correcto, pesos, exclusiones (~6 tests)

---

### S7-T4: Evaluación por programa — Vista de cierre

**Tipo:** feat
**Rama:** `feat/S7-T4-evaluacion-programa`
**Depende de:** S7-T3

**Descripción:**
Pantalla Livewire de evaluación integral al cierre del ejercicio para un programa específico. 5 secciones.

**Secciones:**
1. Resumen ejecutivo (alineación PED, objetivo central del programa)
2. Tablero de semáforos consolidado (por nivel, gráfico de barras o cards)
3. Comparativa vs ejercicio anterior (tendencias: mejoró/empeoró/estable)
4. Análisis de desviaciones (justificaciones aprobadas, supuestos incumplidos)
5. Indicadores crónicos en rojo (2+ de últimos 3 ejercicios)

**Decisiones técnicas:**

- Tendencia: comparar índice del ejercicio actual vs anterior. Por indicador: comparar último semáforo.
- Indicadores crónicos: query avances aprobados en últimos 3 ejercicios, contar rojos por indicador
- Usa EvaluacionPrograma ya calculada en T3
- Route: `routes/web/evaluation.php`

**Criterios de aceptación:**

- [ ] Vista completa con 5 secciones
- [ ] Tendencia por indicador: mejoró / empeoró / estable
- [ ] Indicadores crónicos destacados visualmente
- [ ] Solo accesible con exportar_reportes
- [ ] routes/web/evaluation.php creado
- [ ] Tests: render, tendencias, crónicos, permisos (~5 tests)

---

### S7-T5: Validación de lógica vertical al cierre [REQUIERE_API_IA]

**Tipo:** feat
**Rama:** `feat/S7-T5-logica-vertical-cierre`
**Depende de:** S7-T3

**Descripción:**
La IA analiza semáforos por nivel MIR y detecta rupturas en la cadena causal. Se integra al cálculo de evaluación.

**Decisiones técnicas:**

- Prompt Blade en `resources/views/prompts/evaluation/analizar-rupturas.blade.php`
- Usa LlmService::suggest() — mock implementado para cuando no hay API key
- Resultado guardado en evaluaciones_programa.analisis_ia
- Lenguaje diagnóstico, no acusatorio

**Criterios de aceptación:**

- [ ] Prompt analizar-rupturas.blade.php con semáforos por nivel y supuestos
- [ ] Servicio detecta rupturas (actividades verde + componente rojo = diseño)
- [ ] Sugerencia: problema de ejecución vs diseño
- [ ] IA no emite juicios sobre UR
- [ ] Mock retorna análisis placeholder cuando no hay API key
- [ ] Tests con mock LlmService (~4 tests)

---

### S7-T6: Paneles de evaluación transversal

**Tipo:** feat
**Rama:** `feat/S7-T6-evaluacion-transversal`
**Depende de:** S7-T2, S7-T3

**Descripción:**
4 paneles que cruzan todos los programas para visión global.

**Vistas (tabs o sub-rutas):**
- Por Eje PED: programas agrupados por eje, conteo semáforos, índice promedio
- Por ODS: indicadores por objetivo ODS (via alineación MirNivel → PED → ODS)
- Por Unidad Responsable: ranking de teams por índice
- Por Anexo Transversal: indicadores filtrados por anexo, cruzando dependencias

**Decisiones técnicas:**

- Un solo componente PanelTransversal con tabs
- Datos derivados de evaluaciones_programa + alineación existente (MirNivel → pedObjetivoEstrategico → pedEje)
- ODS: relación PedLineaAccion → OdsMeta (verificar si existe)
- Advertencia si programas sin alineación

**Criterios de aceptación:**

- [ ] 4 vistas con filtros (tabs)
- [ ] Ranking por índice de desempeño
- [ ] Datos de Matriz de Alineación
- [ ] Advertencia para programas sin alineación
- [ ] Solo con exportar_reportes
- [ ] Tests: render, aislamiento, datos (~5 tests)

---

### S7-T7: Exportación a PDF y Excel

**Tipo:** feat
**Rama:** `feat/S7-T7-exportacion-pdf-excel`
**Depende de:** S7-T4, S7-T6

**Descripción:**
Generar reportes exportables. Reportes pesados en cola de jobs.

**Reportes:**
1. MIR formato oficial (4×4 matrix)
2. Fichas técnicas de indicadores
3. Avance trimestral por programa
4. Evaluación anual por programa
5. Transversal por Eje PED / ODS / Anexo

**Decisiones técnicas:**

- Instalar `barryvdh/laravel-dompdf` para PDF
- Instalar `maatwebsite/excel` para Excel
- Jobs para reportes multi-programa (GenerarReportePdfJob, GenerarReporteExcelJob)
- Notificación cuando job completa, con link de descarga
- Almacenar en `storage/app/private/reportes/` con TTL (borrar después de 24h)

**Criterios de aceptación:**

- [ ] Cada reporte con sello de tiempo y período
- [ ] PDF con encabezado institucional configurable
- [ ] Excel con hojas separadas por sección
- [ ] Jobs para reportes pesados + notificación
- [ ] Solo con exportar_reportes
- [ ] Tests: generación PDF, Excel, job dispatch (~6 tests)

---

### S7-T8: Exportación de datos abiertos con Diccionario

**Tipo:** feat
**Rama:** `feat/S7-T8-datos-abiertos`
**Depende de:** S7-T3

**Descripción:**
Exportar datos en CSV y JSON con diccionario de datos, empaquetados en ZIP.

**Criterios de aceptación:**

- [ ] CSV con codificación UTF-8
- [ ] JSON estructurado
- [ ] Diccionario de datos generado automáticamente
- [ ] ZIP: datos + diccionario
- [ ] Cumple requisitos Ley General de Transparencia
- [ ] Tests: CSV, JSON, ZIP, diccionario (~4 tests)

---

## Orden de ejecución

```
S7-T1 → S7-T2 → S7-T6
       → S7-T3 → S7-T4 → S7-T7
              → S7-T5
              → S7-T8
```

- **T1** bloquea todo (modelos base)
- **T2** (etiquetado) y **T3** (cálculo) pueden paralelizarse después de T1
- **T4** requiere T3 (evaluación calculada)
- **T5** requiere T3 (IA analiza evaluación)
- **T6** requiere T2+T3
- **T7** requiere T4+T6
- **T8** requiere T3

## Notas de integración con Sprint 6

| Recurso Sprint 6 | Reutilizado en Sprint 7 |
|---|---|
| Avance.resultado, semaforo_calculado | Input para cálculo de índice (T3) |
| Avance.estado = APROBADO | Solo avances aprobados cuentan para evaluación |
| Avance.justificacion_final | Sección de desviaciones en T4 |
| SemaforoService | Referencia para conteo de semáforos |
| Indicador.activo_seguimiento | Filtro de indicadores evaluables |
| MirNivel.supuestos | Contexto para análisis IA (T5) |
| PanelSeguimiento | Link desde evaluación a seguimiento |
| LlmService::suggest() | Análisis de rupturas verticales (T5) |

## Issues con [REQUIERE_API_IA]

- **S7-T5** usa LlmService::suggest() — implementar con mock que retorna análisis placeholder estático cuando `config('llm.api_key')` está vacío.
