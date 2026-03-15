# Ciclo de Vida Completo de una MIR — Prerequisitos y Bloqueos

> Generado: 2026-03-15
> Base para diseño de seeders realistas

## Diagrama General

```
FASE 0          FASE 1              FASE 2          FASE 3          FASE 4              FASE 5
Prerequisitos   Planeacion MML      Presupuesto     Juridico        Seguimiento         Evaluacion
─────────────   ──────────────      ───────────     ────────        ───────────         ──────────
Catalogos       E1→E2→E3→E4→E5→E6→E7  Partidas     Sustento       Captura→Revision    Reportes
Roles/Permisos  Wizard completo     Calendarizar    Validar        →Aprobacion         Cuenta Publica
URs/Usuarios    MIR + Indicadores   Avance fin.     ROPs           Desbloqueos         Datos Abiertos

PLANEADOR       PLANEADOR           A.FINANCIERO    A.JURIDICO     OPERADOR+PLANEADOR  PLANEADOR+ADMIN
```

---

## FASE 0: Prerequisitos del Sistema

Antes de crear cualquier programa, el sistema necesita:

### 0.1 Roles y Permisos
| Seeder | Que crea | Bloquea si falta |
|--------|---------|-----------------|
| RolesAndPermissionsSeeder | 3 roles (admin, planeador, operador) + 11 permisos core | Todo el sistema |
| PresupuestoPermissionsSeeder | Rol analista_financiero + 4 permisos financieros | Modulo presupuesto |
| JuridicoPermissionsSeeder | Rol analista_juridico + 4 permisos juridicos | Modulo juridico |

**Orden obligatorio**: Roles → Permisos financieros → Permisos juridicos

### 0.2 Catalogos de Referencia
| Seeder | Tabla(s) | Bloquea si falta |
|--------|---------|-----------------|
| OdsSeeder | ods_objetivos, ods_metas | Alineacion ODS en E6 |
| PndSeeder | pnd_ejes, pnd_objetivos, pnd_estrategias | Alineacion PND en E6 |
| PedSeeder | ped_planes, ped_ejes, ped_temas, ped_objetivos_estrategicos, ped_estrategias, ped_lineas_accion | Alineacion PED en E6 |
| ProgramasDerivadosSeeder | programas_derivados, programas_derivados_objetivos | Vinculacion a programas derivados |
| AlineacionesSeeder | alineacion_ped_pnd, alineacion_pnd_ods, alineacion_linea_programa | Cadena estrategica PED↔PND↔ODS |
| UnidadesMedidaSeeder | catalogo_unidades_medida | Campo unidad_medida_id en indicadores |
| AnexosTransversalesSeeder | anexos_transversales | Genero, NNA, Cambio Climatico, Anticorrupcion |
| CatalogoOrdenamientosSeeder | catalogo_ordenamientos | Sustento legal (LOEPO, LEPRH, etc.) |

### 0.3 URs y Usuarios
| Seeder | Que crea | Roles necesarios |
|--------|---------|-----------------|
| DesarrolloSeeder | 4 URs (SE, SS, SEG, SECTUR) + admin + 8 usuarios (planeador+operador por UR) | admin, planeador, operador |
| QaTestingSeeder | 8 usuarios QA con emails @gmail.com | admin, planeador, operador |
| **(FALTA)** | Usuarios analista_financiero y analista_juridico por UR | analista_financiero, analista_juridico |

---

## FASE 1: Planeacion MML (Wizard 7 Etapas)

**Rol**: Planeador de la UR coordinadora
**Permiso**: crear_programa, editar_mir
**Estado del programa**: BORRADOR

### Paso 1.0: Crear Programa

| Campo | Valor | Validacion |
|-------|-------|-----------|
| nombre | string min:5 max:255 | Requerido |
| clave | string min:2 max:30 | Requerido, unico |
| team_id | currentTeam del planeador | Automatico |
| ejercicio_fiscal | año actual | Automatico |
| estado | BORRADOR | Automatico |
| origen | NUEVO o IMPORTADO | Segun flujo |
| created_by | user_id | Automatico |

**Crea en pivot**: programa_team(programa_id, team_id, rol='coordinadora')

**Prerequisito**: Usuario con rol planeador y currentTeam activo
**Bloqueo**: Sin permiso crear_programa → 403

### Paso 1.1: E1 — Definicion del Problema

| Tabla | Datos creados |
|-------|--------------|
| arboles | tipo='problema', programa_presupuestario_id |
| arbol_nodos | tipo_nodo='problema_central', descripcion (min:20 max:1000) |

**Prerequisito**: Programa creado
**Bloqueo**: Ninguno (primera etapa)
**IA**: Valida sintaxis del problema central

### Paso 1.2: E2 — Arbol del Problema

| Tabla | Datos creados |
|-------|--------------|
| arbol_nodos | tipo_nodo: causa_directa, causa_indirecta, consecuencia_directa, consecuencia_indirecta |

Estructura jerarquica:
```
consecuencia_indirecta
  └── consecuencia_directa
        └── problema_central
              └── causa_directa
                    └── causa_indirecta
```

**Prerequisito**: E1 completada (problema central definido)
**Bloqueo**: Sin problema central no hay arbol
**IA**: Sugiere causas/consecuencias

### Paso 1.3: E3 — Arbol de Objetivos

| Tabla | Datos creados |
|-------|--------------|
| arboles | tipo='objetivos', programa_presupuestario_id |
| arbol_nodos | Inversion del arbol de problemas → fin, proposito, componente, actividad |

**Prerequisito**: E2 completada (arbol de problemas)
**Bloqueo**: Sin arbol de problemas no hay arbol de objetivos
**IA**: Genera inversion automatica

### Paso 1.4: E4 — Seleccion de Alternativas

| Tabla | Datos creados |
|-------|--------------|
| alternativas | nombre, seleccionada (bool), justificacion_seleccion |
| alternativa_nodo | Pivot: alternativa_id ↔ arbol_nodo_id |

**Prerequisito**: E3 completada (arbol de objetivos)
**Bloqueo**: Sin objetivos no hay alternativas
**Regla**: Exactamente 1 alternativa debe estar seleccionada=true

### Paso 1.5: E5 — Embudo de Poblaciones

| Tabla | Datos creados |
|-------|--------------|
| poblaciones_programa | unidad_medida, referencia_cantidad, referencia_fuente, potencial_cantidad, potencial_fuente, objetivo_cantidad, objetivo_justificacion, anio_ejercicio |

Embudo: Referencia > Potencial > Objetivo
```
Poblacion de referencia:  1,200,000 (INEGI Censo 2020)
Poblacion potencial:        450,000 (productores agave registrados)
Poblacion objetivo:          12,000 (productores meta 2025)
```

**Prerequisito**: E4 completada (alternativa seleccionada)
**Bloqueo**: Sin alternativa seleccionada no se define poblacion

### Paso 1.6: E6 — Alineacion Estrategica

| Tabla | Campos actualizados en mir_niveles |
|-------|-----------------------------------|
| mir_niveles (FIN) | ped_objetivo_estrategico_id |
| mir_niveles (PROPOSITO) | ped_objetivo_estrategico_id |
| mir_niveles (COMPONENTE) | ped_linea_accion_id |
| mir_niveles (ACTIVIDAD) | ped_linea_accion_id, programa_derivado_objetivo_id |

**Prerequisito**: E5 completada + catalogos PED/PND/ODS seedeados
**Bloqueo**: Sin catalogos, busqueda semantica no encuentra resultados
**IA**: SemanticSearchService busca objetivos PED por similitud con resumen narrativo

### Paso 1.7: E7 — Editor de MIR (el paso mas complejo)

Este paso crea la mayor cantidad de datos y tiene sub-pasos:

#### 1.7.1 Prellenado de Niveles MIR

Si no existen niveles, MirPrellenadoService crea estructura base desde E3:

| Tabla | Datos creados |
|-------|--------------|
| mir_niveles | 1 FIN + 1 PROPOSITO + N COMPONENTES + N ACTIVIDADES |

Cada nivel tiene: resumen_narrativo (de E3), supuestos, orden, arbol_nodo_id

#### 1.7.2 Edicion de Niveles

Para cada MirNivel:
- resumen_narrativo (texto largo)
- supuestos (texto largo)
- team_id (solo COMPONENTE/ACTIVIDAD, para asignar coadyuvante)

**Validacion sintaxis (IA)**:
- Fin: debe ser contribucion ("Contribuir a...")
- Proposito: sustantivo derivado, no infinitivo
- Componente: producto/servicio entregado en participio
- Actividad: sustantivo derivado del verbo

Campos de validacion: sintaxis_valida, sintaxis_observacion, sintaxis_sugerencia, sintaxis_validada_at

#### 1.7.3 Creacion de Indicadores (por cada nivel MIR)

| Campo | Reglas por nivel |
|-------|-----------------|
| tipo | FIN/PROPOSITO: estrategico(fijo), COMPONENTE: estrategico/gestion(flexible), ACTIVIDAD: gestion(fijo) |
| dimension | FIN: eficacia. PROPOSITO: eficacia/eficiencia. COMPONENTE: eficacia/eficiencia/calidad. ACTIVIDAD: eficacia/eficiencia/economia |
| frecuencia | FIN: anual/bianual/sexenal. PROPOSITO: semestral/anual. COMPONENTE: trimestral/semestral. ACTIVIDAD: mensual/trimestral |
| sentido | ascendente/descendente/regular |
| nombre | string max:255 |
| formula_texto | Texto libre ej: "(A / B) x 100" |
| linea_base | decimal(12,4) nullable |
| meta | decimal(12,4) nullable |
| rango_verde_min/max | decimal(8,2) nullable |
| rango_amarillo_min/max | decimal(8,2) nullable |
| unidad_medida_id | FK nullable |
| activo_seguimiento | bool (si se reporta en tracking) |

**Prerequisito**: Nivel MIR creado
**Bloqueo**: Tipo y dimensiones restringidos por nivel (IndicadorReglasService)

#### 1.7.4 Formula y Variables (por cada indicador)

| Tabla | Datos |
|-------|-------|
| indicador_variables | simbolo(A,B,C), nombre, descripcion, comportamiento, unidad_medida_id, orden |

**IA**: Extrae variables automaticamente desde formula_texto
**Manual**: Agregar variable, asignar simbolo

**Prerequisito**: formula_texto definida
**Bloqueo**: Sin formula, CapturaAvance no puede calcular resultado

#### 1.7.5 Medios de Verificacion (por cada indicador)

| Tabla | Datos |
|-------|-------|
| medios_verificacion | nombre, descripcion, fuente, frecuencia, orden |

**Prerequisito**: Indicador creado
**Bloqueo**: MIR incompleta sin medios; reportes los requieren

#### 1.7.6 Validacion CREMAA (por cada indicador)

| Tabla | Datos |
|-------|-------|
| cremaa_validaciones | claro, relevante, economico, monitoreable, adecuado, aportante + observaciones |

**IA**: Evalua los 6 criterios automaticamente
**Prerequisito**: Indicador con nombre y formula definidos

#### 1.7.7 Anexos Transversales (por cada indicador)

| Tabla | Datos |
|-------|-------|
| indicador_anexo_transversal | indicador_id, anexo_transversal_id (pivot) |

Vinculos a: Genero, NNA, Cambio Climatico, Anticorrupcion

**Prerequisito**: AnexosTransversalesSeeder ejecutado

#### 1.7.8 Asignacion de UR Coadyuvante

Solo para COMPONENTE y ACTIVIDAD:
- MirEditor::asignarUrCoadyuvante(nivelId, teamId)
- Actualiza mir_niveles.team_id
- Crea/actualiza programa_team con rol='coadyuvante'

**Prerequisito**: Otra UR registrada en el sistema
**Bloqueo**: FIN y PROPOSITO no pueden tener coadyuvante

#### 1.7.9 Calendarizacion de Metas

| Tabla | Datos |
|-------|-------|
| metas_periodo | indicador_id, periodo, meta_periodo, ejercicio_fiscal, activo, fecha_apertura, fecha_cierre |

CalendarizacionService genera periodos segun frecuencia:
- mensual: 12 periodos (meta/12)
- trimestral: 4 periodos (meta/4)
- semestral: 2 periodos (meta/2)
- anual: 1 periodo (meta completa)
- bianual: 1 periodo solo en años pares
- sexenal: 1 periodo por ejercicio

CalendarioService asigna fechas de apertura/cierre:
- Apertura: mes siguiente al cierre del periodo
- Cierre: apertura + 14 dias

**Prerequisito**: Indicador con meta definida y activo_seguimiento=true
**Bloqueo**: Sin MetaPeriodo, no se crean Avances

#### 1.7.10 Snapshots y Versionado

| Tabla | Datos |
|-------|-------|
| mir_versiones | programa_id, etiqueta, snapshot(jsonb), creado_por |

**Prerequisito**: MIR con al menos 1 nivel
**Bloqueo**: Ninguno (opcional)

### Finalizacion de Fase 1

Cuando planeacion_completada_at != null:
- EstadoConsolidadoService marca planeacion_estado = 'mir_completa'
- El programa puede avanzar a Fases 2, 3 y 4

---

## FASE 2: Presupuesto (puede iniciar en paralelo con Fase 3)

**Rol**: Analista Financiero de la UR coordinadora
**Permiso**: gestionar_presupuesto, capturar_avance_financiero

### Paso 2.1: Crear Partidas Presupuestales

| Tabla | Datos |
|-------|-------|
| partidas_presupuestales | programa_id, clave_partida(COG), descripcion, monto_aprobado, monto_modificado, ejercicio_fiscal, team_id, registrado_por |

**Prerequisito**: Programa creado (no necesita MIR completa)
**Bloqueo**: Sin permiso gestionar_presupuesto → 403

### Paso 2.2: Calendarizar Gasto

| Tabla | Datos |
|-------|-------|
| metas_gasto_trimestral | partida_id, trimestre(1-4), monto_programado |

Distribucion tipica: 20/25/30/25% o 25/25/25/25%

**Prerequisito**: Al menos 1 partida creada
**Bloqueo**: Sin partidas no hay calendarizacion

### Paso 2.3: Capturar Avance Financiero (recurrente por trimestre)

| Tabla | Datos |
|-------|-------|
| avances_financieros | partida_id, trimestre, monto_comprometido, monto_devengado, monto_pagado, registrado_por, observaciones |

**Validacion**: pagado <= devengado <= comprometido
**Prerequisito**: Partida + meta de gasto del trimestre
**Frecuencia**: Trimestral

### Finalizacion de Fase 2

EstadoConsolidadoService marca:
- sin_partidas → parcial → costeado (con avances) → calendarizado (con metas)

---

## FASE 3: Juridico (puede iniciar en paralelo con Fase 2)

**Rol**: Analista Juridico de la UR coordinadora
**Permiso**: gestionar_sustento_legal, validar_sustento_legal, gestionar_reglas_operacion

### Paso 3.1: Registrar Sustento Legal

| Tabla | Datos |
|-------|-------|
| sustento_legal_programa | programa_id, tipo(FACULTAD_UR/MANDATO_GASTO/ROP), catalogo_ordenamiento_id, ordenamiento, articulo, descripcion, nivel_jerarquia, vigente, registrado_por, team_id |

**Prerequisito**: CatalogoOrdenamientosSeeder ejecutado + programa creado
**Minimo**: 1 FACULTAD_UR + 1 MANDATO_GASTO para estado=validado

### Paso 3.2: Subir Documentos Normativos (ROPs)

| Tabla | Datos |
|-------|-------|
| documentos_normativos | programa_id, nombre, descripcion, archivo(path), tipo, registrado_por, team_id |

**Prerequisito**: Programa creado
**Bloqueo**: Sin permiso gestionar_reglas_operacion → 403

### Paso 3.3: Validar Checklist Juridico

| Tabla | Datos |
|-------|-------|
| validacion_juridica_programa | programa_id, ejercicio_fiscal, estado(PENDIENTE/VALIDADO/RECHAZADO), tiene_facultad_ur, tiene_mandato_gasto, tiene_rop, observaciones, validado_por, validado_at |

**Prerequisito**: Al menos 1 sustento legal registrado
**Bloqueo**: Sin sustento → estado = sin_registro

### Finalizacion de Fase 3

EstadoConsolidadoService marca:
- sin_registro → pendiente → validado / rechazado

---

## FASE 4: Seguimiento (requiere Fase 1 completada)

### Paso 4.0: Apertura Automatica de Periodos (cron)

| Tabla | Datos |
|-------|-------|
| avances | meta_periodo_id, indicador_id, estado=EN_CAPTURA, capturado_por |

Comando: `mir:abrir-periodos` (diario)
Logica de asignacion de team:
```
team_id = mir_nivel.team_id ?? programa.team_id
→ Busca operador con capturar_avance en ese team
→ Crea Avance asignado a ese operador
→ Notifica: PeriodoAbiertoNotification
```

**Prerequisito**: MetaPeriodo con fecha_apertura <= hoy, activo=true, sin avance
**Bloqueo**: Sin MetaPeriodo no se crean avances

### Paso 4.1: Captura (Operador)

| Tabla | Datos |
|-------|-------|
| avance_variables | avance_id, indicador_variable_id, valor, valor_acumulado |
| avances | resultado, semaforo_calculado, justificacion_ia, justificacion_final |

Flujo:
1. Ingresar valor por cada variable (A, B, C...)
2. FormulaEvaluatorService evalua formula → resultado
3. SemaforoService calcula semaforo → verde/amarillo/rojo
4. Si amarillo/rojo: justificacion obligatoria (min 10 chars)
5. Guardar

**Prerequisito**: Avance en estado EN_CAPTURA + indicador con formula + variables definidas
**Bloqueo**: Sin formula/variables → no puede calcular

### Paso 4.2: Subir Evidencias (Operador)

| Tabla | Datos |
|-------|-------|
| avance_evidencias | avance_id, nombre_archivo, ruta_archivo, mime_type, tamano_bytes, hash_archivo, nombre_documento, area_generadora, fecha_documento, subido_por |

**Prerequisito**: Avance no congelado y en estado editable (EN_CAPTURA)
**Formatos**: pdf, xlsx, xls, jpg, jpeg, png, doc, docx (max 10MB)

### Paso 4.3: Enviar a Revision (Operador)

Transicion: EN_CAPTURA → EN_REVISION
Notifica: planeadores del team (coordinadora o coadyuvante segun mir_nivel.team_id)

**Prerequisito**: Avance con resultado calculado
**Bloqueo**: Solo desde estado EN_CAPTURA

### Paso 4.4: Revision (Planeador)

Opcion A — Aprobar:
- EN_REVISION → APROBADO
- congelado_at = now()
- Avance queda inmutable

Opcion B — Observar:
- EN_REVISION → OBSERVADO
- Observacion obligatoria (min 10 chars)
- Notifica al operador capturador
- Operador corrige → OBSERVADO → EN_CAPTURA → repite desde 4.1

**Prerequisito**: Avance en EN_REVISION + permiso revisar_avance
**Bloqueo**: Solo planeadores del team del nivel

### Paso 4.5: Desbloqueo (Operador → Admin)

Si un avance APROBADO necesita correccion:
1. Operador solicita desbloqueo (motivo obligatorio)
2. Admin aprueba → APROBADO → EN_CAPTURA (bypass maquina de estados)
3. O rechaza (resolucion obligatoria)

| Tabla | Datos |
|-------|-------|
| desbloqueos | avance_id, motivo, solicitado_por, resuelto_por, estado, resolucion, resuelto_at |

**Prerequisito**: Avance congelado (APROBADO) + no hay solicitud pendiente previa

### Paso 4.6: Vencimiento (Automatico)

Si fecha_cierre del MetaPeriodo pasa sin avance → estado = VENCIDO (terminal)

---

## FASE 5: Evaluacion y Reportes

**Rol**: Planeador, Admin
**Permiso**: exportar_reportes, ver_sabana_captura, ver_concentrado_captura, exportar_cuenta_publica

### Paso 5.1: Estado Consolidado Tripartita

| Tabla | Datos |
|-------|-------|
| estado_validacion_programa | planeacion_estado, juridico_estado, financiero_estado, consolidado(completo/parcial/critico), validaciones_completas(0-3) |

EstadoConsolidadoService::recalcular() evalua:
- Planeacion: ¿MIR completa? (planeacion_completada_at != null)
- Juridico: ¿Validado? (validacion_juridica_programa.estado = 'validado')
- Financiero: ¿Calendarizado? (tiene partidas + metas)

**Prerequisito**: Datos de las 3 fases

### Paso 5.2: Semaforo Combinado Fisico-Financiero

SemaforoFinancieroService::combinado() cruza:
- Semaforo fisico (promedio de indicadores MIR)
- Semaforo financiero (pagado vs programado)
- Regla especial: financiero verde + fisico rojo = rojo

### Paso 5.3: Indice de Eficiencia

PresupuestoResumenService::indiceEficiencia():
- (% avance fisico) / (% avance financiero)
- > 1.0 = eficiente, < 1.0 = ineficiente

---

## MAPA DE DEPENDENCIAS (que bloquea que)

```
RolesPermisos ─────────────┐
Catalogos PED/PND/ODS ────┐│
URs + Usuarios ───────────┐││
                          │││
                          ▼▼▼
                   CREAR PROGRAMA
                          │
              ┌───────────┴───────────┐
              │                       │
         E1-E6 Wizard            (paralelo)
              │                       │
              ▼                       ▼
         E7 MIR Editor         Crear Partidas (F2)
              │                 Registrar Sustento (F3)
      ┌───────┼───────┐              │
      │       │       │              │
   Niveles  Indicadores  Asignar    Calendarizar (F2)
      │       │       │  Coadyuv.   Validar (F3)
      │    ┌──┼──┐    │              │
      │  Formula Variables │         │
      │    │  │  │    Medios        │
      │    │  CREMAA  │              │
      │    │       │  │              │
      │    └──┬────┘  │              │
      │       │       │              │
      │  MetaPeriodos │              │
      │       │       │              │
      ▼───────▼───────▼              │
    planeacion_completada_at         │
              │                      │
              ▼                      ▼
    mir:abrir-periodos ──► Avances EN_CAPTURA
              │
    Operador captura (variables → formula → semaforo)
              │
    Operador sube evidencias
              │
    Operador envia a revision
              │
    Planeador aprueba/observa
              │
              ▼
    EstadoConsolidadoService::recalcular()
              │
    ┌─────────┼─────────┐
    │         │         │
 Planeacion Juridico Financiero
    │         │         │
    └─────────┼─────────┘
              │
    consolidado: completo/parcial/critico
```

---

## PARA LOS SEEDERS: Orden de Creacion de Datos

Un seeder realista debe seguir esta secuencia:

```
1. Roles y permisos (los 3 seeders de permisos)
2. Catalogos (ODS, PND, PED, Unidades Medida, Anexos, Ordenamientos)
3. URs/Teams
4. Usuarios (5 roles x N URs)
5. Programas (con programa_team coordinadora)
6. Arboles problema/objetivos (E1-E3) — NUEVO
7. Alternativas (E4) — NUEVO
8. Poblaciones (E5) — NUEVO
9. MirNiveles con alineacion PED (E6-E7)
10. Asignar coadyuvantes a componentes/actividades
11. Indicadores con formula_texto y reglas por nivel
12. IndicadorVariables (A, B, C por indicador)
13. MediosVerificacion (por indicador)
14. CremaaValidacion (por indicador) — NUEVO
15. Vincular AnexosTransversales (por indicador) — NUEVO
16. MetaPeriodos (calendarizados)
17. Partidas presupuestales (F2)
18. Metas gasto trimestral (F2)
19. Sustentos legales (F3)
20. Validacion juridica (F3)
21. Avances con AvanceVariables y semaforo (F4)
22. Historial de transiciones realista (F4)
23. Al menos 1 desbloqueo (F4) — NUEVO
24. Avances financieros (F5)
25. EstadoValidacionPrograma (recalcular) — NUEVO
26. Generar expected-results.md
```
