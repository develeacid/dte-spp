# Seguimiento de Brechas V2 — dte-spp + GeoBase

> **Documento vivo.** Actualizar al cierre de cada sesión de trabajo: cambiar el estado de las brechas tocadas, mover sprints a ✅, y refrescar la fecha + el commit de referencia.
>
> Fuente original (fuera del repo): `/home/eleacid/code/laravel/matrices de indicadores/` (`informe_complementariedad_v2.md`, `reporte_brechas_dte_spp_v2.md`, `reporte_brechas_geobase_v2.md`). Este doc es el **resumen ejecutable de progreso**, no reemplaza los reportes completos.

**Última actualización:** 2026-06-13
**Estado global:** Informe V2 (2026-05-19) detectó **18 brechas conjuntas reales** del ecosistema en 147 conceptos del temario MIR. Sprints V2 1-4 dte-spp ✅ + geobase Sprint 1 ✅ + decisiones cross-sistema ✅. En curso: par CONAPO (geobase Sprint 3, lado proveedor).

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
| 8 | Cruce CONAPO localidad + Vínculos Padrón↔MIR | S | 🟡 | **Lado proveedor (geobase Sprint 3) EN CURSO 2026-06-13.** Lado consumo dte-spp diferido: V2-F2, V2-A1 (Atendida persistida), V2-B2 (brecha desempeño), V2-F3 (vistas `vw_atendida_proposito`/`vw_evaluacion_proposito_padron`), V2-E9 (`vw_alineacion_completa`). |
| 9 | Cruce PUBP federal | L | 🔒 | Requiere acuerdo SHCP/SFP federal (V2-F1). |

## GeoBase — Sprints V2

| # | Sprint | Tamaño | Estado | Notas |
|---|---|---|---|---|
| 1 | Folio evidencia + Validaciones P-01..P-08 | XS | ✅ | `PadronErrorCode`, P-01/02/05/07/08 cableados. (CLAUDE.md geobase "P-01..P-08 sprint 2026-06-07") |
| 2 | CURP regex (P-06) + RENAPO adapter | S | ❌ | P-06 latente; RENAPO sin fuente interinstitucional (F2-01, G2-04). |
| 3 | CONAPO localidad + Endpoint Atendida-Propósito | S | 🟡 | **EN CURSO 2026-06-13.** G2-02 (tabla `marginacion_indices_localidad` + importer + capa consulta B1, sin warning) + G2-03 (`GET /programs/{id}/atendida-proposito?ejercicio=YYYY`). Diseño: `docs/plans/2026-06-13-conapo-localidad-design.md` (geobase). |
| 4 | Decisiones arquitectónicas | — | ✅ | C-098 (ROP→geobase), C-022 (Atendida→persistida dte-spp), C-111 (conciliación→dte-spp). Resueltas 2026-06-07. |
| 5 | Cruce PUBP federal | L | 🔒 | G2-01, dependencia institucional. |

## Brechas cross-sistema acopladas (el "par")

| Par | dte-spp | geobase | Estado |
|---|---|---|---|
| **CONAPO** | V2-F2/F3/A1/B2 (consumo) | G2-02/G2-03 (proveedor) | 🟡 geobase en curso; dte-spp diferido |
| **ROP** | consume API (V2-E8 🚫) | modelo `ReglasOperacion` (M, futuro) | ❌ pendiente (post-decisión C-098) |
| **PUBP federal** | V2-F1 | G2-01 | 🔒 bloqueado institucional |

## Decisiones cross-sistema resueltas (2026-06-07)

1. **C-098 ROP versionado → geobase** (fuente operativa del padrón). Destraba P-03/P-08.
2. **C-022 Población Atendida → agregado persistido en dte-spp** vía job/webhook M5.
3. **C-111 Conciliación → vista derivada en dte-spp** (parte del sprint M IAFF).

## Próximos candidatos (orden sugerido)

1. **Terminar par CONAPO**: cerrar geobase Sprint 3 (en curso) → luego dte-spp Sprint 8 consumo (V2-F3/A1/B2/E9) en sesión siguiente.
2. **Deploy VPS acumulado** de los sprints V2 (#30-#35 dte-spp + geobase).
3. **Sprint M dte-spp**: IAFF persistido + Cierre fiscal 4 fases (cierra C-022/C-111).
4. **Sprint M dte-spp**: Clave presupuestal canónica.
5. **Sprint geobase**: ROP versionado (`ReglasOperacion`) — post-CONAPO.

## Fuera de scope software (🚫 estructural)

- 21 conceptos del temario delegados correctamente entre sistemas (ver `informe_complementariedad_v2.md`).
- RENAPO/PUBP federal: dependencia de acuerdos interinstitucionales.
