# Guía: Proyecto Claude.ai para DTE-SPP

## Acceso

- **URL del proyecto:** [Agregar link al proyecto de Claude.ai aquí]
- **Requiere:** Cuenta de Claude con acceso al Team/Organization

## Cuándo usar Claude.ai Project vs Claude Code

| Tarea | Herramienta |
|-------|-------------|
| Consultar arquitectura, dominio o diseño | Claude.ai Project |
| Planear features nuevas | Claude.ai Project |
| Entender el modelo de datos | Claude.ai Project |
| Revisar conceptos de PbR-SED | Claude.ai Project |
| Implementar código | Claude Code |
| Ejecutar tests | Claude Code |
| Debugging | Claude Code |
| Refactoring | Claude Code |
| Crear PRs y commits | Claude Code |

**Regla general:** Si necesitas *escribir o modificar código*, usa Claude Code. Si necesitas *consultar, diseñar o planear*, usa el Proyecto en claude.ai.

## Knowledge Files incluidos

El proyecto tiene los siguientes documentos como contexto:

### Críticos (siempre incluidos)
1. `CLAUDE.md` — Contexto técnico base
2. `docs/arquitectura.md` — Visión general de arquitectura
3. `docs/diccionario-datos.md` — Modelo de datos y relaciones
4. `docs/mapa-rutas-permisos.md` — Rutas, permisos y roles
5. `docs/architecture/wizard-planeacion-mml.md` — Módulo MML (core del sistema)
6. `docs/architecture/future-integration-presupuesto-padron.md` — Roadmap de integraciones
7. `docs/schema/indicadores.md` — Schema de indicadores
8. `docs/schema/mir.md` — Schema de MIR
9. `docs/schema/programas-derivados.md` — Schema de programas

### Útiles (incluidos según espacio)
11. `docs/manual-usuario.md` — Contexto funcional
12. `docs/seguridad.md` — Políticas de seguridad
13. `docs/architecture/risk-analysis.md` — Riesgos conocidos
14. `docs/schema/teams.md` — Estructura de equipos
15. `docs/schema/ods.md` — Catálogo ODS
16. `docs/reports/sprint-08-retrospective.md` — Último retro

## Mantenimiento

Cuando se actualice un documento del proyecto:

1. Abrir el proyecto en claude.ai
2. Ir a la sección "Knowledge"
3. Eliminar el archivo antiguo y subir la versión actualizada
4. Verificar con una pregunta de prueba que el contexto se actualizó

**Frecuencia recomendada:** Revisar y actualizar cada 2 sprints.

## Preguntas de prueba

Para verificar que el proyecto está bien configurado, probar con:

1. "¿Qué stack tecnológico usa el proyecto?"
2. "¿Cómo funciona el wizard de MML?"
3. "¿Cuáles son las tablas principales del módulo de indicadores?"
4. "Necesito agregar un nuevo reporte PbR-SED, ¿qué pasos debo seguir?"
