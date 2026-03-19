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
| password | varchar | NULLABLE | Hash bcrypt |
| two_factor_secret | text | NULLABLE | Secret TOTP para 2FA |
| two_factor_recovery_codes | text | NULLABLE | Códigos de recuperación |
| two_factor_confirmed_at | timestamp | NULLABLE | Fecha confirmación 2FA |
| current_team_id | bigint | FK → teams | Equipo activo |
| profile_photo_path | varchar(2048) | NULLABLE | Ruta foto de perfil |
| email_verified_at | timestamp | NULLABLE | Verificación de email |
| activated_at | timestamp | NULLABLE | Fecha de activación |
| active | boolean | DEFAULT true | Estado de cuenta |
| invitation_token | varchar(64) | UNIQUE, NULLABLE | Token de activación |
| invitation_sent_at | timestamp | NULLABLE | Fecha de envío de invitación |
| remember_token | varchar | NULLABLE | Token de sesión persistente |

**Traits:** HasRoles, LogsActivity, HasTeams, TwoFactorAuthenticatable, Notifiable

### `password_reset_tokens`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| email | varchar | PK | Correo del usuario |
| token | varchar | NOT NULL | Token de restablecimiento |
| created_at | timestamp | NULLABLE | Fecha de creación |

### `sessions`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | varchar | PK | ID de sesión |
| user_id | bigint | FK → users, NULLABLE | Usuario |
| ip_address | varchar(45) | NULLABLE | Dirección IP |
| user_agent | text | NULLABLE | Agente del navegador |
| payload | longText | NOT NULL | Datos de sesión |
| last_activity | integer | INDEX | Última actividad |

**Nota:** No incluye timestamps convencionales.

### `teams`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| user_id | bigint | FK → users | Dueño del equipo |
| name | varchar | NOT NULL | Nombre del equipo |
| personal_team | boolean | NOT NULL | Es equipo personal |
| clave_ur | varchar | UNIQUE, NULLABLE | Clave de Unidad Responsable |
| titular | varchar | NULLABLE | Nombre del titular de la UR |
| tipo_ur | varchar | NULLABLE | sustantiva / apoyo |
| activa | boolean | DEFAULT true | UR activa |

### `team_user` (pivot)

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| team_id | bigint | FK → teams | Equipo |
| user_id | bigint | FK → users | Usuario |
| role | varchar | NULLABLE | Rol en equipo (Jetstream) |

**Restricción única:** `(team_id, user_id)`

### `team_invitations`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| team_id | bigint | FK → teams | Equipo destino |
| email | varchar | NOT NULL | Email invitado |
| role | varchar | NULLABLE | Rol asignado |

**Restricción única:** `(team_id, email)`

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
| nivel_gobierno | varchar | DEFAULT 'estatal' | estatal / municipal |
| periodo_inicio | smallint | NOT NULL | Año de inicio |
| periodo_fin | smallint | NOT NULL | Año de fin |
| activo | boolean | DEFAULT false | Plan vigente |

**Constraint:** Índice único parcial `ped_planes_activo_unique` — solo un plan con `activo = true` a la vez.

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
| id | bigint | PK |
| ped_objetivo_estrategico_id | bigint | FK |
| pnd_objetivo_id | bigint | FK |

**Restricción única:** `(ped_objetivo_estrategico_id, pnd_objetivo_id)`

### `alineacion_pnd_ods` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| pnd_objetivo_id | bigint | FK |
| ods_meta_id | bigint | FK |

**Restricción única:** `(pnd_objetivo_id, ods_meta_id)`

### `alineacion_linea_programa` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| ped_linea_accion_id | bigint | FK |
| programa_derivado_objetivo_id | bigint | FK |

**Restricción única:** `(ped_linea_accion_id, programa_derivado_objetivo_id)`

---

## 6. Dominio: Programas Derivados

### `programas_derivados`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| ped_plan_id | bigint | FK → ped_planes |
| nombre | varchar | Nombre del programa |
| descripcion | text | Descripción (nullable) |
| tipo | tipo_programa_derivado | ENUM nativo PG: sectorial, especial, institucional, regional |

### `programas_derivados_objetivos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_derivado_id | bigint | FK |
| clave | varchar(20) | Clave del objetivo (ej. "OS.1") |
| descripcion | text | Descripción del objetivo |
| embedding | vector(1536) | Embedding para búsqueda semántica (pgvector) |

**Restricción única:** `(programa_derivado_id, clave)`

---

## 7. Dominio: MML (Metodología de Marco Lógico)

### `programa_presupuestarios`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| team_id | bigint | FK → teams, NULLABLE | Equipo propietario |
| nombre | varchar | NOT NULL | Nombre del programa |
| clave | varchar | UNIQUE | Clave identificadora |
| ejercicio_fiscal | smallint | DEFAULT 2026 | Año fiscal |
| origen | varchar(20) | DEFAULT 'nuevo' | nuevo, importado |
| estado | varchar(20) | DEFAULT 'borrador' | borrador, activo, cerrado |
| planeacion_completada_at | timestamp | NULLABLE | Fecha en que se completó la planeación |
| geobase_program_id | bigint unsigned | NULLABLE | ID del programa en GeoBase |
| requiere_rop | boolean | DEFAULT false | Requiere Reglas de Operación publicadas |
| created_by | bigint | FK → users, NULLABLE | Creador |
| deleted_at | timestamp | NULLABLE | Soft delete |

**Traits:** SoftDeletes

**Índice:** `(team_id, ejercicio_fiscal)`

### `programa_team` (pivot)

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| team_id | bigint | FK → teams | Equipo |
| rol | varchar(20) | DEFAULT 'coadyuvante' | Rol del equipo en el programa |

**Restricción única:** `(programa_presupuestario_id, team_id)`

### `arboles`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| tipo | varchar(20) | ENUM: problema, objetivos |

**Restricción única:** `(programa_presupuestario_id, tipo)`

### `arbol_nodos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| arbol_id | bigint | FK → arboles |
| parent_id | bigint | FK → arbol_nodos (auto-referencia, nullable) |
| tipo_nodo | varchar(30) | ENUM: problema_central, causa_directa, causa_indirecta, efecto_directo, efecto_indirecto, objetivo_central, medio_directo, medio_indirecto, fin_directo, fin_indirecto |
| descripcion | text | Descripción del nodo |
| nodo_origen_id | bigint | FK → arbol_nodos (nullable) — referencia al nodo del árbol opuesto |
| orden | smallint | DEFAULT 0 |

### `alternativas`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| nombre | varchar | Nombre de la alternativa |
| seleccionada | boolean | DEFAULT false — Alternativa elegida |
| justificacion_seleccion | text | Justificación (nullable) |

### `alternativa_nodo` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| alternativa_id | bigint | FK |
| arbol_nodo_id | bigint | FK |

**Restricción única:** `(alternativa_id, arbol_nodo_id)`

### `mir_niveles`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK | Programa |
| tipo_nivel | varchar(20) | NOT NULL | ENUM: fin, proposito, componente, actividad |
| componente_id | bigint | FK → mir_niveles, NULLABLE | Componente padre (para actividades) |
| resumen_narrativo | text | NULLABLE | Texto del nivel |
| supuestos | text | NULLABLE | Supuestos del nivel |
| arbol_nodo_id | bigint | FK → arbol_nodos, NULLABLE | Nodo origen |
| orden | smallint | DEFAULT 0 | Posición |
| ped_objetivo_estrategico_id | bigint | FK, NULLABLE | Alineación PED |
| programa_derivado_objetivo_id | bigint | FK, NULLABLE | Alineación a programa derivado |
| ped_linea_accion_id | bigint | FK, NULLABLE | Línea de acción PED |
| team_id | bigint | FK → teams, NULLABLE | UR Coadyuvante |
| sintaxis_valida | boolean | NULLABLE | Resultado de validación IA |
| sintaxis_observacion | text | NULLABLE | Observación de la validación |
| sintaxis_sugerencia | text | NULLABLE | Sugerencia de la IA |
| sintaxis_validada_at | timestamp | NULLABLE | Fecha de validación |

**Traits:** LogsActivity

**Índices:** `(programa_presupuestario_id, tipo_nivel)`, `componente_id`

### `mir_versiones`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK |
| etiqueta | varchar(100) | Etiqueta de la versión |
| snapshot | jsonb | Snapshot completo de la MIR |
| created_by | bigint | FK → users (nullable) |

### `indicadores`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| mir_nivel_id | bigint | FK → mir_niveles | Nivel MIR |
| nombre | varchar | NOT NULL | Nombre del indicador |
| formula_texto | text | NULLABLE | Fórmula de cálculo |
| tipo | varchar(20) | | estrategico, gestion |
| dimension | varchar(20) | | eficacia, eficiencia, calidad, economia |
| frecuencia | varchar(20) | | mensual, trimestral, semestral, anual, bianual, sexenal |
| sentido | varchar(20) | NULLABLE | ascendente, descendente, regular |
| linea_base | decimal(12,4) | NULLABLE | Línea base del indicador |
| meta | decimal(12,4) | NULLABLE | Meta anual |
| rango_verde_min | decimal(8,2) | NULLABLE | Semáforo verde mínimo |
| rango_verde_max | decimal(8,2) | NULLABLE | Semáforo verde máximo |
| rango_amarillo_min | decimal(8,2) | NULLABLE | Semáforo amarillo mínimo |
| rango_amarillo_max | decimal(8,2) | NULLABLE | Semáforo amarillo máximo |
| rango_rojo_min | decimal(8,2) | NULLABLE | Semáforo rojo mínimo |
| rango_rojo_max | decimal(8,2) | NULLABLE | Semáforo rojo máximo |
| unidad_medida_id | bigint | FK → catalogo_unidades_medida, NULLABLE | Unidad de medida |
| orden | smallint | DEFAULT 0 | Posición |
| activo_seguimiento | boolean | DEFAULT true | Habilitado para captura |

**Traits:** LogsActivity

### `indicador_variables`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores |
| simbolo | varchar(5) | Símbolo de la variable (ej. "A", "B") |
| nombre | varchar | Nombre de la variable |
| descripcion | text | Descripción (nullable) |
| comportamiento | varchar(20) | acumulable, continua (nullable) |
| unidad_medida_id | bigint | FK → catalogo_unidades_medida (nullable) |
| geobase_endpoint_type | varchar(20) | Tipo de endpoint GeoBase (nullable) |
| geobase_reference_id | bigint unsigned | ID de referencia en GeoBase (nullable) |
| geobase_filter_params | json | Parámetros de filtro GeoBase (nullable) |
| geobase_value_key | varchar(30) | Clave del valor en respuesta GeoBase (nullable) |
| orden | smallint | DEFAULT 0 |

### `medios_verificacion`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores |
| nombre | varchar | Nombre del medio |
| descripcion | text | Descripción (nullable) |
| fuente | varchar | Fuente de información (nullable) |
| frecuencia | varchar(20) | Frecuencia (nullable) |
| orden | smallint | DEFAULT 0 |

### `cremaa_validaciones`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| indicador_id | bigint | FK → indicadores |
| claro | boolean | DEFAULT false |
| claro_observacion | text | Observación (nullable) |
| relevante | boolean | DEFAULT false |
| relevante_observacion | text | Observación (nullable) |
| economico | boolean | DEFAULT false |
| economico_observacion | text | Observación (nullable) |
| monitoreable | boolean | DEFAULT false |
| monitoreable_observacion | text | Observación (nullable) |
| adecuado | boolean | DEFAULT false |
| adecuado_observacion | text | Observación (nullable) |
| aportante | boolean | DEFAULT false |
| aportante_observacion | text | Observación (nullable) |

### `catalogo_unidades_medida`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| clave | varchar(10) | UNIQUE — Clave corta |
| nombre | varchar | Nombre de la unidad |

---

## 8. Dominio: Seguimiento (Tracking)

### `metas_periodo`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| indicador_id | bigint | FK → indicadores | Indicador |
| periodo | smallint | NOT NULL | Número del período |
| meta_periodo | decimal(12,4) | NOT NULL | Meta del período |
| ejercicio_fiscal | integer | NOT NULL | Año fiscal |
| activo | boolean | DEFAULT true | Habilitado |
| fecha_apertura | date | NULLABLE | Fecha de apertura de captura |
| fecha_cierre | date | NULLABLE | Fecha límite de captura |

**Restricción única:** `(indicador_id, periodo, ejercicio_fiscal)`

### `avances`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| meta_periodo_id | bigint | FK → metas_periodo | Período |
| indicador_id | bigint | FK → indicadores | Indicador |
| resultado | decimal(12,4) | NULLABLE | Valor reportado |
| semaforo_calculado | varchar(10) | NULLABLE | verde, amarillo, rojo |
| semaforo_ajustado | varchar(10) | NULLABLE | Semáforo ajustado manualmente |
| justificacion_ia | text | NULLABLE | Justificación generada por IA |
| justificacion_final | text | NULLABLE | Justificación final aprobada |
| estado | varchar(20) | DEFAULT 'en_captura' | en_captura, en_revision, observado, aprobado, vencido |
| historial_observaciones | jsonb | DEFAULT '[]' | Historial de observaciones |
| congelado_at | timestamp | NULLABLE | Fecha en que se congeló el avance |
| capturado_por | bigint | FK → users, NULLABLE | Capturista |

**Restricción única:** `(meta_periodo_id, indicador_id)`

**Índices:** `estado`, `indicador_id`

### `avance_variables`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| indicador_variable_id | bigint | FK → indicador_variables |
| valor | decimal(12,4) | Valor reportado |
| valor_acumulado | decimal(12,4) | Valor acumulado (nullable) |

**Restricción única:** `(avance_id, indicador_variable_id)`

### `avance_evidencias`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| nombre_archivo | varchar | Nombre original del archivo subido |
| ruta_archivo | varchar | Ruta en storage |
| mime_type | varchar(50) | Tipo MIME |
| tamano_bytes | bigint unsigned | Tamaño en bytes |
| hash_archivo | varchar(64) | Hash SHA-256 del archivo |
| nombre_documento | varchar | Nombre descriptivo |
| area_generadora | varchar | Área que genera (nullable) |
| fecha_documento | date | Fecha del documento (nullable) |
| subido_por | bigint | FK → users |

### `desbloqueos`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| avance_id | bigint | FK → avances |
| motivo | text | Justificación de la solicitud |
| solicitado_por | bigint | FK → users |
| resuelto_por | bigint | FK → users (nullable) |
| estado | varchar(20) | DEFAULT 'pendiente' — pendiente, aprobado, rechazado |
| resolucion | text | Texto de resolución (nullable) |
| resuelto_at | timestamp | Fecha de resolución (nullable) |

**Índice:** `(avance_id, estado)`

### `notifications` (Laravel)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | uuid | PK |
| type | varchar | Clase de notificación |
| notifiable_type | varchar | Modelo notificable |
| notifiable_id | bigint | ID del notificable |
| data | text | Datos de la notificación |
| read_at | timestamp | Fecha de lectura (nullable) |

---

## 9. Dominio: Evaluación

### `evaluaciones_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK | Programa |
| ejercicio_fiscal | smallint | NOT NULL | Año fiscal |
| indice_eficacia | decimal(8,4) | NULLABLE | Índice calculado |
| desglose_niveles | jsonb | NULLABLE | Desglose por nivel MIR |
| conteo_semaforos | jsonb | NULLABLE | Conteo de semáforos |
| indicadores_evaluados | integer unsigned | DEFAULT 0 | Indicadores evaluados |
| indicadores_no_evaluados | integer unsigned | DEFAULT 0 | Indicadores sin evaluar |
| configuracion_calculo | jsonb | NULLABLE | Configuración del cálculo |
| analisis_ia | text | NULLABLE | Análisis generado por IA |
| calculado_por | bigint | FK → users, NULLABLE | Usuario que calculó |

**Restricción única:** `(programa_presupuestario_id, ejercicio_fiscal)`

**Traits:** LogsActivity

### `anexos_transversales`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| nombre | varchar | Nombre del anexo |
| clave | varchar(30) | UNIQUE — Clave identificadora |
| descripcion | text | Descripción (nullable) |
| activo | boolean | DEFAULT true |
| orden | smallint | DEFAULT 0 |

### `indicador_anexo_transversal` (pivot)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| indicador_id | bigint | FK |
| anexo_transversal_id | bigint | FK |

**Restricción única:** `(indicador_id, anexo_transversal_id)`

**Nota:** Incluye timestamps.

---

## 10. Dominio: Poblaciones

### `poblaciones_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_id | bigint | FK → programa_presupuestarios | Programa |
| unidad_medida | varchar(100) | NOT NULL | Unidad de medida de la población |
| referencia_cantidad | integer unsigned | NOT NULL | Cantidad de población de referencia |
| referencia_fuente | text | NULLABLE | Fuente de la cifra de referencia |
| potencial_cantidad | integer unsigned | NOT NULL | Cantidad de población potencial |
| potencial_fuente | text | NULLABLE | Fuente de la cifra potencial |
| objetivo_cantidad | integer unsigned | NOT NULL | Cantidad de población objetivo |
| objetivo_justificacion | text | NULLABLE | Justificación de la cifra objetivo |
| anio_ejercicio | smallint | NOT NULL | Año del ejercicio fiscal |

**Restricción única:** `(programa_id, anio_ejercicio)`

**CHECK constraint** (`chk_embudo_logico`): `objetivo_cantidad <= potencial_cantidad AND potencial_cantidad <= referencia_cantidad AND referencia_cantidad > 0 AND potencial_cantidad > 0 AND objetivo_cantidad > 0`

---

## 11. Dominio: Importación e IA

### `importacion_reportes`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| programa_presupuestario_id | bigint | FK (nullable) |
| team_id | bigint | FK → teams |
| archivo_original | varchar | Nombre del archivo subido |
| formato | varchar(10) | md, csv, xlsx |
| datos_parseados | jsonb | Datos parseados |
| diagnostico | jsonb | Diagnóstico de la importación (nullable) |
| estado | varchar(20) | DEFAULT 'pendiente' — pendiente, procesado, descartado |
| created_by | bigint | FK → users |

### `llm_logs`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| user_id | bigint | FK → users (nullable) |
| method | varchar(30) | Método/operación invocada |
| prompt_template | varchar | Nombre del template de prompt (nullable) |
| prompt_version | varchar | Versión del prompt (nullable) |
| prompt_text | text | Prompt enviado |
| response_text | text | Respuesta recibida (nullable) |
| prompt_tokens | integer unsigned | Tokens de entrada (nullable) |
| completion_tokens | integer unsigned | Tokens de salida (nullable) |
| total_tokens | integer unsigned | Tokens totales (nullable) |
| duration_ms | integer unsigned | Duración en ms (nullable) |
| cost_usd | decimal(10,6) | Costo en USD (nullable) |
| model | varchar | Modelo usado (nullable) |
| status | varchar(20) | DEFAULT 'pending' — Estado de la llamada |
| error_message | text | Mensaje de error (nullable) |

**Índices:** `user_id`, `status`, `created_at`

### `llm_budgets`

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| scope | varchar(10) | global, team, user |
| scope_id | bigint unsigned | team_id o user_id (nullable) |
| month | date | Primer día del mes |
| budget_usd | decimal(10,2) | Presupuesto mensual en USD |
| spent_usd | decimal(10,6) | DEFAULT 0 — Monto consumido |
| alert_threshold | decimal(3,2) | DEFAULT 0.80 — Umbral de alerta |
| alerted_at | timestamp | Fecha de la última alerta (nullable) |

**Restricción única:** `(scope, scope_id, month)`

**Índice:** `month`

---

## 12. Dominio: Presupuesto

### `partidas_presupuestales`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| clave_partida | varchar(20) | NOT NULL | Clave presupuestal |
| descripcion | varchar(255) | NOT NULL | Descripción de la partida |
| monto_aprobado | decimal(15,2) | NOT NULL | Monto aprobado |
| monto_modificado | decimal(15,2) | NULLABLE | Monto modificado |
| ejercicio_fiscal | smallint | NOT NULL | Año fiscal |
| team_id | bigint | FK → teams | Equipo |
| registrado_por | bigint | FK → users, NULLABLE | Usuario que registró |

**Restricción única:** `(programa_presupuestario_id, clave_partida, ejercicio_fiscal)`
**Índice:** `(programa_presupuestario_id, ejercicio_fiscal)`
**Traits:** LogsActivity

### `avances_financieros`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| partida_presupuestal_id | bigint | FK → partidas_presupuestales | Partida |
| trimestre | smallint | NOT NULL | Trimestre (1-4) |
| monto_comprometido | decimal(15,2) | DEFAULT 0 | Monto comprometido |
| monto_devengado | decimal(15,2) | DEFAULT 0 | Monto devengado |
| monto_pagado | decimal(15,2) | DEFAULT 0 | Monto pagado |
| registrado_por | bigint | FK → users | Usuario que registró |
| observaciones | text | NULLABLE | Observaciones |

**Restricción única:** `(partida_presupuestal_id, trimestre)`
**CHECK constraints:**
- `chk_trimestre_valido`: `trimestre BETWEEN 1 AND 4`
- `chk_pagado_le_devengado`: `monto_pagado <= monto_devengado`
- `chk_devengado_le_comprometido`: `monto_devengado <= monto_comprometido`
**Traits:** LogsActivity

### `metas_gasto_trimestral`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| partida_presupuestal_id | bigint | FK → partidas_presupuestales | Partida |
| trimestre | smallint | NOT NULL | Trimestre (1-4) |
| monto_programado | decimal(15,2) | NOT NULL | Monto programado |
| justificacion | text | NULLABLE | Justificación |

**Restricción única:** `(partida_presupuestal_id, trimestre)`
**CHECK constraints:**
- `chk_meta_trimestre`: `trimestre BETWEEN 1 AND 4`
- `chk_meta_positiva`: `monto_programado >= 0`

---

## 13. Dominio: Jurídico

### `catalogo_ordenamientos`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| nombre | varchar(255) | NOT NULL | Nombre del ordenamiento |
| nivel_jerarquia | varchar(30) | NOT NULL | constitucional, federal, estatal, reglamentario, operativo |
| abreviatura | varchar(50) | NULLABLE | Abreviatura |
| activo | boolean | DEFAULT true | Activo |
| orden | smallint | DEFAULT 0 | Posición |

**Índice:** `nivel_jerarquia`

### `sustento_legal_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| tipo | varchar(30) | NOT NULL | facultad_ur, mandato_gasto, regla_operacion, otro |
| catalogo_ordenamiento_id | bigint | FK → catalogo_ordenamientos, NULLABLE | Ordenamiento del catálogo |
| ordenamiento | varchar(255) | NOT NULL | Nombre del ordenamiento legal |
| articulo | varchar(100) | NULLABLE | Artículo(s) citado(s) |
| descripcion | text | NULLABLE | Descripción |
| nivel_jerarquia | varchar(30) | NOT NULL | Nivel en jerarquía legal |
| vigente | boolean | DEFAULT true | Vigente |
| registrado_por | bigint | FK → users | Usuario que registró |
| validado_por | bigint | FK → users, NULLABLE | Usuario que validó |
| validado_at | timestamp | NULLABLE | Fecha de validación |
| team_id | bigint | FK → teams | Equipo |

**Índices:** `(programa_presupuestario_id, tipo)`, `team_id`
**Traits:** LogsActivity

### `documentos_normativos`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| tipo_documento | varchar(30) | NOT NULL | reglas_operacion, periodico_oficial, reglamento_interior, ley_organica, otro |
| nombre | varchar(255) | NOT NULL | Nombre del documento |
| archivo_path | varchar(500) | NOT NULL | Ruta en storage |
| archivo_hash | varchar(64) | NULLABLE | Hash SHA-256 |
| archivo_size | integer | NULLABLE | Tamaño en bytes |
| fecha_publicacion | date | NULLABLE | Fecha de publicación |
| fecha_vigencia | date | NULLABLE | Fecha de vigencia |
| verificado | boolean | DEFAULT false | Verificado |
| verificado_por | bigint | FK → users, NULLABLE | Verificador |
| verificado_at | timestamp | NULLABLE | Fecha de verificación |
| registrado_por | bigint | FK → users | Quien subió |
| team_id | bigint | FK → teams | Equipo |

**Índice:** `(programa_presupuestario_id, tipo_documento)`
**Traits:** LogsActivity

### `validacion_juridica_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| ejercicio_fiscal | smallint | NOT NULL | Año fiscal |
| estado | varchar(20) | DEFAULT 'pendiente' | pendiente, en_revision, validado, rechazado, vencido |
| tiene_facultad_ur | boolean | DEFAULT false | ¿Tiene facultad de la UR? |
| tiene_mandato_gasto | boolean | DEFAULT false | ¿Tiene mandato de gasto? |
| tiene_rop | boolean | NULLABLE | ¿Tiene ROP publicadas? (null si no aplica) |
| observaciones | text | NULLABLE | Observaciones del validador |
| validado_por | bigint | FK → users, NULLABLE | Validador |
| validado_at | timestamp | NULLABLE | Fecha de validación |

**Restricción única:** `(programa_presupuestario_id, ejercicio_fiscal)`

---

## 14. Dominio: Validación Tripartita

### `estado_validacion_programa`

| Columna | Tipo | Restricción | Descripción |
|---------|------|-------------|-------------|
| id | bigint | PK | Identificador |
| programa_presupuestario_id | bigint | FK → programa_presupuestarios | Programa |
| ejercicio_fiscal | smallint | NOT NULL | Año fiscal |
| planeacion_estado | varchar(20) | DEFAULT 'incompleta' | Estado de planeación |
| planeacion_detalle | jsonb | NULLABLE | Detalle de planeación |
| planeacion_actualizado_at | timestamp | NULLABLE | Última actualización |
| juridico_estado | varchar(20) | DEFAULT 'no_implementado' | Estado jurídico |
| juridico_detalle | jsonb | NULLABLE | Detalle jurídico |
| juridico_actualizado_at | timestamp | NULLABLE | Última actualización |
| financiero_estado | varchar(20) | DEFAULT 'sin_partidas' | Estado financiero |
| financiero_detalle | jsonb | NULLABLE | Detalle financiero |
| financiero_actualizado_at | timestamp | NULLABLE | Última actualización |
| consolidado | varchar(20) | DEFAULT 'critico' | Estado consolidado |
| validaciones_completas | smallint | DEFAULT 0 | Conteo (0-3) |

**Restricción única:** `(programa_presupuestario_id, ejercicio_fiscal)`

---

## 15. Auditoría

### `activity_log` (spatie/laravel-activitylog)

| Columna | Tipo | Descripción |
|---------|------|-------------|
| id | bigint | PK |
| log_name | varchar | Nombre del log (nullable) |
| description | text | created, updated, deleted |
| subject_type | varchar | Modelo afectado (nullable) |
| subject_id | bigint | ID del registro (nullable) |
| event | varchar | Evento (nullable) |
| causer_type | varchar | Modelo causante (nullable) |
| causer_id | bigint | ID del usuario (nullable) |
| properties | json | Atributos old/new (nullable) |
| batch_uuid | uuid | UUID de batch (nullable) |

**Índices:** `log_name`, `event`, `batch_uuid`

**Modelos auditados:** User, PedPlan, PedEje, PedTema, PedObjetivoEstrategico, MirNivel, Indicador, EvaluacionPrograma, PartidaPresupuestal, AvanceFinanciero, SustentoLegalPrograma, DocumentoNormativo, ValidacionJuridicaPrograma

---

## 16. Enums del Sistema

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
| NivelGobierno | estatal, municipal |
| EstadoValidacionJuridica | pendiente, en_revision, validado, rechazado, vencido |
| NivelJerarquiaLegal | constitucional, federal, estatal, reglamentario, operativo |
| TipoDocumentoNormativo | reglas_operacion, periodico_oficial, reglamento_interior, ley_organica, otro |
| TipoSustentoLegal | facultad_ur, mandato_gasto, regla_operacion, otro |

---

## 17. Diagrama de Relaciones (resumen)

```
PedPlan → PedEje → PedTema → PedObjetivoEstrategico → PedEstrategia → PedLineaAccion
                                        ↕ alineación                          ↕ alineación
                                    PndObjetivo ↔ OdsMeta          ProgramaDerivadoObjetivo

PedPlan → ProgramaDerivado → ProgramaDerivadoObjetivo

ProgramaPresupuestario → ProgramaTeam ↔ Team
                       → Arbol → ArbolNodo
                       → Alternativa ↔ ArbolNodo
                       → MirNivel → Indicador → MetaPeriodo → Avance → AvanceEvidencia
                                                                     → AvanceVariable
                                                             → Desbloqueo
                                   → IndicadorVariable
                                   → MedioVerificacion
                                   → CremaaValidacion
                       → MirVersion
                       → EvaluacionPrograma
                       → PoblacionPrograma
                       → PartidaPresupuestal → MetaGastoTrimestral
                                             → AvanceFinanciero
                       → SustentoLegalPrograma ↔ CatalogoOrdenamiento
                       → DocumentoNormativo
                       → ValidacionJuridicaPrograma
                       → EstadoValidacionPrograma

User → Team (Jetstream Teams)
     → Avance (capturado_por)
     → LlmLog (user_id)
     → ActivityLog (causer)
```
