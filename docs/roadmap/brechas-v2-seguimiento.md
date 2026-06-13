# Seguimiento de Brechas V2 — dte-spp + GeoBase

> **Documento vivo.** Actualizar al cierre de cada sesión de trabajo: cambiar el estado de las brechas tocadas, mover sprints a ✅, y refrescar la fecha + el commit de referencia.
>
> Fuente original (fuera del repo): `/home/eleacid/code/laravel/matrices de indicadores/` (`informe_complementariedad_v2.md`, `reporte_brechas_dte_spp_v2.md`, `reporte_brechas_geobase_v2.md`). Este doc es el **resumen ejecutable de progreso**, no reemplaza los reportes completos.

**Última actualización:** 2026-06-13 (Sprint M IAFF+Cierre fiscal)
**Estado global:** Informe V2 (2026-05-19) detectó **18 brechas conjuntas reales** del ecosistema en 147 conceptos del temario MIR. Sprints V2 1-4 dte-spp ✅ + geobase S1 ✅ + decisiones cross-sistema ✅ + **par CONAPO COMPLETO** (#30/#38) + **Sprint M IAFF+Cierre fiscal** (#40: V2-D1/D4/D5 ✅; D6/D7 diferidos) + **Clave presupuestal core** (#42: V2-E1/E2/E3 ✅; E4/E5/E6 diferidos). Siguiente foco: diferidos D6/D7/E9/E4-E6, o deploy VPS acumulado.

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
| 5 | IAFF persistido + Cierre fiscal 4 fases + Conciliación | M | 🟡 | **Core ✅ (PR #40, 2026-06-13)**: V2-D1 (tabla `iaff` snapshot+hash+firma + hook export + UI Historial), V2-D5 (tabla `cierres_fiscales` por ejercicio + máquina 4 fases + gate IAFF-Q4 + guard CERRADO + UI panel), V2-D4 (`IaffConsolidacionService` §4). **V2-D7 POA ✅ (PR #49)**: vista `vw_poa` + visor `/presupuesto/poa`. **V2-D6 conciliación físico-financiera ✅** (par: geobase #31 endpoint `montos-entregados` + dte-spp #53 `GeoBaseClient::getMontosEntregados` + vista `/presupuesto/conciliacion/{programa}` tesorería⋈padrón en vivo). **Diferido**: e.firma certificada real. |
| 6 | Clave presupuestal canónica + Estructura Programática | M | 🟡 | **Core ✅ (PR #42, 2026-06-13)**: V2-E1 (catálogo `clasificacion_funcional` CONAC 4/28/111 seedeado + 10 campos discretos admin/programáticos en `programa_presupuestarios`), V2-E2 (accessor `clave_presupuestal_canonica` SEFIP 17 díg + editor `/{programa}/clave-presupuestal` con dropdowns CONAC encadenados), V2-E3 **reinterpretado** (jerarquía CONAC vía `ClavePresupuestalService`; **modalidades S/U/E/B descartadas — no aplican a Oaxaca**). **V2-E4 + V2-E5 ✅ (PR #51)**: vista `vw_presupuesto_aprobado` (programa×capítulo) + tabla `modificaciones_presupuestales` (ampliación/reducción) con `monto_modificado` derivado + UI `/presupuesto/partidas/{partida}/modificaciones`. **V2-E6 (cap.4000↔ROP) 🚫**: cross-sistema bloqueado por ROP-en-geobase (C-098). |
| 7 | ROP versionado | — | 🚫 | **Decisión C-098: ROP vive en geobase.** V2-E8 dte-spp → N/A; consume vía API. |
| 8 | Cruce CONAPO localidad + Vínculos Padrón↔MIR | S | 🟡 | **Proveedor (geobase S3) ✅ + Consumidor dte-spp ✅ (PR #38, 2026-06-13).** Hechos: V2-A1 (atendida persistida en `poblaciones_programa` + `geobase:sync-atendida`), V2-B2 (accessors cobertura/brecha), V2-F3 (4º escalón en EmbudoPoblaciones). **Pendientes del sprint**: V2-F2 → 🚫 (CONAPO localidad vive en geobase), V2-E9 (`vw_alineacion_completa`) ✅ **(PR #45)** — vista 1-fila-por-Pp que resuelve PED+PD+PND+ODS. **DS-05 cableado ✅ (PR #47)**: el publisher consume la vista y llena `ods_metas`/`pnd_objetivo` (antes NULL). Post-deploy: `transparencia:sync-public DS-05`. |
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
| **Conciliación (V2-D6)** | consumidor `getMontosEntregados` + vista ✅ (#53) | endpoint `montos-entregados` ✅ (#31) | ✅ **COMPLETO** (2026-06-13). |
| **ROP** | consume API (V2-E8 🚫) | modelo `ReglasOperacion` (M, futuro) | ❌ pendiente (post-decisión C-098) |
| **PUBP federal** | V2-F1 | G2-01 | 🔒 bloqueado institucional |

## Decisiones cross-sistema resueltas (2026-06-07)

1. **C-098 ROP versionado → geobase** (fuente operativa del padrón). Destraba P-03/P-08.
2. **C-022 Población Atendida → agregado persistido en dte-spp** vía job/webhook M5.
3. **C-111 Conciliación → vista derivada en dte-spp** (parte del sprint M IAFF).

## Próximos candidatos (orden sugerido)

1. ~~Par CONAPO~~ ✅ COMPLETO (#30 + #38).
2. ~~Sprint M IAFF + Cierre fiscal (core)~~ ✅ (#40). Quedan sus diferidos (abajo).
3. ~~Sprint M dte-spp: Clave presupuestal canónica~~ ✅ **core** (#42) + ~~V2-E4/E5~~ ✅ (#51). V2-E6 (cap.4000↔ROP) 🚫 bloqueado (ROP en geobase).
4. ~~Par conciliación (V2-D6)~~ ✅ COMPLETO (geobase #31 + dte-spp #53). Conciliación app-level (tesorería local ⋈ montos entregados geobase en vivo), no vista SQL (cross-sistema).
5. **Sprint geobase**: ROP versionado (`ReglasOperacion`) — destraba P-03/P-08; + geobase Sprint 2 (CURP regex P-06 + RENAPO).
6. **Sprints XS dte-spp**: ~~V2-D7 POA~~ ✅ (#49), ~~V2-E9 + DS-05~~ ✅ (#45/#47), ~~V2-E4/E5~~ ✅ (#51), ~~V2-D6 conciliación~~ ✅ (#31/#53). **Roadmap V2 dte-spp ejecutable: CERRADO.** Queda solo deploy VPS acumulado + diferidos bloqueados (E6/ROP/RENAPO/PUBP, e.firma).
7. **Deploy VPS acumulado** de todos los sprints V2 (geobase + dte-spp).

## Fuera de scope software (🚫 estructural)

- 21 conceptos del temario delegados correctamente entre sistemas (ver `informe_complementariedad_v2.md`).
- RENAPO/PUBP federal: dependencia de acuerdos interinstitucionales.
