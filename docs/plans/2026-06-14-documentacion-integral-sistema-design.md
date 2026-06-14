# Diseño — Documentación integral del sistema (dte-spp + geobase)

> 2026-06-14. Brainstorming aprobado por el usuario. Ejecución vía Workflow multi-agente con checkpoints.

## Objetivo

Documentar, módulo por módulo del temario (M1-M10, fuente de verdad `matrices de indicadores/`), **dónde y cómo** el sistema cumple cada requerimiento: URL, vista/componente, botones, modales, pantallas, permisos. Cubre **ambos** sistemas (dte-spp + geobase). Producir manuales por rol, registrar brechas detectadas, y actualizar arquitectura/glosario/guía/README de ambos repos.

## Estructura de salida

Central en **dte-spp** (los módulos son cross-sistema):
```
dte-spp/docs/sistema/
  README.md                  índice + matriz de cobertura M1-M10 × sistema
  modulos/M01..M10-*.md      tabla: Requerimiento | ¿Cumple? | URL | Vista | Botones/Modales | Sistema | Notas/Brecha
  manuales/                  por rol (refrescados + completados)
  brechas/README.md          registro consolidado (severidad, módulo, evidencia, verificado)
  fuente-de-verdad/          curada (módulos temario, glosario, diagramas, integración)
  _mapa-sistema.md           (interno) inventario de rutas/vistas/componentes — insumo Fase 2
```

In-place en cada repo: `docs/architecture/*`, `README.md`, glosario, guía contribución/setup.

## Módulos (spine)

M01 Fundamentos PbR · M02 Marco Lógico (MML) · M03 Arquitectura MIR · M04 Resumen Narrativo · M05 Indicadores · M06 MV y Supuestos · M07 Padrón (↔geobase) · M08 Seguimiento/IAFF/Cierre · M09 Presupuestación/Clave/Alineación · M10 Evaluación.

## Roles (manuales)

- dte-spp: admin, planeador, operador, analista_financiero, analista_juridico, responsable_datos_abiertos.
- geobase: sysadmin, admin_dependencia, analista_global, enlace_mir, operador.
- Base: 00-acceso-general.

## Ejecución (Workflow multi-agente, con checkpoints)

- **Fase 1 — Mapeo del sistema** (paralelo, read-only): inventario de rutas/vistas/Livewire/controllers/botones/modales/permisos por área (dte-spp por dominio + geobase). Salida → `_mapa-sistema.md`. Checkpoint.
- **Fase 2 — Docs de módulo M1-M10** (1 agente/módulo): lee temario + mapa, escribe `Mxx.md` con tabla de cumplimiento + brechas candidatas. Checkpoint.
- **Fase 3 — Verificación adversarial de brechas**: cada brecha candidata validada contra el código real (evita falsos positivos).
- **Fase 4 — Manuales por rol + Arquitectura/glosario/README ambos repos** (paralelo).
- **Fase 5 — Síntesis**: README índice + matriz de cobertura + `brechas/README.md`.

## Decisiones

- Matriz híbrida (temario × sistema). Manuales refrescando los `docs/guias/` existentes. Fuente de verdad curada (no verbatim). Brechas verificadas adversarialmente antes de registrar. Trabajo en rama `docs/documentacion-integral-sistema` (ambos repos).
