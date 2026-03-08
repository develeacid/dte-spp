# Diccionario de Datos — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## Convenciones

- **PK**: Clave primaria (bigint auto-incremental salvo indicación)
- **FK**: Clave foránea con restricción referencial
- **ENUM**: Valor restringido al conjunto listado
- **JSONB**: Campo JSON binario (PostgreSQL)
- Todas las tablas incluyen `created_at` y `updated_at` (timestamps) salvo indicación
- Soft-delete indicado con columna `deleted_at`

---

## 1. Dominio: Autenticación y Equipos

### `users`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| name | varchar | NOT NULL | Nombre completo |
| email | varchar | UNIQUE, NOT NULL | Correo electrónico |
| password | varchar | NOT NULL | Hash bcrypt |
| two_factor_secret | text | NULLABLE | Secret TOTP para 2FA |
| two_factor_recovery_codes | text | NULLABLE | Códigos de recuperación |
| two_factor_confirmed_at | timestamp | NULLABLE | Fecha confirmación 2FA |
| current_team_id | bigint | FK → teams | Equipo activo |
| profile_photo_path | varchar | NULLABLE | Ruta foto de perfil |
| active | boolean | DEFAULT true | Estado de cuenta |
| invitation_token | varchar | NULLABLE | Token de activación |
| invitation_expires_at | timestamp | NULLABLE | Expiración de invitación |
| activated_at | timestamp | NULLABLE | Fecha de activación |
| email_verified_at | timestamp | NULLABLE | Verificación de email |

**Traits:** HasRoles, LogsActivity, HasTeams, TwoFactorAuthenticatable, Notifiable

### `teams`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| user_id | bigint | FK → users | Dueño del equipo |
| name | varchar | NOT NULL | Nombre del equipo |
| personal_team | boolean | NOT NULL | Es equipo personal |
| clave_ur | varchar | NULLABLE | Clave de Unidad Responsable |
| nombre_ur | varchar | NULLABLE | Nombre de la UR |
| tipo_ur | varchar | NULLABLE | sustantiva / apoyo |

### `team_user` (pivot)

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| team_id | bigint | FK → teams | Equipo |
| user_id | bigint | FK → users | Usuario |
| role | varchar | | Rol en equipo (Jetstream) |

### `team_invitations`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| team_id | bigint | FK → teams | Equipo destino |
| email | varchar | NOT NULL | Email invitado |
| role | varchar | NULLABLE | Rol asignado |

---

## 2. Dominio: Permisos (Spatie Permission)

### `roles`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| name | varchar | admin, planeador, operador |
| guard_name | varchar | web |

### `permissions`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| name | varchar | Nombre del permiso |
| guard_name | varchar | web |

**Permisos del sistema:**

| Permiso | Admin | Planeador | Operador |
|---------|:-----:|:---------:|:--------:|
| gestionar_catalogos | ✓ | ✓ | |
| crear_programa | ✓ | ✓ | |
| editar_mir | ✓ | ✓ | |
| capturar_avance | ✓ | | ✓ |
| revisar_avance | ✓ | ✓ | |
| aprobar_avance | ✓ | ✓ | |
| exportar_reportes | ✓ | ✓ | ✓ |
| administrar_usuarios | ✓ | | |
| invitar_usuarios | ✓ | | |

---

## 3. Dominio: Cascada de Planeación (PED)

### `ped_planes`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| nombre | varchar | NOT NULL | Nombre del plan |
| periodo_inicio | integer | NOT NULL | Año de inicio |
| periodo_fin | integer | NOT NULL | Año de fin |
| activo | boolean | DEFAULT false | Plan vigente |
| team_id | bigint | FK → teams | Equipo propietario |

**Traits:** LogsActivity

### `ped_ejes`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| ped_plan_id | bigint | FK → ped_planes | Plan padre |
| nombre | varchar | NOT NULL | Nombre del eje |
| orden | integer | NOT NULL | Posición |

### `ped_temas`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| ped_eje_id | bigint | FK → ped_ejes | Eje padre |
| nombre | varchar | NOT NULL | Nombre del tema |
| orden | integer | NOT NULL | Posición |

### `ped_objetivos_estrategicos`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| ped_tema_id | bigint | FK → ped_temas | Tema padre |
| nombre | varchar | NOT NULL | Descripción del objetivo |
| orden | integer | NOT NULL | Posición |

### `ped_estrategias`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| ped_objetivo_estrategico_id | bigint | FK | Objetivo padre |
| nombre | varchar | NOT NULL | Descripción |
| orden | integer | NOT NULL | Posición |

### `ped_lineas_accion`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| ped_estrategia_id | bigint | FK | Estrategia padre |
| nombre | varchar | NOT NULL | Descripción |
| orden | integer | NOT NULL | Posición |

---

## 4. Dominio: PND y ODS

### `pnd_ejes`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| nombre | varchar | Nombre del eje del PND |
| orden | integer | Posición |

### `pnd_objetivos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| pnd_eje_id | bigint | FK → pnd_ejes |
| nombre | varchar | Descripción del objetivo |
| orden | integer | Posición |

### `pnd_estrategias`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| pnd_objetivo_id | bigint | FK → pnd_objetivos |
| nombre | varchar | Descripción |
| orden | integer | Posición |

### `ods_objetivos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| numero | integer | Número ODS (1-17) |
| nombre | varchar | Nombre del objetivo |

### `ods_metas`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| ods_objetivo_id | bigint | FK → ods_objetivos |
| clave | varchar | Clave de la meta (ej. 1.1) |
| nombre | text | Descripción |

---

## 5. Dominio: Alineación

### `alineacion_ped_pnd` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| ped_objetivo_estrategico_id | bigint | FK |
| pnd_objetivo_id | bigint | FK |

### `alineacion_pnd_ods` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| pnd_objetivo_id | bigint | FK |
| ods_meta_id | bigint | FK |

### `alineacion_linea_programa` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| ped_linea_accion_id | bigint | FK |
| programa_derivado_objetivo_id | bigint | FK |

---

## 6. Dominio: Programas Derivados

### `programas_derivados`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| nombre | varchar | Nombre del programa |
| tipo | varchar | ENUM: sectorial, especial, institucional, regional |
| team_id | bigint | FK → teams |

### `programas_derivados_objetivos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_derivado_id | bigint | FK |
| nombre | text | Descripción del objetivo |
| orden | integer | Posición |

---

## 7. Dominio: MML (Metodología de Marco Lógico)

### `programa_presupuestarios`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| nombre | varchar | NOT NULL | Nombre del programa |
| clave | varchar | UNIQUE | Clave identificadora |
| team_id | bigint | FK → teams | Equipo propietario |
| creado_por | bigint | FK → users | Creador |
| estado | varchar | DEFAULT borrador | borrador, activo, cerrado |
| origen | varchar | DEFAULT nuevo | nuevo, importado |
| ejercicio_fiscal | integer | | Año fiscal |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Traits:** SoftDeletes

### `arboles`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| tipo | varchar | ENUM: problema, objetivos |

### `arbol_nodos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| arbol_id | bigint | FK → arboles |
| tipo | varchar | ENUM: problema_central, causa_directa, causa_indirecta, efecto_directo, efecto_indirecto, objetivo_central, medio_directo, medio_indirecto, fin_directo, fin_indirecto |
| contenido | text | Descripción del nodo |
| parent_id | bigint | FK → arbol_nodos (auto-referencia) |
| orden | integer | Posición |

### `alternativas`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| nombre | varchar | Nombre de la alternativa |
| seleccionada | boolean | Alternativa elegida |
| justificacion | text | Justificación |

### `alternativa_nodo` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| alternativa_id | bigint | FK |
| arbol_nodo_id | bigint | FK |

### `mir_niveles`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK | Programa |
| tipo_nivel | varchar | NOT NULL | ENUM: fin, proposito, componente, actividad |
| resumen_narrativo | text | NOT NULL | Texto del nivel |
| orden | integer | NOT NULL | Posición |
| team_id | bigint | FK → teams | Equipo |
| componente_id | bigint | FK → mir_niveles | Componente padre (para actividades) |
| arbol_nodo_id | bigint | FK → arbol_nodos | Nodo origen |
| ped_objetivo_estrategico_id | bigint | FK | Alineación PED |
| ped_linea_accion_id | bigint | FK | Línea de acción PED |
| validacion_sintaxis | jsonb | NULLABLE | Resultado validación IA |

**Traits:** LogsActivity

### `mir_versiones`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| version | integer | Número de versión |
| snapshot | jsonb | Snapshot completo de la MIR |
| notas | text | Notas del versionamiento |

### `indicadores`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| mir_nivel_id | bigint | FK → mir_niveles | Nivel MIR |
| nombre | varchar | NOT NULL | Nombre del indicador |
| tipo | varchar | | estrategico, gestion |
| dimension | varchar | | eficacia, eficiencia, calidad, economia |
| frecuencia | varchar | | mensual, trimestral, semestral, anual, bianual, sexenal |
| sentido | varchar | | ascendente, descendente, regular |
| meta | decimal | | Meta anual |
| formula | text | NULLABLE | Fórmula de cálculo |
| metodo_calculo | text | NULLABLE | Descripción del método |
| catalogo_unidad_medida_id | bigint | FK | Unidad de medida |
| activo_seguimiento | boolean | DEFAULT false | Habilitado para captura |
| orden | integer | | Posición |

**Traits:** LogsActivity

### `indicador_variables`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores |
| nombre | varchar | Nombre de la variable |
| comportamiento | varchar | acumulable, continua |
| unidad_medida | varchar | Unidad |
| fuente_informacion | text | Fuente |
| orden | integer | Posición |

### `medios_verificacion`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores |
| descripcion | text | Descripción del medio |
| fuente | varchar | Fuente de información |
| orden | integer | Posición |

### `cremaa_validaciones`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores (unique) |
| claro | boolean | Criterio Claro |
| relevante | boolean | Criterio Relevante |
| economico | boolean | Criterio Económico |
| monitoreable | boolean | Criterio Monitoreable |
| adecuado | boolean | Criterio Adecuado |
| aportacion_marginal | boolean | Criterio Aportación Marginal |
| notas | text | Notas de validación |

### `catalogo_unidades_medida`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| nombre | varchar | Nombre de la unidad |
| clave | varchar | Clave corta |

---

## 8. Dominio: Seguimiento (Tracking)

### `metas_periodo`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| indicador_id | bigint | FK → indicadores | Indicador |
| periodo | integer | NOT NULL | Número del período |
| meta_periodo | decimal | NOT NULL | Meta del período |
| ejercicio_fiscal | integer | NOT NULL | Año fiscal |
| activo | boolean | DEFAULT true | Habilitado |
| fecha_apertura | date | NULLABLE | Fecha de apertura de captura |
| fecha_cierre | date | NULLABLE | Fecha límite de captura |

### `avances`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| meta_periodo_id | bigint | FK → metas_periodo | Período |
| indicador_id | bigint | FK → indicadores | Indicador |
| resultado | decimal | NULLABLE | Valor reportado |
| semaforo_calculado | varchar | NULLABLE | verde, amarillo, rojo |
| estado | varchar | NOT NULL | en_captura, en_revision, observado, aprobado, vencido |
| observaciones | text | NULLABLE | Notas del capturista |
| observacion_revisor | text | NULLABLE | Notas del revisor |
| capturado_por | bigint | FK → users | Capturista |
| revisado_por | bigint | FK → users | Revisor |
| aprobado_por | bigint | FK → users | Aprobador |

### `avance_variables`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| indicador_variable_id | bigint | FK → indicador_variables |
| valor | decimal | Valor reportado |

### `avance_evidencias`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| ruta_archivo | varchar | Ruta en storage |
| nombre_original | varchar | Nombre original del archivo |
| nombre_documento | varchar | Nombre descriptivo |
| area_generadora | varchar | Área que genera |
| fecha_documento | date | Fecha del documento |
| mime_type | varchar | Tipo MIME |
| tamano | bigint | Tamaño en bytes |
| subido_por | bigint | FK → users |

### `desbloqueos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| solicitante_id | bigint | FK → users |
| motivo | text | Justificación |
| estado | varchar | pendiente, aprobado, rechazado |
| resuelto_por | bigint | FK → users |
| resuelto_at | timestamp | Fecha de resolución |

---

## 9. Dominio: Evaluación

### `evaluaciones_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK | Programa |
| ejercicio_fiscal | integer | NOT NULL | Año fiscal |
| ponderacion_fin | decimal | | Peso nivel Fin |
| ponderacion_proposito | decimal | | Peso nivel Propósito |
| ponderacion_componente | decimal | | Peso nivel Componente |
| ponderacion_actividad | decimal | | Peso nivel Actividad |
| indice_eficacia | decimal | NULLABLE | Índice calculado |
| resultados | jsonb | NULLABLE | Resultados detallados |
| calculado_por | bigint | FK → users | Usuario que calculó |
| calculado_at | timestamp | NULLABLE | Fecha de cálculo |

**Traits:** LogsActivity

### `anexos_transversales`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| nombre | varchar | Nombre del anexo |
| clave | varchar | Clave identificadora |

### `indicador_anexo_transversal` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| indicador_id | bigint | FK |
| anexo_transversal_id | bigint | FK |

---

## 10. Dominio: Importación e IA

### `importacion_reportes`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| archivo_original | varchar | Nombre del archivo subido |
| ruta_archivo | varchar | Ruta en storage |
| estado | varchar | pendiente, procesando, completado, error |
| datos_extraidos | jsonb | Datos parseados |
| errores | jsonb | Errores encontrados |
| procesado_por | bigint | FK → users |

### `llm_logs`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| modelo | varchar | Modelo usado |
| prompt | text | Prompt enviado |
| respuesta | text | Respuesta recibida |
| tokens_entrada | integer | Tokens de entrada |
| tokens_salida | integer | Tokens de salida |
| costo_entrada | decimal | Costo entrada |
| costo_salida | decimal | Costo salida |
| costo_total | decimal | Costo total |
| duracion_ms | integer | Duración en ms |
| contexto | varchar | Contexto de uso |
| team_id | bigint | FK → teams |

### `llm_budgets`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| team_id | bigint | FK → teams |
| mes | integer | Mes |
| anio | integer | Año |
| presupuesto | decimal | Límite mensual |
| consumido | decimal | Monto consumido |

---

## 11. Auditoría

### `activity_log` (spatie/laravel-activitylog)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| log_name | varchar | Nombre del log |
| description | varchar | created, updated, deleted |
| subject_type | varchar | Modelo afectado |
| subject_id | bigint | ID del registro |
| causer_type | varchar | Modelo causante (User) |
| causer_id | bigint | ID del usuario |
| properties | jsonb | Atributos old/new |
| batch_uuid | uuid | UUID de batch |
| event | varchar | Evento |

**Modelos auditados:** User, PedPlan, PedEje, PedTema, PedObjetivoEstrategico, MirNivel, Indicador, EvaluacionPrograma

---

## 12. Enums del Sistema

| Enum | Valores |
|------|---------|
| SystemRole | admin, planeador, operador |
| SystemPermission | gestionar_catalogos, crear_programa, editar_mir, capturar_avance, revisar_avance, aprobar_avance, exportar_reportes, administrar_usuarios, invitar_usuarios |
| EstadoPrograma | borrador, activo, cerrado |
| OrigenPrograma | nuevo, importado |
| TipoArbol | problema, objetivos |
| TipoNodo | problema_central, causa_directa, causa_indirecta, efecto_directo, efecto_indirecto, objetivo_central, medio_directo, medio_indirecto, fin_directo, fin_indirecto |
| TipoNivelMir | fin, proposito, componente, actividad |
| TipoIndicador | estrategico, gestion |
| DimensionIndicador | eficacia, eficiencia, calidad, economia |
| FrecuenciaMedicion | mensual, trimestral, semestral, anual, bianual, sexenal |
| SentidoIndicador | ascendente, descendente, regular |
| ComportamientoVariable | acumulable, continua |
| EstadoAvance | en_captura, en_revision, observado, aprobado, vencido |
| TipoProgramaDerivado | sectorial, especial, institucional, regional |
| TipoUnidadResponsable | sustantiva, apoyo |

---

## 13. Diagrama de Relaciones (resumen)

```
PedPlan → PedEje → PedTema → PedObjetivoEstrategico → PedEstrategia → PedLineaAccion
                                        ↕ alineación                          ↕ alineación
                                    PndObjetivo ↔ OdsMeta          ProgramaDerivadoObjetivo

ProgramaPresupuestario → Arbol → ArbolNodo
                       → Alternativa ↔ ArbolNodo
                       → MirNivel → Indicador → MetaPeriodo → Avance → AvanceEvidencia
                                                                     → AvanceVariable
                                   → IndicadorVariable
                                   → MedioVerificacion
                                   → CremaaValidacion
                       → MirVersion
                       → EvaluacionPrograma

User → Team (Jetstream Teams)
     → Avance (capturado_por, revisado_por, aprobado_por)
     → ActivityLog (causer)
```
