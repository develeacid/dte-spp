# Ciclo de Vida de un Programa — Involucrados por Fase

> Generado: 2026-03-15

## Diagrama de Secuencia por Rol

```
Tiempo ──────────────────────────────────────────────────────────►

PLANEADOR    ████████████████████░░░░░░░░░░░░████████░░░░░░████
             Fase 1: MML Wizard                Fase 4: Revisar/Aprobar
             (E1-E7 + MIR)                     avances

A_FINANCIERO ░░░░░░░░░░░░░░░░░░██████████░░░░░░░░░░░░██████████
                               Fase 2: Partidas  Fase 5: Avance
                               + Calendarizar    financiero

A_JURIDICO   ░░░░░░░░░░░░░░░░░░░░██████████░░░░░░░░░░░░░░░░░░░
                                 Fase 3: Sustento
                                 legal + Validar

OPERADOR     ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████████████████
                                            Fase 4: Capturar
                                            avances + evidencias

ADMIN        ████████████████████████████████████████████████████
             Supervisión global + desbloqueos + gestión usuarios
```

## Fase 1: Planeacion MML (Wizard 7 etapas) — planeador

| Etapa | Que se crea | Permiso requerido |
|-------|-------------|-------------------|
| Crear programa | `ProgramaPresupuestario` (BORRADOR) | `crear_programa` |
| E1: Definicion del problema | `Arbol` + `ArbolNodo` (problema_central) | `editar_mir` |
| E2: Arbol del problema | `ArbolNodo` (causas/consecuencias) | `editar_mir` |
| E3: Arbol de objetivos | `ArbolNodo` (fin/proposito/comp/act) | `editar_mir` |
| E4: Seleccion de alternativas | `Alternativa` | `editar_mir` |
| E5: Embudo de poblaciones | `PoblacionPrograma` | `editar_mir` |
| E6: Alineacion estrategica | Vinculos PED/PND/ODS en `MirNivel` | `editar_mir` |
| E7: Editor de MIR | `MirNivel` (4 niveles) + `Indicador` + `MetaPeriodo` | `editar_mir` |

Actor unico: Planeador de la UR coordinadora. En programas transversales, el
planeador de la UR coadyuvante edita componentes/actividades asignados a su equipo.

## Fase 2: Alineacion Presupuestal — analista_financiero

| Accion | Que se crea | Permiso requerido |
|--------|-------------|-------------------|
| Crear partidas | `PartidaPresupuestal` (COG 1000-6000) | `gestionar_presupuesto` |
| Calendarizar gasto | `MetaGastoTrimestral` (dist. trimestral) | `gestionar_presupuesto` |
| Importar partidas | Carga masiva de partidas | `gestionar_presupuesto` |

Actor unico: Analista financiero de la UR. Planeador solo ve (ver_datos_financieros).

## Fase 3: Validacion Juridica — analista_juridico

| Accion | Que se crea | Permiso requerido |
|--------|-------------|-------------------|
| Registrar sustento legal | `SustentoLegalPrograma` (facultad, mandato, ROP) | `gestionar_sustento_legal` |
| Subir documentos normativos | `DocumentoNormativo` (PDFs de ROPs) | `gestionar_reglas_operacion` |
| Validar checklist | `ValidacionJuridicaPrograma` | `validar_sustento_legal` |

Actor unico: Analista juridico. Planeador y a_financiero solo ven (ver_sustento_legal).

## Fase 4: Seguimiento de Indicadores — operador + planeador

| Accion | Actor | Permiso requerido |
|--------|-------|-------------------|
| Capturar avance (variables + resultado) | Operador | `capturar_avance` |
| Subir evidencias | Operador | `capturar_avance` |
| Revisar avances (sabana/concentrado) | Planeador | `revisar_avance` |
| Aprobar o devolver con observaciones | Planeador | `aprobar_avance` |
| Solicitar desbloqueo | Operador | `capturar_avance` |
| Autorizar desbloqueo | Admin | `administrar_usuarios` |

Flujo de estados: EN_CAPTURA -> EN_REVISION -> APROBADO | OBSERVADO -> EN_CAPTURA

## Fase 5: Seguimiento Financiero — analista_financiero

| Accion | Permiso requerido |
|--------|-------------------|
| Capturar avance financiero trimestral | `capturar_avance_financiero` |
| Exportar cuenta publica | `exportar_cuenta_publica` |

Cascada de validacion: pagado <= devengado <= comprometido

## Fase Transversal: Reportes y Evaluacion

| Reporte | Quien lo ve | Permiso |
|---------|-------------|---------|
| Sabana de captura | planeador, operador, admin | `ver_sabana_captura` |
| Concentrado de captura | planeador, operador, admin | `ver_concentrado_captura` |
| MIR Aprobada / Evaluacion | planeador, admin | `exportar_reportes` |
| Cuenta publica | planeador, a_financiero, admin | `exportar_cuenta_publica` |
| Panel juridico | planeador, a_financiero, a_juridico, admin | `ver_sustento_legal` |

## Brecha en Seeders Actuales

Para un dataset realista, cada programa necesita datos de las 5 fases:

| Fase | QaTestingSeeder | PresupuestoTestSeeder | JuridicoTestSeeder |
|------|----|----|----|
| 1. MML/MIR | 4 programas completos | - | - |
| 2. Presupuesto | - | 3 programas (distintos) | - |
| 3. Juridico | - | - | 5 programas (distintos) |
| 4. Tracking | Avances para 4 programas | - | - |
| 5. Avance financiero | - | 3 programas | - |

Problema central: Los 3 seeders operan de forma independiente en lugar de construir
capas sobre los mismos 4 programas base (ISM-001, PEC-002, FSP-003, DDT-004).
Un dataset realista deberia tener los 4 programas con datos de todas las fases,
cada uno operado por el rol correcto.
