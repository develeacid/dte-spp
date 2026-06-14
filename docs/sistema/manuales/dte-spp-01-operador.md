# Manual de Usuario — Rol Operador (dte-spp)

> Sistema PbR-SED de planeación de programas presupuestales. Este manual cubre el rol **Operador** en el sistema **dte-spp**. El Operador NO opera en geobase.

---

## 1. Quién es este rol y qué permisos tiene

El **Operador** es el rol de **captura** del sistema: es la persona de la Unidad Responsable (UR) que registra el **avance físico trimestral** de los indicadores (mete los valores observados de cada variable, el sistema calcula el resultado), adjunta evidencias, genera snapshots del padrón y consulta los tableros de seguimiento. Es el **rol de menor privilegio operativo**: no planea (no construye MIR), no aprueba avances, no gestiona presupuesto ni datasets de transparencia ni evaluación externa.

### Permisos reales (confirmados en seeders)

Del seeder base `RolesAndPermissionsSeeder` y de los seeders de dominio `PadronPermissionsSeeder` y `AsmPermissionsSeeder`, el rol `operador` recibe exactamente:

| Permiso | Origen (seeder) | Para qué le sirve |
|---|---|---|
| `capturar_avance` | RolesAndPermissions | Capturar avance físico, enviar a revisión, corregir avances observados |
| `exportar_reportes` | RolesAndPermissions | Ver paneles de Reportes y descargar PDF/Excel; exportar Anexo 11 |
| `ver_sabana_captura` | RolesAndPermissions | Ver la Sábana de Captura (ventana/vencimientos) |
| `ver_concentrado_captura` | RolesAndPermissions | Ver el Concentrado de Captura (resumen por estado) |
| `ver_padron` | PadronPermissions | Consultar padrón y cobertura del programa |
| `generar_snapshot_padron` | PadronPermissions | Activar/desactivar padrón y generar snapshots del trimestre |
| `ver_asm` | AsmPermissions | Lectura amplia de ASM (todos los roles lo reciben) |
| `gestionar_asm` | AsmPermissions | Crear/editar/eliminar ASM (planeador, operador y admin) |
| `ver_evaluacion_externa` | EvaluacionExternaPermissions | Lectura amplia de evaluaciones externas (solo lectura) |

> **Importante (segregación de funciones):** el Operador **NO** tiene `revisar_avance` ni `aprobar_avance` (no aprueba lo que captura), **NO** tiene `editar_mir`/`crear_programa` (no construye la MIR), **NO** tiene `gestionar_evaluacion_externa`, **NO** tiene `exportar_padron_shcp`, ni permisos de presupuesto, jurídico o transparencia. (Ver sección 4.)

---

## 2. A qué entra al iniciar sesión y cómo navega

### Landing
Tras autenticarse, el Operador cae en **Inicio** (`/dashboard`, ruta `dashboard`, componente `Dashboard`). Es el punto de partida para ir a "Mis Indicadores".

### Sidebar (lo que SÍ ve)
La barra lateral (`components/layout/sidebar-nav.blade.php`) muestra grupos según permisos. Con el set del Operador verá:

- **Inicio** — `/dashboard`.
- **Seguimiento** — grupo visible. Dentro, por sus permisos, ve:
  - **Mis Indicadores** → `/seguimiento/pendientes` (gated por `capturar_avance`).
  - **Sábana de Captura** → `/seguimiento/sabana-captura` (gated por `ver_sabana_captura`).
  - **Concentrado** → `/seguimiento/concentrado-captura` (gated por `ver_concentrado_captura`).
  - *(NO ve "Panel" ni "Vencidos": ambos requieren `revisar_avance`.)*
- **Reportes** — grupo visible (tiene `exportar_reportes`, `ver_asm`, `ver_evaluacion_externa`). Dentro ve:
  - **Transversal**, **Desviaciones**, **Acumulado Anual**, **Datos Abiertos** (por `exportar_reportes`).
  - **ASMs** (por `ver_asm`).
  - **Evaluaciones externas** (por `ver_evaluacion_externa`, solo lectura).

### Lo que NO aparece en su sidebar
**Planeación**, **Presupuesto**, **Jurídico**, **Catálogos**, **Transparencia** y **Administración** no se renderizan para el Operador (no tiene ninguno de los permisos que activan esos grupos).

### Navegación a Padrón / Cobertura / Captura
Padrón, Cobertura y la pantalla de Captura **no tienen ítem propio en el sidebar**: se llega a ellos desde el flujo de seguimiento o por URL del programa (`/{programa}/padron`, `/{programa}/cobertura`). El acceso a Captura se hace desde "Mis Indicadores" o desde el Detalle de Indicador.

---

## 3. Tareas principales paso a paso

### FLUJO A — Captura de avance físico trimestral (tarea central del rol)

#### A.1 Encontrar lo que me toca capturar
- **Objetivo:** ver mis avances pendientes de entrega.
- **Ruta/URL:** `/seguimiento/pendientes` — ruta `tracking.pendientes`.
- **Vista/Componente:** `MisIndicadoresPendientes` / `mis-indicadores-pendientes.blade.php`.
- **Qué hace:** lista los avances en estado **EN_CAPTURA** asignados a mí (`auth()->id()`), con filtro por urgencia (≤3 días, 4–7 días, >7 días para el cierre de ventana).
- **Botones:** acceso directo a la captura de cada avance.
- **Resultado:** llego a la pantalla de captura del avance elegido.

#### A.2 Capturar el avance
- **Objetivo:** registrar el valor observado del periodo y que el sistema calcule el resultado.
- **Ruta/URL:** `/seguimiento/captura/{avance_id}` — ruta `tracking.captura`.
- **Vista/Componente:** `CapturaAvance` / `captura-avance.blade.php`.
- **Pasos:**
  1. **Ingresar las variables** de la fórmula (A, B, C…). El sistema **calcula el resultado** aplicando la fórmula del indicador.
  2. *(Si el indicador es de Componente y el programa tiene padrón vinculado)* usar **Sincronizar variables desde GeoBase** para traer el numerador del padrón.
  3. El sistema determina el **semáforo** (verde / amarillo / rojo / rojo alto) con `SemaforoService`.
  4. **Si el semáforo es amarillo, rojo o rojo alto**, completar el **análisis de desviación**: los 4 campos `dato`, `causa`, `acción`, `proyección` (cada uno **obligatorio, mínimo 5 caracteres**). Botón **"Generar justificación IA"** pre-llena la "causa" (editable).
  5. **Guardar** (con `wire:confirm`).
- **Resultado:** se persiste el resultado, el semáforo y `analisis_desviacion` (JSONB). El avance sigue **EN_CAPTURA** hasta que lo envíe a revisión (flujo A.4).

#### A.3 Adjuntar evidencias del avance
- **Objetivo:** subir documentos probatorios del avance.
- **Ruta/URL:** `/seguimiento/avance/{avance_id}/evidencias` — ruta `tracking.evidencia.index`.
- **Vista/Componente:** `EvidenciaAvance` / `evidencia-avance.blade.php`.
- **Pasos:** subir archivo (PDF, Excel, imágenes, documentos, **máx. 10 MB**), registrar **nombre_documento** y **área_generadora**; puedo eliminar una evidencia mientras el avance sea editable.
- **Descargar:** `/seguimiento/evidencia/{evidencia_id}/download` (ruta `tracking.evidencia.download`).
- **Resultado:** la evidencia queda asociada al avance.

#### A.4 Enviar a revisión (y corregir si me observan)
- **Objetivo:** mover el avance por el flujo de aprobación.
- **Ruta/URL:** `/seguimiento/flujo/{avance_id}` — ruta `tracking.flujo`.
- **Vista/Componente:** `FlujosAvance` / `flujos-avance.blade.php`.
- **Lo que YO puedo hacer (con `capturar_avance`):**
  - **Enviar a revisión:** EN_CAPTURA → EN_REVISION (modal de confirmación).
  - **Corregir:** si el revisor lo dejó **OBSERVADO**, devolverlo a EN_CAPTURA para ajustar y reenviar.
- **Lo que NO puedo hacer aquí:** **Aprobar** y **Observar** requieren `revisar_avance` (revisor); no veré/podré ejecutar esas acciones.
- **Resultado:** el avance avanza a revisión o regresa a captura para corrección.

#### A.5 Consultar histórico de un indicador
- **Objetivo:** ver el histórico de avances de un indicador.
- **Ruta/URL:** `/seguimiento/indicador/{id}` — ruta `tracking.indicador.detalle`.
- **Vista/Componente:** `DetalleIndicador` / `detalle-indicador.blade.php`.
- **Resultado:** timeline por ejercicio/periodo; acceso al detalle de cada avance.

---

### FLUJO B — Visibilidad de la ventana de captura

#### B.1 Sábana de Captura
- **Objetivo:** ver el estado de cada meta/periodo y cuántos días faltan para el cierre de la ventana.
- **Ruta/URL:** `/seguimiento/sabana-captura` — ruta `tracking.sabana-captura` (gate `ver_sabana_captura`).
- **Vista/Componente:** `SabanaCaptura` / `sabana-captura.blade.php`.
- **Acciones:** filtrar por programa/nivel/periodo/estado; **exportar a PDF y Excel**.
- **Resultado:** identifico qué me vence y priorizo capturas.

#### B.2 Concentrado de Captura
- **Objetivo:** resumen por programa e indicador con conteos por estado (aprobados / en revisión / en captura / observados).
- **Ruta/URL:** `/seguimiento/concentrado-captura` — ruta `tracking.concentrado-captura` (gate `ver_concentrado_captura`).
- **Vista/Componente:** `ConcentradoCaptura` / `concentrado-captura.blade.php`.
- **Acciones:** filtrar, **exportar PDF/Excel**.

#### B.3 Dashboard de indicadores del programa
- **Objetivo:** ver la MIR del programa con metas, avances y semáforo.
- **Ruta/URL:** `/seguimiento/{programa}/dashboard-indicadores` — ruta `tracking.dashboard-indicadores`.
- **Vista/Componente:** `DashboardIndicadores` / `dashboard-indicadores.blade.php` (sin gate explícito).
- **Acciones:** expandir/colapsar Fin/Propósito/Componentes, ver detalle por nivel.

---

### FLUJO C — Padrón y Cobertura (consulta + snapshots)

#### C.1 Padrón del programa
- **Objetivo:** consultar el padrón, generar snapshots del trimestre y activar/desactivar el vínculo con GeoBase.
- **Ruta/URL:** `/{programa}/padron` — ruta `mml.padron` (gate `ver_padron`).
- **Vista/Componente:** `PadronPrograma` / `livewire.mml.padron-programa`.
- **Lo que YO puedo hacer:**
  - **Seleccionar componente**, **Toggle fuente** (snapshot ↔ vivo), **Seleccionar snapshot** histórico (solo consulta KPIs).
  - **Activar padrón en GeoBase** / **Desactivar padrón** (modal `desactivar-padron-{id}`) — requiere `generar_snapshot_padron` ✔.
  - **Generar snapshot del trimestre** — requiere `generar_snapshot_padron` ✔ (produce hash SHA256, queda como evidencia).
  - **Exportar Anexo 11** (ruta `evaluation.anexo-11`) — requiere `exportar_reportes` ✔.
- **Lo que NO puedo hacer:** **Exportar Padrón SHCP** (botón visible en este tab) requiere `exportar_padron_shcp`, que el Operador **no tiene** (solo planeador). (Ver sección 4.)

#### C.2 Cobertura del programa
- **Objetivo:** ver cobertura territorial, alertas por trimestre/meta y supuestos.
- **Ruta/URL:** `/{programa}/cobertura` — ruta `mml.cobertura` (gate `ver_padron`).
- **Vista/Componente:** `CoberturaPrograma` / `livewire.mml.cobertura-programa`.
- **Acciones:** seleccionar período (`YYYY-QN`), ver mapa choropleth (`/{programa}/cobertura/mapa.png`), revisar alertas (caída de inscripciones, cobertura baja) y supuestos de Propósito/Componentes.
- **Resultado:** detecto señales de que un supuesto está fallando, útil para escribir el análisis de desviación en captura.

---

### FLUJO D — ASM (Aspectos Susceptibles de Mejora)

El Operador **sí puede gestionar ASM** (`gestionar_asm`).

#### D.1 Listar / D.2 Crear o editar ASM
- **Listar:** `/evaluacion/asms` — ruta `evaluation.asms.index`, componente `AsmIndex` (gate `ver_asm`). KPIs y filtros por programa/status/responsable. Exportar XLSX (`evaluation.asms.export.xlsx`).
- **Crear:** `/evaluacion/asms/crear` (ruta `evaluation.asms.create`); **Editar:** `/evaluacion/asms/{asm}/editar` (ruta `evaluation.asms.edit`). Componente `AsmForm`.
  - **Campos:** programa, recomendación de origen (opcional), descripción del aspecto, acción de mejora, tipo de plazo, fecha compromiso, responsable, observaciones.
  - **Guardar** → vuelve al índice.
- **Detalle:** `/evaluacion/asms/{asm}` — ruta `evaluation.asms.show`, componente `AsmShow`.
- **Eliminar:** DELETE `/evaluacion/asms/{asm}` (requiere `gestionar_asm`).

---

### FLUJO E — Reportes (lectura y descarga)

Con `exportar_reportes` el Operador entra a los paneles de **lectura** del módulo de Reportes y descarga PDF/Excel:

- **Transversal:** `/evaluacion/transversal` (`PanelTransversal`) — tabs PED/ODS/UR/Anexo.
- **Desviaciones:** `/evaluacion/desviaciones` (`ReporteDesviaciones`).
- **Acumulado Anual:** `/evaluacion/acumulado-anual` (`AcumuladoAnual`).
- **Datos Abiertos:** `/evaluacion/datos-abiertos/diccionario` y descargas CSV/JSON/ZIP por ejercicio.
- **Evaluaciones externas (solo lectura):** `/evaluacion/externas` (`EvaluacionExternaIndex`) y el informe `/evaluacion/externas/{id}` — el Operador **lee** (`ver_evaluacion_externa`) pero **no edita** (no tiene `gestionar_evaluacion_externa`).

> Estos paneles son **consulta/descarga**. El Operador no "edita" indicadores ni evaluaciones desde aquí.

---

## 4. Qué NO puede hacer (acciones bloqueadas o no visibles)

| Acción | Por qué | Cómo se manifiesta |
|---|---|---|
| Construir/editar la MIR, crear programas, importar MIR | Sin `editar_mir` / `crear_programa` | El grupo **Planeación** no aparece en el sidebar; rutas `mml.etapa*`, `mml.mir`, `mml.importar*` quedan fuera (403 por middleware `permission:editar_mir`) |
| **Aprobar** u **Observar** avances | Sin `revisar_avance` / `aprobar_avance` | En `FlujosAvance` no ve/usa esas acciones; **Panel** y **Vencidos** de Seguimiento no aparecen en el sidebar |
| **Exportar Padrón SHCP** | Sin `exportar_padron_shcp` (solo planeador) | El botón existe en el tab Padrón pero la ruta `evaluation.padron-shcp` lo rechaza (403) |
| Gestionar presupuesto, capturar avance financiero, ver datos financieros, cuenta pública | Sin permisos del dominio Presupuesto | El grupo **Presupuesto** no aparece; conciliación, POA, partidas y captura financiera fuera de alcance |
| Sustento legal / documentos normativos / validación jurídica | Sin `ver_sustento_legal` (ni gestionar/validar) | El grupo **Jurídico** no aparece |
| Catálogos: PED, programas derivados, alineación | Sin `gestionar_catalogos` | El grupo **Catálogos** no aparece (middleware `permission:gestionar_catalogos`) |
| Datasets de Transparencia (crear/editar/aprobar/publicar) | Sin `ver_datasets_abiertos` / `gestionar_dataset_abierto` / `aprobar_datos_abiertos` | El grupo **Transparencia** no aparece |
| **Gestionar evaluación externa** (crear/editar informe, hallazgos, recomendaciones) | Sin `gestionar_evaluacion_externa` (decisión: la evaluación externa es de planeación, NO de operador) | Entra de **solo lectura**; no ve/usa los botones de edición del `InformeEvaluacionEditor` |
| Administración (usuarios, monitor IA, auditoría) | Sin `administrar_usuarios` / `invitar_usuarios` | El grupo **Administración** no aparece |
| **Gestionar desbloqueos** de avances congelados | Requiere `administrar_usuarios` | `/seguimiento/desbloqueos` fuera de alcance (sí puede *solicitar* desbloqueo, ver sección 5) |

> **Nota:** el scoping de equipo (`currentTeam`) limita además al Operador a ver solo los programas de su Unidad Responsable.

---

## 5. Errores y validaciones comunes (reglas duras relevantes a este rol)

1. **Análisis de desviación obligatorio en semáforo ≠ verde.** Si el resultado cae en **amarillo / rojo / rojo alto**, los 4 campos `dato`, `causa`, `acción`, `proyección` son **required, mínimo 5 caracteres**. No se puede guardar sin ellos. En verde no se exigen.
   - *Tip:* el botón "Generar justificación IA" sólo pre-llena la **causa**; los otros 3 campos hay que completarlos a mano.

2. **Avance congelado / no editable.** La captura y las evidencias validan en `guardar()` mediante `$avance->estaCongelado()` y `$avance->estado->esEditable()`. Si el avance ya fue **APROBADO** (congelado), **no podrás** modificar valores ni evidencias.
   - **Salida:** solicitar desbloqueo en `/seguimiento/desbloqueo/{avance_id}` (ruta `tracking.desbloqueo.solicitar`, componente `SolicitarDesbloqueo`): registras un **motivo** (textarea) y queda como solicitud pendiente. **Quien resuelve** el desbloqueo es un administrador (`administrar_usuarios`), no el Operador.

3. **Ventana de captura (vencimientos).** La ventana normativa = cierre del periodo + `tracking.dias_ventana_captura` (default 30 días). Si la ventana cerró, el avance puede marcarse **VENCIDO**. Usa **Mis Indicadores** (filtro de urgencia) y la **Sábana de Captura** (días para cierre) para no llegar tarde.

4. **Estado correcto para enviar a revisión.** Solo se envía a revisión un avance en **EN_CAPTURA**. Si está EN_REVISION o APROBADO no aplica; si fue **OBSERVADO**, primero **Corregir** (vuelve a EN_CAPTURA) y luego reenviar.

5. **Subida de evidencias.** Archivo **máx. 10 MB**; se exige `nombre_documento` y `área_generadora`. Formatos aceptados: PDF, Excel, imágenes y documentos.

6. **Padrón/Cobertura dependientes de GeoBase.** Si GeoBase no responde, el tab muestra estado `error` y el mapa hace retry; los snapshots y la sincronización de variables pueden fallar transitoriamente. Es esperado en entornos sin worker de cola corriendo (los jobs quedan pendientes). No es un error de captura tuyo.

7. **403 silenciosos por permiso.** Si tecleas a mano una URL fuera de tu alcance (p. ej. `evaluation.padron-shcp`, `presupuesto.*`, `mml.mir`), recibirás **403 / redirección**. No es un bug: es la segregación de funciones del rol.
