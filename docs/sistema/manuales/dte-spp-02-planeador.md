# Manual de Usuario — Rol Planeador (dte-spp)

> Sistema PbR-SED de planeación de programas presupuestales.
> Este manual cubre **dte-spp** (Laravel 12: planeación / MIR / seguimiento / evaluación / transparencia). El padrón de beneficiarios y los reportes territoriales viven en **geobase** (proyecto hermano), pero el Planeador los consume desde dte-spp vía las pestañas Padrón / Cobertura y la exportación SHCP.

---

## 1. Quién es este rol y qué permisos tiene

El **Planeador** (`SystemRole::PLANEADOR`) es el rol **central de planeación**. Es responsable de construir la MIR de cada programa de principio a fin: define el problema (Wizard MML), arma la Matriz de Indicadores (MirEditor), calendariza metas, ajusta metas con justificación, registra ASM, gestiona evaluaciones externas y exporta el padrón SHCP.

### Permisos reales (confirmados en los seeders)

Del `RolesAndPermissionsSeeder` y los seeders de dominio (`PadronPermissionsSeeder`, `PresupuestoPermissionsSeeder`, `AsmPermissionsSeeder`, `EvaluacionExternaPermissionsSeeder`, `JuridicoPermissionsSeeder`):

| Permiso | Origen (seeder) | Para qué le sirve |
|---|---|---|
| `gestionar_catalogos` | RolesAndPermissions | Gestionar PED, alineación estratégica, programas derivados (módulo Cascade) |
| `crear_programa` | RolesAndPermissions | Crear programas presupuestarios |
| `editar_mir` | RolesAndPermissions | Wizard MML completo (etapas 1-7), MirEditor, clave presupuestal |
| `revisar_avance` | RolesAndPermissions | Panel de seguimiento, detalle de indicador, vencidos |
| `aprobar_avance` | RolesAndPermissions | Aprobar / observar avances en el flujo de revisión |
| `exportar_reportes` | RolesAndPermissions | Paneles de evaluación, exportaciones PDF/Excel, Anexo 11, datos abiertos |
| `ver_sabana_captura` | RolesAndPermissions | Sábana de Captura |
| `ver_concentrado_captura` | RolesAndPermissions | Concentrado de Captura |
| `ver_datasets_abiertos` | RolesAndPermissions | Índice y detalle de datasets de transparencia |
| `gestionar_dataset_abierto` | RolesAndPermissions | Crear borradores/entregas, enviar a revisión |
| `firmar_iaff` | RolesAndPermissions | Firmar IAFF (Informe de Avance Financiero-Físico) |
| `gestionar_cierre_fiscal` | RolesAndPermissions | Panel de cierre fiscal anual |
| `ver_padron` | PadronPermissions | Pestañas Padrón y Cobertura |
| `generar_snapshot_padron` | PadronPermissions | Activar/desactivar padrón en GeoBase, generar snapshots |
| `exportar_padron_shcp` | PadronPermissions | **Exportar Padrón SHCP (CURP descifrado) — exclusivo del Planeador** |
| `ver_datos_financieros` | PresupuestoPermissions | Panel presupuestal, POA, conciliación, cuenta pública (lectura) |
| `exportar_cuenta_publica` | PresupuestoPermissions | Exportar Cuenta Pública PDF/Excel, presupuesto-capítulo |
| `ver_asm` | AsmPermissions | Ver ASM |
| `gestionar_asm` | AsmPermissions | Crear / editar / eliminar ASM |
| `ver_evaluacion_externa` | EvaluacionExternaPermissions | Ver evaluaciones externas (lo tienen todos los roles) |
| `gestionar_evaluacion_externa` | EvaluacionExternaPermissions | **CRUD de evaluación externa e informe — solo Planeador y Admin** |
| `ver_sustento_legal` | JuridicoPermissions | Ver sustento legal de programas (solo lectura) |

> **No tiene** (importante): `capturar_avance` (eso es del Operador), `gestionar_presupuesto`, `capturar_avance_financiero`, `gestionar_sustento_legal`, `validar_sustento_legal`, `gestionar_reglas_operacion`, `administrar_usuarios`, `invitar_usuarios`, `aprobar_datos_abiertos` (eso es del rol RDA). Ver sección 4.

---

## 2. A qué entra al iniciar sesión y cómo navega

- **Landing**: `/dashboard` (ruta `dashboard`, componente `App\Livewire\Dashboard`). El contenido se adapta al rol: como Planeador verá KPIs de seguimiento, semáforo de indicadores, estadísticas financieras (porque tiene `ver_datos_financieros`) y notificaciones recientes. Es solo lectura.
- **Navegación por sidebar** agrupada por dominio. Los grupos relevantes para el Planeador:
  - **Planeación / Cascade**: PED, Matriz de Alineación, Programas Derivados (`/cascade/...`).
  - **MML / Programas**: listado de programas, importación MIR, Wizard MML, MirEditor (`/mml/...` y `/{programa}/etapa/...`).
  - **Seguimiento**: panel, sábana, concentrado, desviaciones (`/seguimiento/...`).
  - **Evaluación**: paneles, ASM, evaluación externa, exportaciones (`/evaluacion/...`).
  - **Transparencia**: datasets abiertos (`/transparencia/datos-abiertos`).
  - **Presupuesto** (solo lectura para el Planeador): panel, POA, conciliación, cuenta pública (`/presupuesto/...`).

Todas las rutas exigen sesión verificada (`auth:sanctum`, `verified`). Los usuarios no-admin solo ven los programas de su `currentTeam` (Unidad Responsable).

---

## 3. Tareas principales paso a paso

### Flujo A — Construir el problema con el Wizard MML (etapas 1-6)

**Objetivo**: definir el problema central, árbol de problema/objetivos, alternativa, poblaciones y alineación estratégica antes de armar la MIR.

Punto de partida: `/mml/programas` (ruta `mml.programas`, componente `ListaProgramas`) → "Crear programa" o entrar a uno existente. Permiso: `editar_mir`.

| Etapa | URL | Componente | Qué haces | Resultado |
|---|---|---|---|---|
| 1. Definición del problema | `/{programa}/etapa/1` | `DefinicionProblema` | Escribir el problema central; "Validar con IA" → aceptar sugerencia → "Guardar" | Problema central guardado |
| 2. Árbol del problema | `/{programa}/etapa/2` | `ArbolProblemaBuilder` | Agregar/editar/eliminar nodos (causas y efectos); "Sugerir causas (IA)", "Generar árbol ejemplo" | Árbol de problema |
| 3. Árbol de objetivos | `/{programa}/etapa/3` | `ArbolObjetivosBuilder` | "Transformar nodo (IA)" o "Transformar todos" (problemas → objetivos) | Árbol de objetivos |
| 4. Selección de alternativas | `/{programa}/etapa/4` | `SeleccionAlternativas` | Crear alternativa, "Evaluar con IA", "Seleccionar" | Alternativa seleccionada |
| 5. Embudo de poblaciones | `/{programa}/etapa/5` | `EmbudoPoblaciones` | Capturar referencia / potencial / objetivo + fuentes y justificación; "Guardar" | `PoblacionPrograma` del ejercicio |
| 6. Alineación estratégica | `/{programa}/etapa/6` | `AlineacionEstrategica` | "Buscar con IA" objetivos PED, seleccionar sugerencia, "Guardar", "Finalizar planeación" | Programa alineado al PED |

> El nodo central del árbol (problema/objetivo) **no se puede modificar ni eliminar** (es único por árbol). En el embudo la regla es **objetivo ≤ potencial ≤ referencia** (cada cantidad es entero ≥1).

**Alternativa por importación**: si ya tiene la MIR en archivo, use el flujo de importación en lugar del wizard manual:
`/mml/importar/nuevo` (`ImportarPrograma`) → `/mml/importar/{importacion}/completar` (`CompletarHuecos`, corregir fórmula/tipo/dimensión/frecuencia) → `/mml/importar/{importacion}/vincular` (`VincularAlineacion`) → `/mml/importar/{importacion}/calendarizar` (`CalendarizarMetas`, Confirmar = activa el programa). Dashboard de importaciones: `/mml/importar` (`DashboardImportaciones`).

---

### Flujo B — Editar la MIR (Etapa 7 / MirEditor)

**Objetivo**: construir los 4 niveles MIR (Fin, Propósito, Componentes, Actividades) con sus indicadores, medios de verificación, supuestos y semáforo.

- **URL**: `/{programa}/etapa/7/mir` (ruta `mml.mir`)
- **Componente**: `App\Livewire\Mml\MirEditor` (`mir-editor.blade.php`, filas en `mir-nivel-row.blade.php`)
- **Permiso**: `editar_mir`

#### B.1 Niveles MIR
- Agregar/editar/eliminar Fin, Propósito, Componentes y Actividades (anidadas bajo Componentes).
- **Resumen narrativo** por nivel: textarea con "Validar sintaxis" (IA, reglas SHCP por tipo de nivel). Badges "Sintaxis OK" / "Sintaxis: revisar".
- Eliminar un nivel pide confirmación (`wire:confirm`).

#### B.2 Indicador (dentro de cada nivel)
1. Capturar Nombre, Tipo (Proceso/Eficacia/Impacto según nivel), Dimensión (Cantidad/Calidad/Tiempo), Frecuencia.
2. **Fórmula** textual; botón para **extraer variables** (IA) y definir variables (símbolo A/B/C, nombre, fuente, unidad).
3. Año de línea base.
4. **Meta** (ver Flujo C — exige justificación si cambia).
5. **Semáforo** de 4 rangos (ver Flujo C).
6. **CREMAA**: botón "Validar CREMAA" (IA, 6 atributos: Claro, Relevante, Económico, Monitoreable, Adecuado, Aporte Marginal); revisar badges y "Ver detalles CREMAA".

#### B.3 Medio de Verificación (dentro de cada indicador)
Inputs: nombre del MV, "Organismo (opcional)", "URL (opcional)", select **Frecuencia**, select **Tipo de fuente** (Fuente externa / Registro administrativo propio / Evaluación externa). Botón "Validar CREMA" (IA, 5 criterios: Confiable/Relevante/Económico/Monitoreable/Asequible) + edición manual.
- **Regla dura B9**: si el indicador es de **FIN o PROPÓSITO**, el tipo de fuente debe ser **externa**, o el guardado falla (`tipo_fuente_mv_{id}`).
- **Regla dura B7**: la frecuencia del MV no puede ser menos frecuente que la del indicador (`frecuencia_mv_{id}`).

#### B.4 Supuestos estructurados (dentro de cada nivel)
"+ Supuesto" (`agregarSupuesto`) → descripción + 3 checkboxes (**Externo**, **Relevante**, **Probable**). En modo lectura aparece badge **"Válido"** (verde, si los 3 = true) o **"Incompleto"** (ámbar). Reemplaza al texto libre legacy.

#### B.5 Alineación, snapshots y validación
- **Alineación estratégica** (modo edición): "Buscar alineación" con búsqueda semántica (Fin/Propósito → Objetivo Estratégico PED; Componente/Actividad → Línea de Acción) con score de similitud.
- **Snapshots / versiones**: "Crear snapshot" etiquetado (captura el estado completo de la MIR); toggle "Ver versiones" lista los snapshots; "Restaurar versión" (confirmación, destructivo).
- **Validar MIR completa**: ejecuta `MirLogicaValidacionService` → tabla de hallazgos (error_crítico rojo / advertencia amarillo / sugerencia azul) con nivel involucrado y mensaje.

#### B.6 Clave presupuestal (complementaria)
`/{programa}/clave-presupuestal` (ruta `mml.clave-presupuestal`, componente `ClavePresupuestalEditor`, permiso `editar_mir`): segmentar la clave SEFIP de 32 caracteres (administrativa / programática / funcional CONAC en cascada), con vista previa de la clave canónica.

---

### Flujo C — Calendarización de metas y revisiones de meta

**Objetivo**: distribuir las metas por periodo y, si cambia una meta ya calendarizada, dejar audit trail.

- **Calendarización inicial** (durante importación): `/mml/importar/{importacion}/calendarizar` (`CalendarizarMetas`). Ajustar metas por periodo y "Confirmar" → `CalendarizacionService::confirmar()` puebla `fecha_apertura`/`fecha_cierre` de cada `MetaPeriodo`. La frecuencia del indicador define qué periodos generan meta (un indicador anual solo genera periodo anual).
- **Captura de la Meta y revisión** (MirEditor, Flujo B.2 → campo "Meta"):
  1. Capturar o cambiar el valor de **Meta** en la fila del indicador.
  2. Si modifica una meta **previa**, el sistema **exige una justificación ≥ 10 caracteres** antes de guardar.
  3. Al guardar (`guardarMeta()`), el cambio queda registrado en `revisiones_meta` (modelo `RevisionMeta`) como audit trail. Import y snapshots están exentos.
- **Ventana de captura normativa**: el cierre de cada periodo + `config('tracking.dias_ventana_captura', 30)` días define la ventana en que el Operador puede capturar. Re-confirmar la calendarización recalcula esas fechas.

---

### Flujo D — Seguimiento y revisión de avances

**Objetivo**: monitorear el avance físico capturado por los operadores y aprobar/observar.

| Tarea | URL | Componente | Permiso |
|---|---|---|---|
| Panel de seguimiento (semáforos, filtros, gráficos) | `/seguimiento` | `PanelSeguimiento` | `revisar_avance` |
| Detalle de un indicador (histórico) | `/seguimiento/indicador/{id}` | `DetalleIndicador` | `revisar_avance` |
| Avances vencidos del team | `/seguimiento/vencidos` | `IndicadoresVencidos` | `revisar_avance` |
| **Flujo de revisión** (aprobar / observar) | `/seguimiento/flujo/{avance_id}` | `FlujosAvance` | `aprobar_avance` para aprobar/observar |
| Sábana de Captura (PDF/Excel) | `/seguimiento/sabana-captura` | `SabanaCaptura` | `ver_sabana_captura` |
| Concentrado de Captura (PDF/Excel) | `/seguimiento/concentrado-captura` | `ConcentradoCaptura` | `ver_concentrado_captura` |

En `FlujosAvance` puede **Aprobar** un avance EN_REVISION o **Observar** (devolverlo con un textarea de observación, mínimo requerido). El estado fluye EN_CAPTURA → EN_REVISION → APROBADO/OBSERVADO. El Planeador **no captura** avances (ese paso es del Operador) ni puede "enviar a revisión"/"corregir" (acciones de `capturar_avance`).

---

### Flujo E — ASM (Aspectos Susceptibles de Mejora)

**Objetivo**: registrar y dar seguimiento a compromisos de mejora, idealmente vinculados a una recomendación de evaluación externa.

| Tarea | URL | Componente | Permiso |
|---|---|---|---|
| Índice (filtros + KPIs) | `/evaluacion/asms` | `AsmIndex` | `ver_asm` |
| Crear ASM | `/evaluacion/asms/crear` | `AsmForm` | `gestionar_asm` |
| Editar ASM | `/evaluacion/asms/{asm}/editar` | `AsmForm` | `gestionar_asm` |
| Ver detalle | `/evaluacion/asms/{asm}` | `AsmShow` | `ver_asm` |
| Eliminar | DELETE `/evaluacion/asms/{asm}` | `AsmIndex` | `gestionar_asm` |
| Exportar XLSX | `/evaluacion/asms/exportar/xlsx` | `AsmXlsxExportController` | `exportar_reportes` |

En el formulario (`AsmForm`): seleccionar programa, **recomendación de origen** (opcional — los selects de recomendaciones se filtran por programa), descripción del aspecto, acción de mejora, tipo de plazo, fecha de compromiso, responsable, observaciones. La cadena normativa es **Hallazgo → Recomendación → ASM** (`asms.recomendacion_id` nullable; un ASM sin recomendación es estado válido).

---

### Flujo F — Evaluación externa estructurada

**Objetivo**: registrar evaluaciones externas y capturar su informe (6 secciones del temario M10), incluyendo hallazgos y recomendaciones que alimentan los ASM.

| Tarea | URL | Componente | Permiso |
|---|---|---|---|
| Índice (filtros programa/tipo/ejercicio) | `/evaluacion/externas` | `EvaluacionExternaIndex` | `ver_evaluacion_externa` |
| Crear evaluación | `/evaluacion/externas/crear` | `EvaluacionExternaForm` | `gestionar_evaluacion_externa` |
| Editar evaluación | `/evaluacion/externas/{evaluacionExterna}/editar` | `EvaluacionExternaForm` | `gestionar_evaluacion_externa` |
| Editor del informe (6 secciones) | `/evaluacion/externas/{evaluacionExterna}` | `InformeEvaluacionEditor` | `gestionar_evaluacion_externa` (editar) / `ver_evaluacion_externa` (lectura) |

- **Crear** una evaluación (programa, ejercicio fiscal, fechas, tipo, evaluador, estado) **auto-crea** un `InformeEvaluacion` 1:1.
- En el **editor del informe**: 4 secciones de texto (Resumen ejecutivo, Metodología, Conclusiones, Fichas) que se guardan inline (`guardarSeccion`), más:
  - **Hallazgos** (`agregarHallazgo`): descripción ≥10 ch, severidad (alta/media/baja), evidencia_url (url o vacío). Eliminar un hallazgo desvincula sus ASM (la confirmación lo advierte).
  - **Recomendaciones** anidadas por hallazgo (`agregarRecomendacion`): descripción ≥10 ch, prioridad (alta/media/baja). Cada recomendación muestra cuántos ASM tiene asociados.

---

### Flujo G — Padrón y Cobertura (consumo de geobase)

**Objetivo**: vincular el padrón del programa a GeoBase, generar snapshots y consultar cobertura territorial.

| Tarea | URL | Componente | Permiso |
|---|---|---|---|
| Padrón (KPIs, snapshots, vivo/histórico) | `/{programa}/padron` | `PadronPrograma` | `ver_padron` |
| Cobertura (choropleth, alertas, supuestos) | `/{programa}/cobertura` | `CoberturaPrograma` | `ver_padron` |
| Mapa de cobertura (PNG) | `/{programa}/cobertura/mapa.png` | `MapaCoberturaProgramaController` | `ver_padron` |

En la pestaña Padrón el Planeador puede (todo gated por `generar_snapshot_padron`):
- **Activar padrón en GeoBase** (`activarPadron`), **Desactivar** (`desactivarPadron`, con modal de confirmación `desactivar-padron-{id}`), **Generar snapshot del trimestre** (`generarSnapshot`).
- Seleccionar componente, alternar **fuente vivo/snapshot** (`toggleFuente`), seleccionar un snapshot histórico.

> Si una activación reciente no aparece (404 / "Ver en vivo" vacío), suele ser por el worker de cola async; el comando idempotente `sail artisan geobase:hydrate-padron` re-hidrata (esto es operación de despliegue, no del usuario).

---

### Flujo H — Exportaciones y transparencia

| Tarea | URL/Ruta | Permiso |
|---|---|---|
| **Exportar Padrón SHCP** (CURP descifrado) | `/evaluacion/exportar/padron-shcp/{programa}` (`evaluation.padron-shcp`) | `exportar_padron_shcp` |
| Exportar Anexo 11 | `/{programa}/anexo-11` (`evaluation.anexo-11`) | `exportar_reportes` |
| Exportar reportes (MIR, ficha, avance, anual, transversal, FMyE) PDF/Excel | `/evaluacion/exportar/...` (`evaluation.exportar.*`) | `exportar_reportes` |
| Datos abiertos (CSV/JSON/ZIP/diccionario) | `/evaluacion/datos-abiertos/...` | `exportar_reportes` |
| Cuenta pública PDF/Excel | `/presupuesto/exportar/{pdf,excel}/{ejercicio}` | `exportar_cuenta_publica` |
| Datasets abiertos (transparencia) | `/transparencia/datos-abiertos` (`Index`/`Show`/`CrearEntrega`) | `ver_datasets_abiertos` + `gestionar_dataset_abierto` |

En transparencia el Planeador puede crear borradores, **crear entregas** (clonar plantilla por periodo) y **enviar a revisión**, pero **no aprobar/publicar/rechazar/retirar** ni editar plantillas (eso es del rol RDA).

---

## 4. Qué NO puede hacer (acciones bloqueadas o no visibles)

- **Capturar avances físicos**: no tiene `capturar_avance`. En `FlujosAvance` no verá "enviar a revisión" ni "corregir"; en `/seguimiento/captura/{avance_id}` no es su flujo. Esa es responsabilidad del **Operador**.
- **Gestionar presupuesto**: solo lectura financiera. No verá Nueva/Editar Partida, Adecuaciones, Importar Presupuesto ni Captura de Avance Financiero (faltan `gestionar_presupuesto` y `capturar_avance_financiero`). Sí ve POA, conciliación y panel presupuestal.
- **Jurídico**: solo `ver_sustento_legal` (lectura). No puede agregar/editar fundamentos, subir documentos normativos ni validar jurídicamente (faltan `gestionar_sustento_legal`, `validar_sustento_legal`, `gestionar_reglas_operacion`).
- **Aprobar/publicar/retirar datasets abiertos** ni **editar plantillas**: falta `aprobar_datos_abiertos` (exclusivo del rol RDA). Verá los datasets y podrá crear borradores/entregas, pero no las acciones de aprobación/publicación.
- **Administración**: no puede invitar/gestionar usuarios (`invitar_usuarios`/`administrar_usuarios`), ni acceder a Monitoreo de IA, Auditoría del sistema o Gestionar Desbloqueos (`administrar_usuarios`).
- **Evaluación externa de otros operadores**: puede gestionarla (es Planeador), pero el **Operador NO** — relevante si delega.

---

## 5. Errores y validaciones comunes (reglas duras)

Reglas que el Planeador encontrará al guardar, propias de su rol:

**MirEditor — Semáforo (sección "Semáforo (rangos)"):** valida `IndicadorReglasService::validarRangosSemaforo` al guardar:
- **B3 (C-066)**: la **meta debe caer dentro del rango Verde** (`meta < verde_min` o `meta > verde_max` → error). También aparece como advertencia no bloqueante al guardar la meta.
- **B4 (C-067)**: **sin solapamiento** entre rangos (min ≤ max por color; bordes contiguos válidos, intersección con longitud >0 = error).
- **B5 (C-068)**: si la unidad es **Porcentaje (PCT)**, todos los límites deben estar en **[0,100]**.
- **B6 (C-069)**: el **rango Rojo no puede iniciar en 0** (`rango_rojo_min === 0` → error).
- Recuerde: el **4.º rango es "Rojo alto"** (sobrecumplimiento, color púrpura) — señal de mala planeación, no un valor opcional.

**MirEditor — Medios de Verificación:**
- **B9 (C-073)**: MV de **FIN/PROPÓSITO** exige **tipo_fuente = externa** (error `tipo_fuente_mv_{id}` al guardar).
- **B7**: la frecuencia del MV debe ser **al menos tan frecuente** como la medición del indicador (error `frecuencia_mv_{id}`).

**MirEditor — Meta:**
- Cambiar una **meta ya existente** exige **justificación ≥ 10 caracteres**; sin ella, el guardado falla. El cambio se audita en `revisiones_meta`.

**Wizard MML:**
- El **nodo central** del árbol de problema/objetivos no se edita ni elimina.
- **Embudo de poblaciones**: debe cumplirse **objetivo ≤ potencial ≤ referencia**; cada cantidad es entero ≥1.

**Flujo de avances:**
- **Observar** un avance requiere capturar una observación (textarea no vacío).
- Un avance **congelado** (aprobado) no se reabre desde el flujo normal: requiere una **solicitud de desbloqueo** que resuelve un administrador (`administrar_usuarios`), no el Planeador.

**Evaluación externa:**
- Hallazgo y Recomendación exigen **descripción ≥ 10 ch**; `evidencia_url` debe ser URL válida o quedar vacía.
- Eliminar un hallazgo **desvincula** sus ASM (no los borra) — la confirmación lo advierte.
