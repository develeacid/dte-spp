# Sistema de Reportes PbR-SED — Design Document

## Contexto

Los reportes en administración pública mexicana se clasifican por consumidor y riesgo legal, no por dominio técnico. El sistema ya tiene infraestructura de exportación (DomPDF + Laravel Excel, jobs async, permisos Spatie) y 5 tipos de export existentes. Este diseño agrega reportes faltantes y mejora los existentes para cumplir requisitos institucionales.

## Alcance

**5 reportes** + 1 documento arquitectónico:

| # | Nivel | Reporte | Formato | Permiso |
|---|-------|---------|---------|---------|
| R1 | Legal | Avance Trimestral (mejorar existente) | PDF/Excel | planeador, admin |
| R2 | Táctico | Ficha de Monitoreo y Evaluación (FMyE) | PDF | planeador, admin |
| R3 | Transparencia | MIR Aprobada (mejorar existente) | PDF | cualquier autenticado |
| R4 | Operativo | Sábana de Captura | Pantalla + PDF + Excel | operador, planeador, admin |
| R5 | Operativo | Concentrado de Captura | Pantalla + PDF + Excel | operador, planeador, admin |
| Doc | — | Integración futura presupuesto + padrón | Markdown | — |

**Fuera de alcance:** Avance financiero (presupuesto), Padrón de Beneficiarios, georreferenciación, Cuenta Pública. Se documenta arquitectura de integración futura.

## Detalle por Reporte

### R1 — Avance Trimestral (mejorado)

**Cambios sobre el existente** (`AvanceTrimestralPdfExport`, `avance-trimestral.blade.php`):
- Agregar bloque Vo.Bo. al final del PDF (nombre titular, cargo, firma manuscrita, fecha)
- El titular se obtiene de `Team.titular`
- Sin cambios en la query ni en el Excel

### R2 — FMyE (Ficha de Monitoreo y Evaluación)

**Reporte nuevo.** 1-2 páginas PDF por Programa Presupuestario.

Secciones:
1. **Datos generales**: clave, nombre, UR (team.name), titular, ejercicio fiscal
2. **Alineación estratégica**: Cascada PED Objetivo → PND Objetivo → ODS Meta (vía `alineacion_ped_pnd`, `alineacion_pnd_ods` y relaciones del programa derivado)
3. **Resumen MIR**: Tabla con Fin/Propósito/Componente/Actividad — resumen narrativo + indicador + semáforo último trimestre
4. **Índice de eficacia**: Valor global + desglose por nivel (pesos 40/30/20/10) — usa `IndiceEficaciaService` o `EvaluacionPrograma` existente
5. **Semáforo histórico**: Tabla trimestres (T1-T4) con semáforo por indicador
6. **Vo.Bo.**: Mismo bloque que R1

Archivos nuevos:
- `app/Exports/Pdf/FmyePdfExport.php`
- `resources/views/exports/pdf/fmye.blade.php`

### R3 — MIR Aprobada (formato ciudadano)

**Cambios sobre el existente** (`MirPdfExport`, `mir.blade.php`):
- Agregar encabezado institucional formal (ya existe en config)
- Agregar fecha de aprobación (`planeacion_completada_at`)
- Agregar pie de página: "Documento público conforme al Art. 70 LGTAIP"
- Sin Vo.Bo. (es información pública)
- Permiso: cualquier usuario autenticado (sin `exportar_reportes`)

### R4 — Sábana de Captura

**Vista Livewire nueva** + exportación PDF/Excel.

Vista en pantalla:
- Filtros: UR (admin ve todas), programa, trimestre, estado (pendiente/observado/vencido/aprobado)
- Tabla: Indicador | Meta Periodo | Estado | Operador responsable | Días restantes/vencidos
- Datos: `MetaPeriodo` con su `Avance` (o sin él para pendientes/vencidos)

Archivos nuevos:
- `app/Livewire/Tracking/SabanaCaptura.php`
- `resources/views/livewire/tracking/sabana-captura.blade.php`
- `app/Exports/Pdf/SabanaCapturaPdfExport.php`
- `app/Exports/Excel/SabanaCapturaExcelExport.php`
- `resources/views/exports/pdf/sabana-captura.blade.php`

### R5 — Concentrado de Captura

**Vista Livewire nueva** + exportación PDF/Excel.

Vista en pantalla:
- Filtros: UR (admin ve todas), rango de fechas
- Métricas resumen: capturados hoy, esta semana, aprobados, observados, vencidos
- Tabla: Programa | Indicador | Capturados | Aprobados | Observados | Vencidos
- Datos: `Avance` agrupado por programa/indicador con conteos por estado

Archivos nuevos:
- `app/Livewire/Tracking/ConcentradoCaptura.php`
- `resources/views/livewire/tracking/concentrado-captura.blade.php`
- `app/Exports/Pdf/ConcentradoCapturaPdfExport.php`
- `app/Exports/Excel/ConcentradoCapturaExcelExport.php`
- `resources/views/exports/pdf/concentrado-captura.blade.php`

## Arquitectura

### Permisos

Nuevos permisos Spatie en `RolesAndPermissionsSeeder`:
- `ver_sabana_captura` → operador, planeador, admin
- `ver_concentrado_captura` → operador, planeador, admin
- `exportar_mir_publica` no se necesita (cualquier autenticado puede ver)
- Los reportes R1 y R2 usan el permiso existente `exportar_reportes`

### Rutas

```
# R1 y R3: ya existen en routes/web/evaluation.php, solo ajustar lógica
# R2: agregar tipo 'fmye' al ExportController existente

# R4 y R5: nuevas rutas en routes/web/tracking.php
GET /seguimiento/sabana-captura          → SabanaCaptura (Livewire)
GET /seguimiento/concentrado-captura     → ConcentradoCaptura (Livewire)
```

### Patrón de exportación

Reutilizar la infraestructura existente:
- PDF: clase Export con `generate(): string` que usa DomPDF + vista Blade
- Excel: clase Export que implementa `FromCollection, WithHeadings`
- Async: jobs `GenerarReportePdfJob` / `GenerarReporteExcelJob` ya soportan nuevos tipos

### Vo.Bo. (componente reutilizable)

Crear partial Blade `resources/views/exports/pdf/partials/vobo.blade.php`:
```
Nombre: {{ $titular }}
Cargo: Titular de {{ $dependencia }}
Firma: ____________________
Fecha: {{ $fecha }}
```

Usado por R1 (avance-trimestral) y R2 (fmye).

## Decisiones

- **No se crean nuevos permisos para R1, R2, R3** — se reutiliza `exportar_reportes` existente; R3 no requiere permiso especial.
- **R4 y R5 son vistas Livewire** porque son herramientas de trabajo diario, no solo documentos para descargar.
- **FMyE solo PDF** — es un documento ejecutivo de 1-2 páginas, Excel no aporta valor.
- **Análisis IA excluido de FMyE** — es para uso interno, no para el titular.
- **Admin ve todos los programas/URs** en R4 y R5 (bypass de filtro por team, como en ListaProgramas).

## Orden de implementación

1. R1 — Avance Trimestral + Vo.Bo. (cambio mínimo al existente)
2. R2 — FMyE (reporte nuevo, mayor complejidad)
3. R3 — MIR Aprobada (cambio menor al existente)
4. R4 — Sábana de Captura (Livewire + exports)
5. R5 — Concentrado de Captura (Livewire + exports)
6. Doc — Integración futura presupuesto + padrón
