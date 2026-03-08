# Sprint 7 Retrospective — Evaluación y Reportes

**Fecha:** 2026-03-07
**Baseline inicial:** 349 tests, 7 skipped
**Baseline final:** 392 tests, 7 skipped (43 nuevos)
**Ramas completadas:** 8/8

---

## Resumen de Ejecución

| Task | Rama | Tests | Estado |
|------|------|:---:|--------|
| T1: Migraciones y modelos | `feat/S7-T1-migraciones-evaluacion` | 6 | Completado |
| T2: Etiquetado anexos transversales | `feat/S7-T2-etiquetado-anexos-transversales` | 3 | Completado |
| T3: Cálculo Índice de Eficacia | `feat/S7-T3-indice-eficacia` | 6 | Completado |
| T4: Evaluación por programa — cierre | `feat/S7-T4-evaluacion-programa` | 5 | Completado |
| T5: Validación lógica vertical [IA] | `feat/S7-T5-logica-vertical-cierre` | 4 | Completado |
| T6: Paneles evaluación transversal | `feat/S7-T6-evaluacion-transversal` | 6 | Completado |
| T7: Exportación PDF y Excel | `feat/S7-T7-exportacion-pdf-excel` | 6 | Completado |
| T8: Datos abiertos con Diccionario | `feat/S7-T8-datos-abiertos` | 7 | Completado |

---

## Criterios de Aceptación — Cumplimiento

### T1: Migraciones y modelos
- [x] Migración create_evaluaciones_programa_table con JSONB desglose_niveles y conteo_semaforos
- [x] Migración create_anexos_transversales_table
- [x] Migración create_indicador_anexo_transversal_table (pivote)
- [x] Modelo EvaluacionPrograma con fillable, casts, relaciones
- [x] Modelo AnexoTransversal con fillable, relación belongsToMany(Indicador)
- [x] Indicador::anexosTransversales() relación belongsToMany agregada
- [x] Seeder AnexosTransversalesSeeder con 4 registros base
- [x] migrate:fresh --seed sin errores

### T2: Etiquetado anexos transversales
- [x] Checkboxes de anexos transversales en ficha del indicador (MirEditor)
- [x] Guardado con sync() en tabla pivote
- [x] Badges visibles en la vista MIR
- [x] Badges visibles en PanelSeguimiento

### T3: Cálculo del Índice de Eficacia
- [x] Comando evaluacion:calcular-indice calcula y guarda
- [x] Conteo de semáforos (verde, amarillo, rojo, sin_dato)
- [x] Pesos en config/evaluation.php, no hardcodeados
- [x] Solo indicadores con activo_seguimiento=true
- [x] Excluye indicadores sin capturas → indicadores_no_evaluados

### T4: Evaluación por programa — Vista de cierre
- [x] Vista completa con 5 secciones (resumen, semáforos, comparativa, desviaciones, crónicos)
- [x] Tendencia por indicador: mejoró / empeoró / estable
- [x] Indicadores crónicos destacados visualmente
- [x] Solo accesible con exportar_reportes
- [x] routes/web/evaluation.php creado

### T5: Validación lógica vertical [IA]
- [x] Prompt analizar-rupturas.blade.php con semáforos por nivel y supuestos
- [x] Servicio detecta rupturas (verde inferior + rojo superior = diseño)
- [x] Sugerencia: problema de ejecución vs diseño
- [x] Mock retorna análisis placeholder cuando no hay API key
- [x] Tests con mock LlmService

### T6: Paneles de evaluación transversal
- [x] 4 vistas con tabs (PED, ODS, UR, Anexo)
- [x] Ranking por índice de desempeño
- [x] Datos de Matriz de Alineación
- [x] Advertencia para programas sin alineación
- [x] Solo con exportar_reportes

### T7: Exportación PDF y Excel
- [x] Cada reporte con sello de tiempo y período
- [x] PDF con encabezado institucional configurable
- [x] Excel con hojas separadas por sección
- [x] Jobs para reportes pesados + notificación ReporteListoNotification
- [x] Solo con exportar_reportes
- [x] 5 tipos PDF + 4 tipos Excel

### T8: Datos abiertos con Diccionario
- [x] CSV con codificación UTF-8 (BOM)
- [x] JSON estructurado con metadata
- [x] Diccionario de datos generado automáticamente (11 campos)
- [x] ZIP: datos CSV + datos JSON + diccionario
- [x] Cumple requisitos Ley General de Transparencia

---

## Decisiones Técnicas Relevantes

1. **EvaluacionProgramaView usa `evaluacionModel`:** Propiedad renombrada para evitar conflicto con el route model binding automático de Livewire.

2. **Índice de eficacia cap 0-200:** El cálculo permite valores superiores a 100 para indicadores que superan la meta. Capped a 200 como máximo.

3. **ODS relationship chain:** PED → ODS se navega a través de pedObjetivoEstrategico → pndObjetivos → odsMetas → odsObjetivo. No hay relación directa PED-ODS.

4. **PDF/Excel packages:** barryvdh/laravel-dompdf v3.1.1 + maatwebsite/excel v3.1.67. Jobs async para reportes multi-programa.

5. **Datos abiertos UTF-8 BOM:** CSV incluye BOM (\xEF\xBB\xBF) para compatibilidad con Excel en español.

---

## Deuda Técnica

1. **Navegación Evaluación:** Falta enlace "Evaluación" en navigation-menu.blade.php para acceder a las vistas de evaluación y reportes.
2. **Cleanup de reportes temporales:** El TTL de 24h para reportes en storage está configurado pero no hay comando/scheduler para limpiar archivos expirados.
3. **ODS relationship gap:** La cadena PED → ODS es indirecta y compleja. Considerar agregar relación directa en futuro sprint.
4. **Mail channel en notificaciones:** ReporteListoNotification solo usa canal `database`. Agregar `mail` cuando se configure el servicio.

---

## Esquema de BD al cierre del Sprint 7

**Tablas nuevas:** evaluaciones_programa, anexos_transversales, indicador_anexo_transversal
**Modelos nuevos:** Evaluation/EvaluacionPrograma, Evaluation/AnexoTransversal
**Seeders nuevos:** AnexosTransversalesSeeder (4 temas transversales)
**Config nuevo:** config/evaluation.php (pesos + exports)
**Paquetes nuevos:** barryvdh/laravel-dompdf, maatwebsite/excel
**Rutas nuevas:** routes/web/evaluation.php (programa, transversal, exportar/*, datos-abiertos/*)
**Jobs nuevos:** GenerarReportePdfJob, GenerarReporteExcelJob
**Notificaciones nuevas:** ReporteListoNotification

---

## Dependencias con Sprint 8

| Recurso Sprint 7 | Reutilizado en Sprint 8 |
|---|---|
| EvaluacionPrograma.analisis_ia | Campo para análisis IA mejorado |
| LogicaVerticalService | Refactor con LlmService mejorado |
| LlmService::suggest() | Refactor en S8-T1 |
| config/evaluation.php | Extensible para nuevas configs |
