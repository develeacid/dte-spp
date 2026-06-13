# Seguimiento de Brechas V2 — dte-spp + GeoBase

> **Documento vivo.** Actualizar al cierre de cada sesión de trabajo: cambiar el estado de las brechas tocadas, mover sprints a ✅, y refrescar la fecha + el commit de referencia.
>
> Fuente original (fuera del repo): `/home/eleacid/code/laravel/matrices de indicadores/` (`informe_complementariedad_v2.md`, `reporte_brechas_dte_spp_v2.md`, `reporte_brechas_geobase_v2.md`). Este doc es el **resumen ejecutable de progreso**, no reemplaza los reportes completos.

**Última actualización:** 2026-06-13 (par CONAPO completo)
**Estado global:** Informe V2 (2026-05-19) detectó **18 brechas conjuntas reales** del ecosistema en 147 conceptos del temario MIR. Sprints V2 1-4 dte-spp ✅ + geobase S1 ✅ + decisiones cross-sistema ✅ + **par CONAPO COMPLETO** (geobase #30 proveedor + dte-spp #38 consumidor, 2026-06-13). Siguiente foco: sprints M (IAFF+cierre fiscal, Clave presupuestal).

## Leyenda

| Símbolo | Significado |
|---|---|
| ✅ | Implementado y mergeado |
| 🟡 | Parcial / en curso |
| ❌ | Pendiente |
| 🚫 | N/A (delegado a otro sistema o fuera de scope software) |
| 🔒 | Bloqueado (dependencia institucional externa) |

---

## dte-spp — Sprints V2

| # | Sprint | Tamaño | Estado | Notas |
|---|---|---|---|---|
| 1 | Hardening MIR | XS | ✅ | NOT NULL load-bearing, UNIQUE FIN/Propósito, ventana captura SHCP, análisis desviación JSONB, `revisiones_meta`, DS-07. (CLAUDE.md "Hardening MIR 2026-06-06") |
| 2 | Semaforización 4-rangos + validaciones duras | S | ✅ | `rojo_alto`, rangos capturables B3-B7, `FrecuenciaMedicion`. (CLAUDE.md "Semaforización 4 rangos 2026-06-07") |
| 3 | Modelo Evaluación Externa estructurado | S | ✅ | `EvaluacionExterna`, `InformeEvaluacion`, Hallazgo→Recomendación→ASM (C-143), C-146. |
| 4 | MV CREMA + Supuestos estructurados | S | ✅ | `mir_supuestos`, `crema_validaciones_mv`, `tipo_fuente` MV (B9), V2-A8/B8-B10. |
| — | Seeders demo V2 | XS | ✅ | PR #35 mergeado (`b3d6ad2`). Demo completo + fix B4. |
| 5 | IAFF persistido + Cierre fiscal 4 fases + Conciliación | M | ❌ | Cuenta Pública + auditoría ASF. Incluye C-022 (Población Atendida persistida) y C-111 (conciliación, vista derivada). |
| 6 | Clave presupuestal canónica + Modalidades S/U/E/B + Estructura Programática 6 niveles | M | ❌ | Compatibilidad SHCP/sistemas estatales. |
| 7 | ROP versionado | — | 🚫 | **Decisión C-098: ROP vive en geobase.** V2-E8 dte-spp → N/A; consume vía API. |
| 8 | Cruce CONAPO localidad + Vínculos Padrón↔MIR | S | 🟡 | **Proveedor (geobase S3) ✅ + Consumidor dte-spp ✅ (PR #38, 2026-06-13).** Hechos: V2-A1 (atendida persistida en `poblaciones_programa` + `geobase:sync-atendida`), V2-B2 (accessors cobertura/brecha), V2-F3 (4º escalón en EmbudoPoblaciones). **Pendientes del sprint**: V2-F2 → 🚫 (CONAPO localidad vive en geobase), V2-E9 (`vw_alineacion_completa`) ❌ diferido a sprint XS propio. |
| 9 | Cruce PUBP federal | L | 🔒 | Requiere acuerdo SHCP/SFP federal (V2-F1). |

## GeoBase — Sprints V2

| # | Sprint | Tamaño | Estado | Notas |
|---|---|---|---|---|
| 1 | Folio evidencia + Validaciones P-01..P-08 | XS | ✅ | `PadronErrorCode`, P-01/02/05/07/08 cableados. (CLAUDE.md geobase "P-01..P-08 sprint 2026-06-07") |
| 2 | CURP regex (P-06) + RENAPO adapter | S | ❌ | P-06 latente; RENAPO sin fuente interinstitucional (F2-01, G2-04). |
| 3 | CONAPO localidad + Endpoint Atendida-Propósito | S | ✅ | **Cerrado 2026-06-13** (rama `feature/conapo-localidad`, suite 754/0). G2-02 (tabla `marginacion_indices_localidad` + comando `geobase:import-marginacion-localidad` + `MarginacionLocalidadService` B1, sin warning) + G2-03 (`GET /programs/{id}/atendida-proposito?ejercicio=YYYY`). Diseño/plan: `docs/plans/2026-06-13-conapo-localidad-{design,}.md` (geobase). |
| 4 | Decisiones arquitectónicas | — | ✅ | C-098 (ROP→geobase), C-022 (Atendida→persistida dte-spp), C-111 (conciliación→dte-spp). Resueltas 2026-06-07. |
| 5 | Cruce PUBP federal | L | 🔒 | G2-01, dependencia institucional. |

## Brechas cross-sistema acopladas (el "par")

| Par | dte-spp | geobase | Estado |
|---|---|---|---|
| **CONAPO** | V2-A1/B2/F3 (consumo) ✅ | G2-02/G2-03 (proveedor) ✅ | ✅ **COMPLETO** (geobase #30 + dte-spp #38, 2026-06-13). E9 diferido aparte. |
| **ROP** | consume API (V2-E8 🚫) | modelo `ReglasOperacion` (M, futuro) | ❌ pendiente (post-decisión C-098) |
| **PUBP federal** | V2-F1 | G2-01 | 🔒 bloqueado institucional |

## Decisiones cross-sistema resueltas (2026-06-07)

1. **C-098 ROP versionado → geobase** (fuente operativa del padrón). Destraba P-03/P-08.
2. **C-022 Población Atendida → agregado persistido en dte-spp** vía job/webhook M5.
3. **C-111 Conciliación → vista derivada en dte-spp** (parte del sprint M IAFF).

## Próximos candidatos (orden sugerido)

1. ~~Par CONAPO~~ ✅ COMPLETO (geobase #30 + dte-spp #38).
2. **Sprint M dte-spp**: IAFF persistido + Cierre fiscal 4 fases + Conciliación (cierra C-022 a nivel reporte/C-111). El más impactante para auditoría ASF/Cuenta Pública.
3. **Sprint M dte-spp**: Clave presupuestal canónica + Modalidades S/U/E/B + Estructura Programática 6 niveles.
4. **Sprint geobase**: ROP versionado (`ReglasOperacion`) — destraba P-03/P-08; + geobase Sprint 2 (CURP regex P-06 + RENAPO).
5. **Sprint XS dte-spp**: V2-E9 `vw_alineacion_completa` (vista materializada de alineación; diferido del par CONAPO).
6. **Deploy VPS acumulado** de todos los sprints V2 (geobase + dte-spp).

## Fuera de scope software (🚫 estructural)

- 21 conceptos del temario delegados correctamente entre sistemas (ver `informe_complementariedad_v2.md`).
- RENAPO/PUBP federal: dependencia de acuerdos interinstitucionales.
