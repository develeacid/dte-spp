# Analisis de Gaps: Tablas de BD vs Cobertura de Seeders

> Generado: 2026-03-15

## Resumen Ejecutivo

- **70 tablas** en el sistema (79 migraciones)
- **28 tablas** con datos de seeders
- **~20 tablas** que son system-generated (OK sin seedear)
- **~12 tablas** transaccionales sin datos que DEBERIAN tener para QA realista
- **2 seeders de permisos** fuera de la cadena de DatabaseSeeder

## Tablas NO Seedeadas que DEBERIAN Tener Datos para QA

### Prioridad CRITICA — Afectan directamente la calidad del testing

| Tabla | Modelo | Que falta | Por que importa |
|-------|--------|-----------|-----------------|
| `indicador_variables` | IndicadorVariable | Formula variables (A, B, C) | Sin variables, CapturaAvance no puede calcular resultado via formula |
| `medios_verificacion` | MedioVerificacion | Fuentes de verificacion | MIR incompleta sin medios; reportes MIR Aprobada los requiere |
| `cremaa_validaciones` | CremaaValidacion | Validacion CREMAA por indicador | Calidad del indicador no verificable |
| `estado_validacion_programa` | EstadoValidacionPrograma | Estado tripartita consolidado | Panel presupuestal muestra badges vacios |
| `avance_evidencias` | AvanceEvidencia | Archivos de soporte | Flujo de auditoria sin evidencia documental |
| `indicador_anexo_transversal` | Pivot | Vinculos indicador↔anexo transversal | PanelTransversal sin datos, reportes de genero/NNA vacios |

### Prioridad ALTA — Necesarios para pruebas de modulos nuevos

| Tabla | Modelo | Que falta | Por que importa |
|-------|--------|-----------|-----------------|
| `poblaciones_programa` | PoblacionPrograma | Embudo de poblaciones (E5) | Wizard incompleto; dato necesario para ficha tecnica |
| `documentos_normativos` | DocumentoNormativo | PDFs de ROPs | Modulo juridico sin documentos adjuntos |
| `desbloqueos` | Desbloqueo | Solicitudes de desbloqueo | Flujo excepcional no probado |
| `notifications` | Notification | Notificaciones in-app | Campana de notificaciones vacia en QA |

### Prioridad MEDIA — Mejorarian la completitud del wizard MML

| Tabla | Modelo | Que falta | Por que importa |
|-------|--------|-----------|-----------------|
| `arboles` | Arbol | Arboles de problema/objetivos (E2-E3) | Etapas 1-3 del wizard sin datos |
| `arbol_nodos` | ArbolNodo | Nodos del arbol (causas, consecuencias) | Etapas 1-3 del wizard sin datos |
| `alternativas` | Alternativa | Alternativas seleccionadas (E4) | Etapa 4 del wizard sin datos |
| `alternativa_nodo` | Pivot | Vinculos alternativa↔nodo | Etapa 4 del wizard sin datos |
| `mir_versiones` | MirVersion | Snapshots de la MIR | Sin historial de versiones |

### OK Sin Seedear (system-generated en runtime)

| Tabla | Razon |
|-------|-------|
| cache, cache_locks | Cache de framework |
| sessions | Sesiones de usuario |
| jobs, job_batches, failed_jobs | Queue system |
| password_reset_tokens | Recuperacion de password |
| personal_access_tokens | API tokens |
| team_invitations | Flujo de invitacion |
| llm_logs, llm_budgets | Logs de IA |
| activity_log | Spatie activity log |
| importacion_reportes | Importacion de MIR |
| evaluaciones_programa | Calculado por EvaluacionService |

## Cadena de DatabaseSeeder — Lo que se Ejecuta

```
DatabaseSeeder::run()
│
├── 1. RolesAndPermissionsSeeder     ← Permisos core (11 permisos, 3 roles)
├── 2. DesarrolloSeeder              ← Admin + 4 URs + 8 usuarios + 1 programa transversal
├── 3. OdsSeeder                     ← Catalogo ODS (17 objetivos + metas)
├── 4. PndSeeder                     ← Catalogo PND
├── 5. PedSeeder                     ← Catalogo PED Oaxaca
├── 6. ProgramasDerivadosSeeder      ← 3 programas derivados con objetivos
├── 7. AlineacionesSeeder            ← Alineaciones PED↔PND, PND↔ODS, Linea↔Programa
├── 8. AnexosTransversalesSeeder     ← 4 anexos: Genero, NNA, Cambio Climatico, Anticorrupcion
└── 9. QaTestingSeeder               ← 4 programas con MIR + indicadores + avances

    ★ NO SE EJECUTAN AUTOMATICAMENTE:
    ├── PresupuestoPermissionsSeeder  ← 4 permisos financieros + rol analista_financiero
    ├── JuridicoPermissionsSeeder     ← 4 permisos juridicos + rol analista_juridico
    ├── PresupuestoTestSeeder         ← Partidas + metas gasto + avances financieros
    └── JuridicoTestSeeder            ← Sustentos legales + validaciones
```

## Impacto de los Gaps por Modulo

### Modulo MML (Wizard de Planeacion)

| Etapa | Tabla(s) | Seedeada | Impacto |
|-------|---------|:---:|--------|
| E1: Definicion problema | arboles, arbol_nodos | No | Solo E7 tiene datos |
| E2: Arbol problema | arbol_nodos | No | Sin causas/consecuencias |
| E3: Arbol objetivos | arbol_nodos | No | Sin objetivos intermedios |
| E4: Alternativas | alternativas, alternativa_nodo | No | Sin analisis de alternativas |
| E5: Poblaciones | poblaciones_programa | No | Sin embudo poblacional |
| E6: Alineacion | mir_niveles (ped_*_id) | No | Niveles MIR sin alineacion PED |
| E7: Editor MIR | mir_niveles, indicadores | Si | Pero sin formula, variables, medios, CREMAA |

**Resultado**: El seeder crea MIR "esqueleticas" saltando E1-E6 y dejando E7 incompleta.

### Modulo Tracking (Seguimiento)

| Dato | Tabla | Seedeado | Impacto |
|------|-------|:---:|--------|
| Avances con resultado | avances | Si | OK pero sin pasar por formula |
| Variables capturadas | avance_variables | No* | *QaTestingSeeder no las crea |
| Evidencias | avance_evidencias | No | Sin documentos de soporte |
| Desbloqueos | desbloqueos | No | Flujo excepcional no probado |
| Notificaciones | notifications | No | Campana vacia |
| Historial transiciones | avances.historial_observaciones | Parcial | Sin transiciones realistas |

### Modulo Presupuesto

| Dato | Tabla | Seedeado | Impacto |
|------|-------|:---:|--------|
| Partidas | partidas_presupuestales | Si (opcional) | Pero en programas DISTINTOS a los 4 de QA |
| Metas gasto | metas_gasto_trimestral | Si (opcional) | OK |
| Avance financiero | avances_financieros | Si (opcional) | OK pero montos aleatorios (rand) |
| Estado validacion | estado_validacion_programa | No | Badge financiero no se calcula |
| Semaforo financiero | (calculado) | No | No se pre-calcula |

### Modulo Juridico

| Dato | Tabla | Seedeado | Impacto |
|------|-------|:---:|--------|
| Sustentos legales | sustento_legal_programa | Si (opcional) | Pero en programas del primer team, no los 4 de QA |
| Validacion juridica | validacion_juridica_programa | Si (opcional) | OK, 4 escenarios |
| Documentos normativos | documentos_normativos | No | Sin PDFs de ROPs |
| Estado validacion | estado_validacion_programa | No | Badge juridico no se calcula |

### Panel de Evaluacion

| Dato | Tabla | Seedeado | Impacto |
|------|-------|:---:|--------|
| Evaluaciones programa | evaluaciones_programa | No | Panel de evaluacion vacio |
| Indicadores↔Anexos | indicador_anexo_transversal | No | PanelTransversal sin datos |

## Los 5 Problemas Mas Criticos

1. **PresupuestoPermissionsSeeder y JuridicoPermissionsSeeder NO estan en DatabaseSeeder**
   - `migrate:fresh --seed` no crea los roles analista_financiero ni analista_juridico
   - Los seeders de presupuesto/juridico fallan si los permisos no existen

2. **PresupuestoTestSeeder y JuridicoTestSeeder trabajan sobre programas DISTINTOS**
   - QaTestingSeeder crea ISM-001, PEC-002, FSP-003, DDT-004
   - PresupuestoTestSeeder usa `ProgramaPresupuestario::take(3)` (puede tomar los de Desarrollo)
   - JuridicoTestSeeder usa programas del `User::first()->currentTeam`
   - Resultado: los 4 programas principales de QA no tienen datos financieros ni juridicos

3. **Sin usuarios analista_financiero ni analista_juridico**
   - Ningun seeder crea usuarios con estos roles
   - PresupuestoTestSeeder y JuridicoTestSeeder usan `User::first()` como autor

4. **Indicadores sin formula, variables ni medios de verificacion**
   - CapturaAvance requiere formula_texto + IndicadorVariable para funcionar
   - La Sabana de Captura y reportes necesitan MedioVerificacion
   - Sin CREMAA, la calidad del indicador no esta validada

5. **estado_validacion_programa nunca se calcula**
   - El EstadoConsolidadoService existe pero ningun seeder lo invoca
   - Los badges tripartita (planeacion/juridico/financiero) aparecen vacios
