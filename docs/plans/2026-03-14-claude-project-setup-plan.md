# Plan: Configuración de Claude.ai Project para DTE-SPP

**Fecha:** 2026-03-14
**Objetivo:** Crear un proyecto en claude.ai optimizado para onboarding de equipo nuevo
**Contexto:** El proyecto ya está avanzado (Sprint 18). Se necesita que miembros nuevos puedan consultar, diseñar y planear usando Claude con contexto completo del proyecto.

---

## Fase 1: Crear CLAUDE.md del proyecto (prerequisito)

**Por qué:** Claude.ai Projects usa "Custom Instructions" — el CLAUDE.md es la base perfecta para esto. Además beneficia a Claude Code.

### Tarea 1.1: Crear `/CLAUDE.md` en la raíz del proyecto

Contenido recomendado (sintetizar de docs existentes):

```markdown
# DTE-SPP 2026

## Stack
- Laravel 12, PHP 8.2+, PostgreSQL (pgvector/pgvector:pg16)
- Jetstream + Livewire 3 + Teams
- barryvdh/laravel-dompdf, maatwebsite/excel, symfony/expression-language
- Tests: `./vendor/bin/sail artisan test`

## Arquitectura
- Estructura híbrida Laravel + dominio: Cascade/, Mml/, Tracking/, Evaluation/
- Frontend: Atomic Design con Slots (Layout → Page → Module)
- Rutas: `routes/web/` separado por dominio (cascade.php, mml.php, etc.)

## Convenciones
- Commits: `tipo(scope): descripción` + `Resolves DTE-<n>` para Linear
- Sin modales para flujos principales (crear/editar = páginas completas)
- Componentes Blade: x-page.container, x-page.header, x-forms.section, etc.
- Tests: baseline 426 passed, 7 skipped

## Dominio del Negocio
- Sistema de Planeación para Programas Presupuestales (PbR-SED)
- MIR (Matriz de Indicadores para Resultados) es el módulo central
- Wizard de planeación MML para construir MIR paso a paso
- Reportes: Sábana de Captura, Concentrado, MIR Aprobada
- Alineación: PND → PED → ODS → Programas Derivados

## Producción
- VPS Hostinger, Nginx reverse proxy, Docker
- Dominio: eleaciddev.cloud
- Rama de deploy: `desarrollo`
```

---

## Fase 2: Preparar documentos para subir a Claude.ai Project

### Documentos CRÍTICOS (subir como Knowledge)

| # | Archivo | Por qué |
|---|---------|---------|
| 1 | `/CLAUDE.md` (crear en Fase 1) | Contexto técnico base |
| 2 | `docs/arquitectura.md` | Visión general de arquitectura |
| 3 | `docs/diccionario-datos.md` | Modelo de datos y relaciones |
| 4 | `docs/mapa-rutas-permisos.md` | Rutas, permisos y roles |
| 5 | `docs/architecture/wizard-planeacion-mml.md` | Módulo MML (core del sistema) |
| 6 | `docs/architecture/future-integration-presupuesto-padron.md` | Roadmap de integraciones |
| 7 | `docs/deployment/production-context.md` | Contexto de producción |
| 8 | `docs/schema/indicadores.md` | Schema de indicadores |
| 9 | `docs/schema/mir.md` | Schema de MIR |
| 10 | `docs/schema/programas-derivados.md` | Schema de programas |

### Documentos ÚTILES (subir según espacio)

| # | Archivo | Por qué |
|---|---------|---------|
| 11 | `docs/manual-usuario.md` | Contexto funcional |
| 12 | `docs/seguridad.md` | Políticas de seguridad |
| 13 | `docs/architecture/risk-analysis.md` | Riesgos conocidos |
| 14 | `docs/schema/teams.md` | Estructura de equipos |
| 15 | `docs/schema/ods.md` | Catálogo ODS |
| 16 | `docs/reports/sprint-08-retrospective.md` | Último retro (contexto de proceso) |

### NO subir (ruido o demasiado granulares)

- `docs/plans/*` — Son planes de implementación por sprint, demasiado detallados
- `docs/devlog/*` — Logs de desarrollo internos
- `docs/reports/stubs.md` — Datos temporales de QA
- `docs/data/*` — Datos de seed/test
- Archivos de memory de Claude Code — Son para la CLI, no para Projects

---

## Fase 3: Configurar el Proyecto en claude.ai

### Tarea 3.1: Crear el proyecto
- Nombre: **DTE-SPP 2026 — Sistema de Planeación PbR-SED**
- Descripción: Sistema de planeación presupuestal con MIR, indicadores y reportes PbR-SED

### Tarea 3.2: Custom Instructions
Copiar el contenido de CLAUDE.md + agregar instrucciones de equipo:

```
Eres un asistente para el equipo de desarrollo del sistema DTE-SPP 2026.

CONTEXTO DEL EQUIPO:
- Líder técnico: [nombre] — arquitectura y decisiones técnicas
- Desarrolladores: [nombres] — implementación de features
- Revisor: [nombre] — QA y code review

REGLAS:
- Responde en español
- Usa las convenciones del proyecto (ver Knowledge)
- Para código, sigue la arquitectura Blade + Livewire 3 del proyecto
- Para consultas de base de datos, referencia el diccionario de datos
- Para rutas y permisos, referencia el mapa de rutas
- Nunca sugieras tecnologías fuera del stack (no React, no Vue, no Inertia)

DOMINIO:
- PbR = Presupuesto basado en Resultados
- SED = Sistema de Evaluación del Desempeño
- MIR = Matriz de Indicadores para Resultados
- MML = Metodología del Marco Lógico
```

### Tarea 3.3: Subir Knowledge files
Subir los 10 documentos críticos de la Fase 2, en el orden listado.

### Tarea 3.4: Test de validación
Hacer estas preguntas de prueba al proyecto:
1. "¿Qué stack tecnológico usa el proyecto?"
2. "¿Cómo funciona el wizard de MML?"
3. "¿Cuáles son las tablas principales del módulo de indicadores?"
4. "Necesito agregar un nuevo reporte PbR-SED, ¿qué pasos debo seguir?"

Si las respuestas son precisas y usan el contexto de los docs → el proyecto está bien configurado.

---

## Fase 4: Documentar para el equipo

### Tarea 4.1: Agregar sección en el README o docs/
Crear `docs/team/claude-project-guide.md` con:
- Link al proyecto de Claude.ai
- Qué tipo de consultas hacer ahí vs Claude Code
- Cómo actualizar los Knowledge files cuando cambien los docs

---

## Notas importantes

1. **Límite de Knowledge:** Claude.ai Projects tiene límite de ~200k tokens de contexto. Los 10 docs críticos deben caber holgadamente.
2. **Mantenimiento:** Cuando actualices docs del proyecto, re-subir al Project. Considerar un recordatorio cada 2 sprints.
3. **Complementario a Claude Code:** El Project es para consultar/diseñar/planear. Claude Code sigue siendo la herramienta para implementar.
4. **Permisos:** Claude.ai Projects con plan Team permite compartir proyectos con miembros.
