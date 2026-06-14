# Mapa del sistema (inventario de rutas/vistas/componentes) — insumo interno

> Generado por Fase 1 (mapeo paralelo). Insumo para los docs de módulo. No es entregable final.



---

Perfecto. Ahora tengo suficiente información. Voy a crear el mapa final del área "dte-cascade".

## Área: dte-cascade (dte-spp)

### PED (Plan Estatal de Desarrollo)

**Listado de Planes**
- **URL**: `/cascade/ped` | Route: `cascade.ped.index`
- **Vista**: `/resources/views/cascade/ped/index.blade.php`
- **Componente**: `PedTree` (Livewire)
- **Acciones**: Crear Plan (botón), Editar Plan (por nodo), Expandir/Contraer árbol
- **Propósito**: Visualizar jerárquicamente planes, ejes, temas, objetivos, estrategias y líneas de acción

**Crear Plan**
- **URL**: `/cascade/ped/plan/create` | Route: `cascade.ped.plan.create`
- **Vista**: `/resources/views/cascade/ped/plan/create.blade.php`
- **Componente**: `PedPlanForm` (Livewire)
- **Campos**: Nombre, Nivel de Gobierno (estatal/municipal), Período (inicio-fin), Activo (checkbox)
- **Acciones**: Guardar Plan → POST `/cascade/ped/plan` (route: `cascade.ped.plan.store`)
- **Propósito**: Crear nuevo Plan Estatal de Desarrollo

**Editar Plan**
- **URL**: `/cascade/ped/plan/{id}/edit` | Route: `cascade.ped.plan.edit`
- **Vista**: `/resources/views/cascade/ped/plan/edit.blade.php`
- **Componente**: `PedPlanForm` (Livewire, modo edición)
- **Acciones**: Actualizar → PUT `/cascade/ped/plan/{id}` (route: `cascade.ped.plan.update`)
- **Propósito**: Editar propiedades de plan existente

**Eliminar Plan**
- **Método**: DELETE `/cascade/ped/plan/{id}` (route: `cascade.ped.plan.destroy`)
- **Propósito**: Eliminar plan y todos sus descendientes (ejes, temas, objetivos, estrategias, líneas)

**Gestionar Nodos (Eje, Tema, Objetivo, Estrategia, Línea)**
- **Crear Nodo**: GET `/cascade/ped/nodo/create?tipo={tipo}&parent_id={id}` | Route: `cascade.ped.nodo.create`
- **Editar Nodo**: GET `/cascade/ped/nodo/{tipo}/{id}/edit` | Route: `cascade.ped.nodo.edit`
- **Vista**: `/resources/views/cascade/ped/nodo/create.blade.php`, `/nodo/edit.blade.php`
- **Componente**: `PedNodoForm` (Livewire)
- **Acciones**:
  - Eje/Tema: POST/PUT `/cascade/ped/eje|tema` con `numero`, `nombre`, `descripcion`
  - Objetivo/Estrategia/Línea: POST/PUT `/cascade/ped/objetivo|estrategia|linea` con `clave`, `descripcion`
  - Eliminar: DELETE `/cascade/ped/{tipo}/{id}`
- **Propósito**: Crear/editar/eliminar nodos individuales en la cascada PED

**Importar PED desde Markdown**
- **URL**: `/cascade/ped/import` | Route: `cascade.ped.import`
- **Vista**: `/resources/views/cascade/ped/import.blade.php`
- **Componente**: `PedImporter` (Livewire)
- **Campos**: Archivo (MD/TXT), Nombre Plan, Período (inicio-fin), Activo
- **Acciones**: Subir Archivo → Preview → Importar (POST `/cascade/ped/import`)
- **Propósito**: Importar estructura PED masiva desde archivo Markdown

---

### Matriz de Alineación

**Vista Principal**
- **URL**: `/cascade/alineacion` | Route: `cascade.alineacion.index`
- **Vista**: `/resources/views/cascade/alineacion/index.blade.php`
- **Componente**: `MatrizAlineacionManager` (Livewire)
- **Tabs**: PED ↔ PND | PND ↔ ODS | Línea ↔ Programa Derivado
- **Stats**: Contadores de cada tipo de alineación
- **Propósito**: Gestionar alineación estratégica entre niveles de la cascada

**PED ↔ PND**
- **Componente**: `AlineacionPedPnd` (Livewire)
- **Acciones**:
  - Crear: POST `/cascade/alineacion/ped-pnd` (sync objetivos)
  - Eliminar: DELETE `/cascade/alineacion/ped-pnd/{pedObjetivo}/{pndObjetivo}`
- **Búsquedas**: GET `/cascade/alineacion/search/ped-objetivos?q={search}`, `/search/pnd-objetivos?q={search}`
- **Propósito**: Alinear objetivos estratégicos PED con objetivos PND

**PND ↔ ODS**
- **Componente**: `AlineacionPndOds` (Livewire)
- **Acciones**:
  - Crear: POST `/cascade/alineacion/pnd-ods`
  - Eliminar: DELETE `/cascade/alineacion/pnd-ods/{pndObjetivo}/{odsMeta}`
- **Búsquedas**: GET `/cascade/alineacion/search/pnd-objetivos?q={search}`, `/search/ods-metas?q={search}`
- **Propósito**: Alinear objetivos PND con metas ODS

**Línea ↔ Programa Derivado**
- **Componente**: `AlineacionLineaPrograma` (Livewire)
- **Acciones**:
  - Crear: POST `/cascade/alineacion/linea-programa`
  - Eliminar: DELETE `/cascade/alineacion/linea-programa/{linea}/{programaObjetivo}`
- **Búsquedas**: GET `/cascade/alineacion/search/lineas-accion?q={search}`, `/search/programas-objetivos?q={search}`
- **Propósito**: Alinear líneas de acción PED con objetivos de programas derivados

**Vista de Cadena Completa**
- **URL**: `/cascade/alineacion/cadena/{lineaAccion}` | Route: `cascade.alineacion.cadena.show`
- **Vista**: `/resources/views/cascade/alineacion/cadena.blade.php`
- **Componente**: `CadenaAlineacion` (Livewire)
- **Propósito**: Mostrar cascada completa de una línea de acción (Plan → Eje → Tema → Objetivo → Estrategia → Línea → PND → ODS → Programas)

---

### Programas Derivados

**Listado y Gestión**
- **URL**: `/cascade/programas-derivados` | Route: `cascade.programas-derivados.index`
- **Vista**: `/resources/views/cascade/programas-derivados/index.blade.php`
- **Componente**: `ProgramasDerivadosManager` (Livewire)
- **Filtros**: Por tipo (Sectoriales, Especiales, Institucionales, Regionales)
- **Acciones**:
  - Crear Programa: POST `/cascade/programas-derivados` (campos: nombre, descripción, tipo)
  - Editar Programa: PUT `/cascade/programas-derivados/{id}`
  - Eliminar Programa: DELETE `/cascade/programas-derivados/{id}`
  - Expandir/Contraer programas
- **Propósito**: Gestionar programas derivados del PED activo

**Objetivos de Programas**
- **Crear Objetivo**: POST `/cascade/programas-derivados/{programa}/objetivos` (campos: clave, descripción)
- **Editar Objetivo**: PUT `/cascade/programas-derivados/{programa}/objetivos/{objetivo}`
- **Eliminar Objetivo**: DELETE `/cascade/programas-derivados/{programa}/objetivos/{objetivo}`
- **Propósito**: Gestionar objetivos dentro de cada programa derivado

---

### Permisos y Autenticación

- **Middleware Global**: `auth:sanctum`, `verified`, `permission:gestionar_catalogos`
- **Todas las rutas requieren**: Autenticación verificada + Permiso "gestionar_catalogos"

---

### Vista Vw_alineacion_completa

No hay ruta explícita mapeada en `routes/web/cascade.php`. Verificar en modelos si existe vista/query SQL para alineación completa.


---

Ahora tengo toda la información necesaria. Voy a crear el mapa completo del área dte-mml-wizard.

## Área: dte-mml-wizard (dte-spp)

### DASHBOARD Y GESTIÓN GENERAL

| URL | Nombre Ruta | Componente | Acciones | Modales | Permiso | Propósito |
|-----|-------------|-----------|----------|---------|---------|----------|
| `/mml/programas` | `mml.programas` | `ListaProgramas` | Crear, Importar, Acceder a MML/MIR | Form embebido (crear nuevo programa) | `permission:editar_mir` | Listado de programas presupuestarios con KPIs |
| `/mml/importar` | `mml.importaciones` | `DashboardImportaciones` | Retomar importación, Ver detalles | - | `permission:editar_mir` | Dashboard de importaciones con estado y conteos |

### IMPORTACIÓN MIR (FLUJO MULTI-ETAPA)

| URL | Nombre Ruta | Componente | Acciones | Modales | Permiso | Propósito |
|-----|-------------|-----------|----------|---------|---------|----------|
| `/mml/importar/nuevo` | `mml.importar.nuevo` | `ImportarPrograma` | Upload archivo (MD/CSV/XLSX), Preview, Continuar | - | `permission:editar_mir` | Etapa 1: Carga y parseo de archivo MIR |
| `/mml/importar/{importacion}/completar` | `mml.importar.completar` | `CompletarHuecos` | Corregir campos críticos (formula, tipo, dimension, frecuencia), Cargar indicadores | - | `permission:editar_mir` | Etapa 2: Validación y corrección de indicadores |
| `/mml/importar/{importacion}/vincular` | `mml.importar.vincular` | `VincularAlineacion` | Buscar con IA, Vincular a objetivos PED, Avanzar entre niveles | Sugerencias semánticas | `permission:editar_mir` | Etapa 3: Alineación a objetivos estratégicos |
| `/mml/importar/{importacion}/calendarizar` | `mml.importar.calendarizar` | `CalendarizarMetas` | Ajustar metas por período, Confirmar (activar programa) | - | `permission:editar_mir` | Etapa 4: Calendarización de metas anuales |

### WIZARD MML (ETAPAS 1-7)

| URL | Nombre Ruta | Componente | Acciones | Modales | Permiso | Propósito |
|-----|-------------|-----------|----------|---------|---------|----------|
| `/{programa}/etapa/1` | `mml.etapa1` | `DefinicionProblema` | Validar con IA, Aceptar sugerencia, Guardar | Panel IA (validación, sugerencias) | `permission:editar_mir` | Etapa 1: Definición del problema central |
| `/{programa}/etapa/2` | `mml.etapa2` | `ArbolProblemaBuilder` | Agregar nodo, Editar, Eliminar, Sugerir causas (IA), Sugerir causas indirectas, Generar árbol ejemplo | Modal edición, Modal preview de árbol | `permission:editar_mir` | Etapa 2: Árbol del problema (causas y efectos) |
| `/{programa}/etapa/3` | `mml.etapa3` | `ArbolObjetivosBuilder` | Transformar nodo (IA), Transformar todos, Editar, Cancelar | - | `permission:editar_mir` | Etapa 3: Árbol de objetivos (transformación de problemas) |
| `/{programa}/etapa/4` | `mml.etapa4` | `SeleccionAlternativas` | Crear alternativa, Toggle nodo, Evaluar con IA, Seleccionar, Eliminar | Panel evaluación IA | `permission:editar_mir` | Etapa 4: Selección de alternativas |
| `/{programa}/etapa/5` | `mml.etapa5` | `EmbudoPoblaciones` | Guardar poblaciones | Validación de embudo (referencias, potencial, objetivo) | `permission:editar_mir` | Etapa 5: Embudo de poblaciones (cobertura) |
| `/{programa}/etapa/6` | `mml.etapa6` | `AlineacionEstrategica` | Buscar con IA, Seleccionar sugerencia, Guardar, Finalizar planeación | Sugerencias semánticas PED | `permission:editar_mir` | Etapa 6: Alineación a objetivos estratégicos (PED) |
| `/{programa}/etapa/7/mir` | `mml.mir` | `MirEditor` | Crear/Editar niveles, Gestionar indicadores, Validar lógica, Crear snapshot, Ver versiones | Modal edición de niveles, Modal indicadores | `permission:editar_mir` | Etapa 7: Matriz de Indicadores para Resultados (MIR) |

### PADRÓN Y COBERTURA

| URL | Nombre Ruta | Componente | Acciones | Modales | Permiso | Propósito |
|-----|-------------|-----------|----------|---------|---------|----------|
| `/{programa}/padron` | `mml.padron` | `PadronPrograma` | Seleccionar componente, Toggle fuente (snapshot/vivo), Seleccionar snapshot, Activar/Desactivar padrón, Generar snapshot | - | `can:ver_padron` | Consulta de padrón de beneficiarios (GeoBase) |
| `/{programa}/cobertura` | `mml.cobertura` | `CoberturaPrograma` | Seleccionar período, Ver alertas por trimestre/meta | - | `can:ver_padron` | Seguimiento de cobertura y alertas de cumplimiento |
| `/{programa}/cobertura/mapa.png` | `mml.cobertura.mapa` | `MapaCoberturaProgramaController` | Descargar PNG | - | `can:ver_padron` | Generación de mapa de cobertura geográfica |

### CONFIGURACIÓN Y SEGUIMIENTO

| URL | Nombre Ruta | Componente | Acciones | Modales | Permiso | Propósito |
|-----|-------------|-----------|----------|---------|---------|----------|
| `/{programa}/clave-presupuestal` | `mml.clave-presupuestal` | `ClavePresupuestalEditor` | Segmentar clave presupuestal (administrativa/programática/funcional) | - | `can:editar_mir` | Asignación de clave presupuestal SEFIP |
| `/{programa}/iaff` | `mml.iaff` | `HistorialIaff` | Firmar IAFF | - | `can:firmar_iaff` | Historial y firma de Informes de Avance Financiero-Físico |
| `/{programa}/cierre-fiscal` | `mml.cierre-fiscal` | `CierreFiscalPanel` | Avanzar fase de cierre | - | `can:gestionar_cierre_fiscal` | Panel de cierre fiscal anual |

### NOTAS TÉCNICAS

- **Protección de rutas:** Todas las etapas (1-7) y flujo de importación requieren `permission:editar_mir` en el middleware de grupo. Padrón/cobertura usan permisos granulares (`can:ver_padron`, etc.)
- **IA Integrada:** Etapa 1 (validación), Etapa 2 (sugerencias de causas), Etapa 4 (evaluación alternativas), Etapa 6 (búsqueda semántica PED). Componentes manejan errores de conectividad sin bloqueo.
- **Flujo de Importación:** Secuencial (upload → completar → vincular → calendarizar), con persistencia de `ImportacionReporte` para retomabilidad.
- **Snapshot & Versionado:** MIR Editor soporta snapshots con etiqueta y versioning de cambios.
- **Validaciones:** Árbol de problemas (nodo central inmodificable), Embudo poblaciones (objetivo ≤ potencial ≤ referencia), MIR (lógica vertical/horizontal).


---

Perfect! Now I have comprehensive information about the dte-mir-editor area. Let me compile the complete mapping:

## Área: dte-mir-editor (dte-spp)

### Editor de Matriz de Indicadores para Resultados (Etapa 7)

| URL | Nombre de Ruta | Componente/Vista | Acciones Principales | Modales | Permisos | Propósito |
|---|---|---|---|---|---|---|
| `/mml/{programa}/etapa/7/mir` | `mml.mir` | `App\Livewire\Mml\MirEditor` / `mir-editor.blade.php` | Agregar/editar/eliminar componentes, actividades, indicadores, medios de verificación, supuestos. Guardar meta (con justificación obligatoria). Configurar semáforo 4 rangos (verde/amarillo/rojo/rojo alto). Validar sintaxis narrativa. Validar CREMAA (indicadores) y CREMA (medios verificación). Extraer variables. Sugerir fórmula. Buscar alineación PED (Fin/Propósito → Objetivos Estratégicos, Componente/Actividad → Líneas de Acción). Asignar UR Coadyuvante. Crear/restaurar snapshots. Validar MIR completa (lógica). | wire:confirm en eliminar niveles; expansible para búsqueda alineación; sugerencias CREMAA/Sintaxis; snapshot versioning | `permission:editar_mir` (vía middleware en grupo) | Editor interactivo con 4 niveles MIR (Fin, Propósito, Componentes, Actividades), indicadores por nivel, medios de verificación, supuestos estructurados, evaluación de calidad (CREMAA/CREMA) con IA, alineación estratégica y versionado |

### Clave Presupuestal

| URL | Nombre de Ruta | Componente/Vista | Acciones Principales | Modales | Permisos | Propósito |
|---|---|---|---|---|---|---|
| `/mml/programas/{programa}/clave-presupuestal` | `mml.clave-presupuestal` | `App\Livewire\Mml\ClavePresupuestalEditor` / `clave-presupuestal-editor.blade.php` | Importar clave SEFIP de 32 caracteres (segmentar automáticamente). Capturar clasificación administrativa (Grupo 1, UR 2, UE 3). Capturar clasificación programática (Programa 3, Subprograma 2, Proyecto 3, Actividad 3). Seleccionar clasificación funcional CONAC (Finalidad → Función → Subfunción en cascada). Vista previa en vivo de clave canónica. Guardar con validación (no bloqueante para segmentación informativa). | N/A | `can:editar_mir` | Definición y validación de clave presupuestal canónica SEFIP/CONAC: estructura administrativa, programática y funcional del gasto |

### Componentes y Subcomponentes Clave (dentro de mir-editor)

**Niveles MIR (filas en tabla)**:
- **Fin** (nivel superior, sin bordeborder azul): indicadores, medios verificación, supuestos
- **Propósito** (nivel estratégico, border verde): indicadores, medios verificación, supuestos
- **Componentes** (nivel de entrega, border ámbar): indicadores, supuestos, UR Coadyuvante
  - **Actividades** anidadas (border violeta, indentadas): indicadores, supuestos, UR Coadyuvante

**Indicador (dentro cada nivel)**:
- Nombre, tipo (Proceso/Eficacia/Impacto según reglas), dimensión (Cantidad/Calidad/Tiempo), frecuencia (Anual/Trimestral/etc.)
- Fórmula textual (con extracción de variables vía IA)
- Variables (Símbolo A,B,C... Nombre, Fuente, Unidad de Medida)
- Año de línea base
- Meta anual (con justificación obligatoria si cambia de valor previo; almacena audit trail)
- Semáforo: 4 rangos (Verde [min-max], Amarillo, Rojo, Rojo Alto/Sobrecumplimiento)
- CREMAA: 6 criterios (Claro, Relevante, Económico, Monitoreable, Adecuado, Aportante) con validación IA
- Anexos Transversales: checkboxes de anexos activos

**Medio de Verificación (dentro cada indicador)**:
- Nombre, fuente, tipo de fuente (Interna/Externa/Desagregada), organismo, URL, frecuencia de publicación
- Validación B9: FIN/PROPÓSITO exigen tipo_fuente externo
- Validación B7: frecuencia MV >= frecuencia indicador (cross-check)
- CREMA: 5 criterios (Confiable, Relevante, Económico, Monitoreable, Asequible) con validación IA + edición manual

**Supuestos Estructurados (dentro cada nivel)**:
- Descripción libre
- 3 checkboxes: Es Externo, Es Relevante, Probabilidad Razonable
- Badges: "Válido" si 3 checks = true; "Incompleto" si no

**Resumen Narrativo (por nivel)**:
- Textarea con validación sintáctica IA (reglas SHCP específicas por tipo nivel)
- Badges: Sintaxis OK / Sintaxis: revisar + observaciones + sugerencias (aceptable)

**Alineación estratégica (edit mode)**:
- Búsqueda semántica vía embedding (Fin/Propósito → PedObjetivoEstrategico; Componente/Actividad → PedLineaAccion)
- Sugerencias con similarity score (%)
- Read mode: badges verde con cadena expandible (Eje/Tema/PND para OE; Estrategia/Obj. Estratégico para LA)

**Snapshots (versioning)**:
- Crear snapshot etiquetado: captura estado completo MIR
- Toggle "Ver versiones": lista snapshots ordenada descendente (fecha, usuario creador)
- Restaurar versión confirmando (destructivo)

**Validación Lógica (al final)**:
- Botón "Validar MIR completa" → ejecuta `MirLogicaValidacionService`
- Resultado: tabla de hallazgos categorizados (error_critico rojo, advertencia amarillo, sugerencia azul)
- Cada fila: nivel involucrado, tipo hallazgo, mensaje + detalle opcional

**Protecciones de seguridad internas (scoping)**:
- Métodos privados `nivelDelPrograma()`, `indicadorDelPrograma()`, etc. validan pertenencia al programa montado
- Miss → no-op silencioso (no 404), defensa-en-profundidad contra request crafting


---

Perfecto. Ahora tengo suficiente información. Voy a generar el mapa consolidado del área dte-tracking.

## Área: dte-tracking (dte-spp)

### Panel de Seguimiento
- **URL**: `/seguimiento` | Nombre ruta: `tracking.panel`
- **Componente**: `PanelSeguimiento` (Livewire) → `panel-seguimiento.blade.php`
- **Acciones principales**: Filtrar por semáforo/estado/programa/nivel, búsqueda de indicador, visualizar tabla con semáforos, gráficos de donut y barras horizontales
- **Modales**: Ninguno; toolbar de filtros inline
- **Permiso**: `revisar_avance`
- **Propósito**: Dashboard principal de revisión de indicadores con estado y semáforo calculado por periodo

### Detalle de Indicador
- **URL**: `/seguimiento/indicador/{id}` | Nombre ruta: `tracking.indicador.detalle`
- **Componente**: `DetalleIndicador` (Livewire) → `detalle-indicador.blade.php`
- **Acciones principales**: Visualizar histórico de avances por ejercicio y periodo, acceder a detalles de cada avance
- **Modales**: Ninguno; timeline de observaciones en partial
- **Permiso**: `revisar_avance`
- **Propósito**: Consultar histórico completo de avances y resultados de un indicador específico

### Captura de Avance
- **URL**: `/seguimiento/captura/{avance_id}` | Nombre ruta: `tracking.captura`
- **Componente**: `CapturaAvance` (Livewire) → `captura-avance.blade.php`
- **Acciones principales**: Ingresar valores de variables, calcular resultado (vía fórmula), generar justificación IA, completar análisis de desviación (dato/causa/acción/proyección), sincronizar variables desde GeoBase, guardar
- **Modales**: Modal de confirmación para guardar (wire:confirm)
- **Permiso**: Sin gate explícito (validaciones en guardar via `$avance->estaCongelado()` y `$avance->estado->esEditable()`)
- **Propósito**: Captura de variables e indicadores con cálculo automático de fórmulas y análisis de desviación

### Evidencias de Avance
- **URL**: `/seguimiento/avance/{avance_id}/evidencias` | Nombre ruta: `tracking.evidencia.index`
- **Componente**: `EvidenciaAvance` (Livewire) → `evidencia-avance.blade.php`
- **Acciones principales**: Subir archivo (PDF, Excel, imágenes, documentos), registrar nombre_documento y área_generadora, eliminar evidencia
- **Modales**: Formulario embebido para subir archivo (max 10MB)
- **Permiso**: Validación en `guardar()` via `$avance->estaCongelado()` y `$avance->estado->esEditable()`
- **Propósito**: Gestión de documentos probatorios (evidencias) asociadas a avances

### Descargar Evidencia
- **URL**: `/seguimiento/evidencia/{evidencia_id}/download` | Nombre ruta: `tracking.evidencia.download`
- **Controlador**: `EvidenciaController@download`
- **Acciones principales**: Descargar archivo de evidencia
- **Permiso**: Sin gate explícito en ruta (implementado a nivel de controlador)
- **Propósito**: Descarga de archivos de evidencia

### Flujo de Avance (Revisión)
- **URL**: `/seguimiento/flujo/{avance_id}` | Nombre ruta: `tracking.flujo`
- **Componente**: `FlujosAvance` (Livewire) → `flujos-avance.blade.php`
- **Acciones principales**: Enviar a revisión (capturador), aprobar (revisor), observar (revisor con textarea de observación), corregir (capturador devuelve a EN_CAPTURA)
- **Modales**: Modal de confirmación para cada acción; modal embedded para observaciones (textarea)
- **Permiso**: `revisar_avance` (para aprobar/observar); `capturar_avance` (para enviar a revisión/corregir)
- **Propósito**: Orquestar transiciones de estado de avances (EN_CAPTURA → EN_REVISION → APROBADO/OBSERVADO → EN_CAPTURA)

### Mis Indicadores Pendientes
- **URL**: `/seguimiento/pendientes` | Nombre ruta: `tracking.pendientes`
- **Componente**: `MisIndicadoresPendientes` (Livewire) → `mis-indicadores-pendientes.blade.php`
- **Acciones principales**: Listar avances EN_CAPTURA del usuario autenticado, filtrar por urgencia (≤3d, 4-7d, >7d), acceso directo a captura
- **Modales**: Ninguno
- **Permiso**: Sin gate explícito (filtra por `auth()->id()`)
- **Propósito**: Dashboard del capturador con avances pendientes de entrega

### Indicadores Vencidos
- **URL**: `/seguimiento/vencidos` | Nombre ruta: `tracking.vencidos`
- **Componente**: `IndicadoresVencidos` (Livewire) → `indicadores-vencidos.blade.php`
- **Acciones principales**: Listar avances con estado VENCIDO del team, filtrar por programa, información de capturador
- **Modales**: Ninguno
- **Permiso**: `revisar_avance`
- **Propósito**: Visibilidad de avances vencidos para supervisión y reasignación

### Solicitar Desbloqueo
- **URL**: `/seguimiento/desbloqueo/{avance_id}` | Nombre ruta: `tracking.desbloqueo.solicitar`
- **Componente**: `SolicitarDesbloqueo` (Livewire) → `solicitar-desbloqueo.blade.php`
- **Acciones principales**: Registrar motivo de desbloqueo (textarea), crear solicitud con estado pendiente, visualizar histórico de solicitudes
- **Modales**: Formulario inline para motivo (textarea), histórico de desbloqueos con estado y resolución
- **Permiso**: Sin gate explícito (validación: avance debe estar `estaCongelado()`)
- **Propósito**: Solicitud de descongelación excepcional de avances aprobados

### Gestionar Desbloqueos
- **URL**: `/seguimiento/desbloqueos` | Nombre ruta: `tracking.desbloqueos`
- **Componente**: `GestionarDesbloqueos` (Livewire) → `gestionar-desbloqueos.blade.php`
- **Acciones principales**: Listar solicitudes pendientes, aprobar (descongelación + cambio a EN_CAPTURA), rechazar (con textarea de resolución)
- **Modales**: Modal embedded para rechazo (textarea de resolucion)
- **Permiso**: `administrar_usuarios`
- **Propósito**: Resolución de solicitudes de desbloqueo de avances congelados

### Sábana de Captura
- **URL**: `/seguimiento/sabana-captura` | Nombre ruta: `tracking.sabana-captura`
- **Componente**: `SabanaCaptura` (Livewire) → `sabana-captura.blade.php`
- **Acciones principales**: Tabla de metas/periodo con estado de avance, filtrar por programa/nivel/periodo/estado, exportar a PDF y Excel
- **Modales**: Ninguno
- **Permiso**: `ver_sabana_captura` (middleware)
- **Propósito**: Visibilidad consolidada de ventana de captura por meta/periodo con control de vencimiento (días para cierre)

### Concentrado de Captura
- **URL**: `/seguimiento/concentrado-captura` | Nombre ruta: `tracking.concentrado-captura`
- **Componente**: `ConcentradoCaptura` (Livewire) → `concentrado-captura.blade.php`
- **Acciones principales**: Tabla agrupada por programa e indicador con conteos por estado (aprobados/en_revision/en_captura/observados), filtrar, exportar PDF y Excel
- **Modales**: Ninguno
- **Permiso**: `ver_concentrado_captura` (middleware)
- **Propósito**: Resumen ejecutivo de estado de capturas agrupadas (concentrado) con métricas por estado

### Dashboard de Indicadores
- **URL**: `/seguimiento/{programa}/dashboard-indicadores` | Nombre ruta: `tracking.dashboard-indicadores`
- **Componente**: `DashboardIndicadores` (Livewire) → `dashboard-indicadores.blade.php`
- **Acciones principales**: Expandir/colapsar niveles (Fin, Propósito, Componentes), ver detalle de indicadores por nivel, visualizar metas y avances
- **Modales**: Panel expandible por nivel (toggleNivel, verDetalle)
- **Permiso**: Sin gate explícito
- **Propósito**: Visualización jerárquica del MIR de un programa con indicadores, metas y avances


---

Perfecto. Tengo toda la información necesaria. Ahora construiré el mapa exhaustivo del área dte-evaluation:

## Área: dte-evaluation (dte-spp)

### Panel de Evaluación Interna (EvaluacionPrograma)

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/programa/{evaluacion}` |
| **Ruta** | `evaluation.programa` |
| **Componente** | `EvaluacionProgramaView.php` (Livewire) |
| **Vista** | `evaluacion-programa.blade.php` |
| **Propósito** | Vista de lectura: resumen ejecutivo, índice de eficacia, tablero de semáforos (verde/amarillo/rojo/rojo alto), comparativa anual, indicadores crónicos, desviaciones. |
| **Permiso** | `can:exportar_reportes` |
| **Acciones** | Visualización solo lectura (sin modales). Carga indicadores con avances aprobados, metasPeriodo, alineación PED/ODS. |

### Panel Transversal (Análisis por Ejes, ODS, UR, Anexos)

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/transversal?tab={tab}` |
| **Ruta** | `evaluation.transversal` |
| **Componente** | `PanelTransversal.php` (Livewire, con `#[Url]` tab) |
| **Vista** | `panel-transversal.blade.php` |
| **Propósito** | Panel multi-tab: agrupa programas por Eje PED, Objetivo ODS, Unidad Responsable (team), o Anexo Transversal. Calcula índices promedio y conteos de semáforos por grupo. |
| **Permiso** | `can:exportar_reportes` |
| **Tabs/Acciones** | Switcheo de tabs: `ped`, `ods`, `ur`, `anexo`. Datos dinámicos por tab (sin guardar). |

### Reporte de Desviaciones

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/desviaciones` |
| **Ruta** | `evaluation.desviaciones` |
| **Componente** | `ReporteDesviaciones.php` (Livewire con HasTraceableTable, HasTrackingFilters) |
| **Vista** | `reporte-desviaciones.blade.php` |
| **Propósito** | Tabla paginada de avances que tienen justificación (final o IA). Filtros: programa, nivel MIR, estado, trimestre, rango fechas. Columnas: indicador, período, meta/resultado, justificación (corta/completa con badge final/IA/ambas), estado, capturador, fecha. KPIs: total, con justificación final, solo IA, pendientes validar. |
| **Permiso** | `can:exportar_reportes` |
| **Modales** | Tooltip en justificaciones (view inline si >100 chars). |
| **Acciones** | Paginación, ordenamiento (indicador/fecha), agrupación por programa, búsqueda full-text. |

### Acumulado Anual

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/acumulado-anual` |
| **Ruta** | `evaluation.acumulado-anual` |
| **Componente** | `AcumuladoAnual.php` (Livewire con HasTraceableTable, HasTrackingFilters) |
| **Vista** | `acumulado-anual.blade.php` |
| **Propósito** | Tabla de indicadores con acumulado anual por trimestre (T1–T4), meta anual, acumulado, cumplimiento %, semáforo (verde/amarillo/rojo/rojo_alto según umbral 130%). Filtros: programa, nivel MIR, búsqueda. Fuerza alcanceTemporal=anio. |
| **Permiso** | `can:exportar_reportes` |
| **KPIs** | Total indicadores, en verde/amarillo/rojo/rojo_alto. |
| **Acciones** | Paginación, ordenamiento (natural por indicador), agrupación por programa. |

### ASM (Análisis de Situación de Meta) - Índice

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/asms` |
| **Ruta** | `evaluation.asms.index` |
| **Componente** | `AsmIndex.php` (Livewire con WithPagination) |
| **Vista** | `asm/index.blade.php` |
| **Propósito** | Lista paginada de ASM vinculados a recomendaciones de evaluación externa. Filtros: programa, status (pendiente/en_proceso/cumplido), responsable, búsqueda descripción_aspecto, recomendación. |
| **Permiso** | `can:ver_asm` |
| **KPIs** | Total, pendientes, en proceso, cumplidos. |
| **Acciones** | Crear (`/crear`), editar (`/{asm}/editar`), eliminar (DELETE `/{asm}`), exportar XLSX (`/exportar/xlsx`), ver detalle (`/{asm}`). |

### ASM - Crear/Editar

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/asms/crear`, `/evaluacion/asms/{asm}/editar` |
| **Ruta** | `evaluation.asms.create`, `evaluation.asms.edit` |
| **Componente** | `AsmForm.php` (Livewire) con `AsmFormData` (form object) |
| **Vista** | `asm/form.blade.php` |
| **Propósito** | Form para crear/editar ASM. Secciones: Programa + recomendación de origen (opcional, relación a Recomendacion vía Hallazgo→InformeEvaluacion→EvaluacionExterna). Descripción aspecto, acción de mejora, tipo plazo, fecha compromiso, responsable, observaciones. |
| **Permiso** | `can:gestionar_asm` |
| **Modales** | Selects dinámicos: recomendaciones filtradas por programa. |
| **Acciones** | Guardar (crea o actualiza), redirige a index. |

### ASM - Detalle

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/asms/{asm}` |
| **Ruta** | `evaluation.asms.show` |
| **Componente** | `AsmShow.php` (Livewire) |
| **Vista** | `asm/show.blade.php` |
| **Propósito** | Vista de lectura: datos ASM, recomendación vinculada (si existe), programa, responsable, fechas, estado con badge semafórico. |
| **Permiso** | `can:ver_asm` |
| **Acciones** | Botón editar, volver a índice. |

### Exportaciones ASM

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/asms/exportar/xlsx` |
| **Ruta** | `evaluation.asms.export.xlsx` |
| **Controlador** | `AsmXlsxExportController::download()` |
| **Propósito** | Descarga archivo XLSX con todos los ASM (filtrados). |
| **Permiso** | `can:exportar_reportes` |

### Evaluación Externa - Índice

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/externas?programa={id}&tipo={tipo}&ejercicio={año}` |
| **Ruta** | `evaluation.externas.index` |
| **Componente** | `EvaluacionExternaIndex.php` (Livewire con WithPagination, `#[Url]`) |
| **Vista** | `externa/index.blade.php` |
| **Propósito** | Lista paginada de evaluaciones externas. Filtros: programa, tipo (evaluacion_especifica/evaluacion_integral/etc), ejercicio fiscal. Ordenado descendente por ejercicio/id. |
| **Permiso** | `can:ver_evaluacion_externa` |
| **KPIs** | Total, en proceso, concluidas. |
| **Acciones** | Crear (`/crear`), editar (`/{evaluacionExterna}/editar`), ver detalle (`/{evaluacionExterna}`). |

### Evaluación Externa - Crear/Editar

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/externas/crear`, `/evaluacion/externas/{evaluacionExterna}/editar` |
| **Ruta** | `evaluation.externas.create`, `evaluation.externas.edit` |
| **Componente** | `EvaluacionExternaForm.php` (Livewire) con `EvaluacionExternaFormData` |
| **Vista** | `externa/form.blade.php` |
| **Propósito** | Form para crear/editar evaluación externa. Programa, ejercicio fiscal, fecha inicio/fin, tipo, evaluador externo, estado, evaluación_programa_id (asociada, opcional). Al crear: auto-crea InformeEvaluacion. |
| **Permiso** | `can:gestionar_evaluacion_externa` |
| **Modales** | Selects dinámicos: evaluaciones_programa filtradas por programa + ejercicio. |
| **Acciones** | Guardar (transaction), redirige a index. |

### Informe de Evaluación - Editor (6 Secciones)

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/externas/{evaluacionExterna}` |
| **Ruta** | `evaluation.externas.show` |
| **Componente** | `InformeEvaluacionEditor.php` (Livewire) |
| **Vista** | `externa/informe-editor.blade.php` |
| **Propósito** | Editor WYSIWYG de InformeEvaluacion con 6 secciones: (1) Resumen ejecutivo, (2) Metodología, (3) Conclusiones (fallback), (4) Fichas (fallback), + (5) Hallazgos + (6) Recomendaciones anidadas. Cada hallazgo tiene descripción, severidad (alta/media/baja), evidencia_url, contador de recomendaciones. Cada recomendación tiene descripción, prioridad (alta/media/baja), contador de ASMs asociados. |
| **Permiso** | `can:gestionar_evaluacion_externa` para modificar, `can:ver_evaluacion_externa` para lectura. |
| **Modales/Acciones** | **Guardar sección** (AJAX, inline): `guardarSeccion(campo, valor)`. **Agregar hallazgo** (modal/form inline): `agregarHallazgo()` valida descripción (min 10), severidad, evidencia_url (url|nullable). **Eliminar hallazgo**: `eliminarHallazgo(id)` con confirm (advierte ASMs desvinculados). **Agregar recomendación** (inline por hallazgo): `agregarRecomendacion(hallazgoId)` valida descripción (min 10), prioridad. **Eliminar recomendación**: `eliminarRecomendacion(id)`. |
| **Validación** | SeveridadHallazgo enum (alta/media/baja), PrioridadRecomendacion enum (alta/media/baja). |

### Exportaciones Generales

| Elemento | Detalle |
|----------|---------|
| **URL Base** | `/evaluacion/exportar/` |
| **Ruta Base** | `evaluation.exportar.*` |
| **Controlador** | `ExportController` |
| **Acciones** | |
| — **PDF** | `pdf(tipo, id?)` tipos: `mir`, `ficha-tecnica`, `avance-trimestral`, `evaluacion-anual`, `transversal`, `fmye`. |
| — **Excel** | `excel(tipo, id?)` mismos tipos. |
| — **Async** | `async(formato, tipo)` lanza job, retorna JSON con filename. |
| — **Descargar** | `descargar(filename)` desde storage. |
| **Permiso** | `can:exportar_reportes` |

### Anexo 11 (PEF - Presupuesto de Egresos Federales)

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/exportar/anexo-11/{programa}` |
| **Ruta** | `evaluation.anexo-11` |
| **Controlador** | `Anexo11Controller::__invoke()` |
| **Propósito** | Descarga XLSX Anexo 11 para programa (requiere GeoBase link). Usa `Anexo11ExportService::build()` y `Anexo11ExcelExport`. |
| **Permiso** | `can:exportar_reportes` |

### Padrón SHCP

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/exportar/padron-shcp/{programa}` |
| **Ruta** | `evaluation.padron-shcp` |
| **Controlador** | `PadronShcpController::download()` |
| **Propósito** | Descarga padrón de beneficiarios en formato SHCP. |
| **Permiso** | `can:exportar_padron_shcp` |

### Presupuesto Capítulo

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/exportar/presupuesto-capitulo/{programa}` |
| **Ruta** | `evaluation.exportar.presupuesto-capitulo` |
| **Controlador** | `PresupuestoCapituloXlsxController::download()` |
| **Propósito** | Descarga XLSX capítulo presupuestario para Cuenta Pública. |
| **Permiso** | `can:exportar_cuenta_publica` |

### MIR Pública (PDF)

| Elemento | Detalle |
|----------|---------|
| **URL** | `/evaluacion/mir-publica/{id}?ejercicio_fiscal={año}` |
| **Ruta** | `evaluation.mir-publica` |
| **Controlador** | Closure en routes |
| **Propósito** | Genera PDF MIR en el acto (inline, no attachment) usando `MirPdfExport::generate()`. Parámetro ejercicio_fiscal opcional (default: año actual). |
| **Permiso** | Público (sin restricción explícita, pero sin autenticación se bloqueará por `auth:sanctum, verified`). |

### Datos Abiertos

| Elemento | Detalle |
|----------|---------|
| **URL Base** | `/evaluacion/datos-abiertos/` |
| **Ruta Base** | `evaluation.datos-abiertos.*` |
| **Controlador** | `DatosAbiertosController` |
| **Acciones** | |
| — **CSV** | `/csv/{ejercicio}` → `evaluation.datos-abiertos.csv` |
| — **JSON** | `/json/{ejercicio}` → `evaluation.datos-abiertos.json` |
| — **Diccionario** | `/diccionario` → `evaluation.datos-abiertos.diccionario` |
| — **ZIP** | `/zip/{ejercicio}` → `evaluation.datos-abiertos.zip` |
| **Propósito** | Exporta indicadores, avances y evaluaciones en formatos abiertos (CSV, JSON, ZIP multiarchivo) para cumplimiento de transparencia. Diccionario describe campos. |
| **Permiso** | `can:exportar_reportes` |

### Middleware y Seguridad

| Elemento | Detalle |
|----------|---------|
| **Auth** | Todas las rutas en `/evaluacion` requieren `auth:sanctum`, `verified` (Jetstream). |
| **Gates/Permisos** | `exportar_reportes` (lectura paneles, descargas), `ver_asm`, `gestionar_asm` (CRUD ASM), `ver_evaluacion_externa`, `gestionar_evaluacion_externa` (CRUD evaluación + informe), `exportar_padron_shcp`, `exportar_cuenta_publica`. |
| **Scoping** | Usuarios no-admin ven solo programas del `currentTeam`. |


---

Perfecto. Tengo toda la información necesaria. Ahora genero el mapa completo del área dte-juridico:

## Área: dte-juridico (dte-spp)

| Pantalla | URL | Componente | Acciones | Modales | Permiso | Propósito |
|----------|-----|-----------|----------|---------|---------|-----------|
| **Panel Jurídico** | `/juridico/` | `PanelJuridico.php` / `panel-juridico.blade.php` | Filtrar por ejercicio, Filtrar por estado (Validado, Pendiente, En revisión, Rechazado, Sin registro), Ver, Validar | Alerta de documentos próximos a vencer | `can:ver_sustento_legal` | Dashboard con KPIs de validaciones (validados, pendientes, rechazados, sin registro) y lista de programas con estado de sustento legal. |
| **Sustento Legal de Programa** | `/juridico/programa/{programa}` | `SustentoLegalPrograma.php` / `sustento-legal-programa.blade.php` | Agregar fundamento (Create), Editar fundamento, Eliminar fundamento (wire:confirm), Descargar documento | Confirmación: "Eliminar este fundamento?" | `can:ver_sustento_legal` (lectura), `can:gestionar_sustento_legal` (crear/editar/eliminar) | Visualiza fundamentos jurídicos agrupados por tipo, documentos normativos asociados y estado de validación automática del programa. |
| **Crear/Editar Fundamento** | `/juridico/programa/{programa}/fundamento/create` o `/programa/{programa}/fundamento/{fundamento}/edit` | `FundamentoForm.php` / `fundamento-form.blade.php` | Seleccionar tipo, nivel jerarquía, ordenamiento (catálogo), artículos, descripción, marcar vigente, Registrar/Guardar Cambios | Modal de selección de catálogo (actualiza nombre y nivel automáticamente) | `can:gestionar_sustento_legal` | Formulario para crear o editar un fundamento legal específico de un programa, con validación y recalcular checklist. |
| **Documentos Normativos** | `/juridico/programa/{programa}/documentos` | `DocumentosNormativos.php` / `documentos-normativos.blade.php` | Subir documento (PDF), Verificar documento, Eliminar documento, Descargar documento | Confirmación: "Eliminar este documento?", Upload progress (wire:loading) | `can:gestionar_reglas_operacion` | Gestión de archivos PDF (ROP, leyes, reglamentos) asociados al programa, con carga, verificación y descarga de documentos normativos. |
| **Descarga de Documento** | `/juridico/documento/{documento}/download` | `DocumentoNormativoController.php` (descarga controlada) | Descargar PDF | — | `can:ver_sustento_legal` | Endpoint protegido para descarga de documentos normativos específicos con control de acceso. |
| **Validación Jurídica** | `/juridico/programa/{programa}/validacion` | `ValidacionJuridica.php` / `validacion-juridica.blade.php` | Validar jurídicamente, Rechazar, Marcar en revisión, Añadir observaciones | Confirmación implícita en "Rechazar" (requiere observaciones >=10 caracteres) | `can:validar_sustento_legal` | Panel de validación jurídica final con checklist automático (Facultad UR, Mandato gasto, ROP), detalle de fundamentos y documentos, y acciones de aprobación/rechazo con observaciones. |


---

Perfecto. Ahora voy a compilar el mapa completo del área. Tengo toda la información necesaria:

## Área: dte-presupuesto (dte-spp)

| Funcionalidad | URL | Ruta | Vista/Componente | Acciones Principales | Permisos | Propósito |
|---|---|---|---|---|---|---|
| **Panel Presupuestal** | `/presupuesto/` | `presupuesto.panel` | `PanelPresupuestal.php` + `panel-presupuestal.blade.php` | Filtro ejercicio fiscal, visualización KPIs (total aprobado, ejercido, % ejercido) | `can:ver_datos_financieros` | Dashboard con KPIs y gráficos (Gauge, Marimekko, Lollipop) del presupuesto por programa |
| **Gestión Partidas** | `/presupuesto/partidas` | `presupuesto.partidas` | `GestionPartidas.php` + `gestion-partidas.blade.php` | Nueva Partida, Editar, Adecuaciones, Eliminar (confirmación), Buscar, Filtro programa/ejercicio | `can:gestionar_presupuesto` | CRUD de partidas presupuestales (claves COG, montos aprobados/modificados) con paginación |
| **Crear/Editar Partida** | `/presupuesto/partidas/create` `/presupuesto/partidas/{partida}/edit` | `presupuesto.partidas.create` `presupuesto.partidas.edit` | `PartidaForm.php` + `partida-form.blade.php` | Guardar, Cancelar, Validación (programa, clave, descripción, monto) | `can:gestionar_presupuesto` | Formulario para alta/edición de partidas con programa, ejercicio, COG, montos |
| **Adecuaciones Presupuestales** | `/presupuesto/partidas/{partida}/modificaciones` | `presupuesto.partidas.modificaciones` | `ModificacionesPartida.php` + `modificaciones-partida.blade.php` | Registrar adecuación (ampliación/reducción), Tipo, Monto, Fecha, Oficio, Justificación | `can:gestionar_presupuesto` | Registra ampliaciones/reducciones de presupuesto con historial (D6: modificaciones presupuestales) |
| **Captura Avance Financiero** | `/presupuesto/captura/{programa}` | `presupuesto.captura` | `CapturaAvanceFinanciero.php` + `captura-avance-financiero.blade.php` | Tabs: Calendarización (metas T1-T4), Avance (comprometido/devengado/pagado/observaciones), Guardar metas, Guardar avances | `can:capturar_avance_financiero` | Captura trimestral de metas de gasto y avances financieros (4 trimestres con validación comprometido≥devengado≥pagado) |
| **Importar Presupuesto** | `/presupuesto/importar` | `presupuesto.importar` | `ImportarPresupuesto.php` + `importar-presupuesto.blade.php` | Upload CSV (clave_programa, clave_partida, descripción, monto_aprobado, monto_modificado), Procesar, Preview, Confirmar (crear/actualizar) | `can:gestionar_presupuesto` | Carga masiva de partidas por CSV con validación, preview y resultado (creados/actualizados/errores) |
| **Conciliación Físico-Financiera** | `/presupuesto/conciliacion/{programa}` | `presupuesto.conciliacion` | `ConciliacionPadron.php` + `conciliacion-padron.blade.php` | Cruza pagado (tesorería local) vs entregado (padrón geobase), muestra diferencia y detalle por componente | `can:ver_datos_financieros` | Conciliación D6: tesorería ⋈ padrón beneficiarios con validación física-financiera en vivo |
| **Programa Operativo Anual (POA)** | `/presupuesto/poa` | `presupuesto.poa` | `ReportePoa.php` + `reporte-poa.blade.php` | Selector ejercicio fiscal, grouped por programa, tipos (Físico/Financiero), conceptos, trimestres (T1-T4), total anual, link a conciliación | `can:ver_datos_financieros` | Reporte POA (vw_poa): vista física + financiera calendarizada por trimestre con aceso a conciliaciones |
| **Cuenta Pública** | `/presupuesto/cuenta-publica` | `presupuesto.cuenta-publica` | `CuentaPublicaView.php` + `cuenta-publica-view.blade.php` | Filtro ejercicio, Toggle "Agrupar por Eje PED", Tabla con programa, aprobado, ejercido, % financiero, eficiencia, semáforos (físico/financiero/combinado), alineación PED | `can:exportar_cuenta_publica` | Resumen ejecutivo con semáforos por programa (rojo/amarillo/verde según % ejercido) y ejes PED |
| **Exportar Cuenta Pública (PDF)** | `/presupuesto/exportar/pdf/{ejercicio}` | `presupuesto.exportar.pdf` | `PresupuestalController@exportarPdf` (HTTP GET) | Genera descarga PDF de cuenta pública | `can:exportar_cuenta_publica` | Exportación a PDF para cierre fiscal y reporte público |
| **Exportar Cuenta Pública (Excel)** | `/presupuesto/exportar/excel/{ejercicio}` | `presupuesto.exportar.excel` | `PresupuestalController@exportarExcel` (HTTP GET) | Genera descarga Excel de cuenta pública | `can:exportar_cuenta_publica` | Exportación a Excel para análisis y auditoría |

### Detalles de Modelos y Enums

- **Modelos**: `PartidaPresupuestal`, `AvanceFinanciero`, `MetaGastoTrimestral`, `ModificacionPresupuestal`, `CierreFiscal`, `Iaff`, `ClasificacionFuncional`
- **Enum**: `TipoModificacionPresupuestal` (AMPLIACION, REDUCCION)
- **Vistas derivadas**: `VwPoa` (física + financiero por trimestre)
- **Servicios**: `ModificacionPresupuestalService`, `CuentaPublicaService`, `GeoBaseClient` (integración padrón)

### Modales y Confirmaciones

- **Eliminar partida**: `wire:confirm` — "¿Eliminar esta partida? Los avances financieros asociados también se eliminarán."
- **Confirmar importación**: `wire:confirm` — "¿Confirmar la importación de {count} registros?"
- Sin modales embebidos detectados; usa confirmaciones nativas de Livewire.

### Características Especiales

- **Validación en tiempo real**: Metas no pueden exceder monto efectivo; avances respetan orden (pagado ≤ devengado ≤ comprometido)
- **Calendarización trimestral**: T1, T2, T3, T4 para metas de gasto y avances
- **Semáforos**: Verde (≥75%), Amarillo (40-75%), Rojo (<40%) por ejercido
- **Cierre fiscal 4 fases**: Soportado por modelo `CierreFiscal` (no UI visible en presupuesto.php)
- **Integración padrón**: `GeoBaseClient` consulta beneficiarios en vivo para conciliación D6


---

Perfecto. Ahora tengo toda la información necesaria para crear el mapa. Voy a compilarlo.

## Área: dte-transparencia (dte-spp)

### Gestión de Datasets Abiertos (Admin)

| Página | URL | Componente | Acciones | Modales/Formularios | Permiso | Propósito |
|--------|-----|-----------|----------|-------------------|---------|----------|
| **Índice de datasets** | `GET /transparencia/datos-abiertos` | `Livewire\Transparencia\Datasets\Index` / `livewire/transparencia/datasets/index.blade.php` | Buscar, filtrar (status, sistema, tipo), Ver, Crear entrega | — | `ver_datasets_abiertos` | Listar todos los datasets (plantillas y entregas) con KPIs agregados (Total, Publicados, En revisión, Borradores) |
| **Detalle dataset** | `GET /transparencia/datos-abiertos/{dataset}` | `Livewire\Transparencia\Datasets\Show` / `livewire/transparencia/datasets/show.blade.php` | Enviar a revisión, Aprobar, Publicar, Editar borrador, Editar plantilla, Crear entrega, Rechazar, Retirar | `wire:confirm` antes de Aprobar/Publicar/Rechazar/Retirar + textarea para motivo (rechazar/retirar) | `ver_datasets_abiertos` (lectura) + `gestionar_dataset_abierto`/`aprobar_datos_abiertos` (acciones) | Mostrar metadatos, DCAT metadata, actividad; flujo de aprobación/publicación/retiro |
| **Editar borrador** | `GET /transparencia/datos-abiertos/{dataset}/editar` | `Livewire\Transparencia\Datasets\Edit` / `livewire/transparencia/datasets/edit.blade.php` | Guardar, Cancelar | Campos: Nombre (255), Descripción (5000), DCAT Metadata (JSON) | `gestionar_dataset_abierto` + creador del dataset o RDA | Editar draft dataset; solo borradores editables |
| **Crear entrega** | `GET /transparencia/datos-abiertos/{dataset}/crear-entrega` | `Livewire\Transparencia\Datasets\CrearEntrega` / `livewire/transparencia/datasets/crear-entrega.blade.php` | Crear entrega, Cancelar | Campo Periodo (YYYY o YYYY-Q1..Q4 regex) | `gestionar_dataset_abierto` | Clonar plantilla para periodo específico (crea nuevo dataset con status=borrador, metadatos heredados) |
| **Editar plantilla** | `GET /transparencia/datos-abiertos/{dataset}/editar-plantilla` | `Livewire\Transparencia\Datasets\EditarPlantilla` / `livewire/transparencia/datasets/editar-plantilla.blade.php` | Guardar plantilla, Cancelar | Campos: Nombre (255), Descripción (5000), DCAT Metadata (JSON) | `aprobar_datos_abiertos` (RDA only) | Editar plantilla del catálogo; solo RDA puede editar (periodo=null) |

### Portal Público (Sin autenticación)

| Página | URL | Componente | Acciones | Modales/Formularios | Permiso | Propósito |
|--------|-----|-----------|----------|-------------------|---------|----------|
| **Catálogo datasets** | `GET /transparencia` | `Portal\PortalIndexController@index` / `portal/index.blade.php` | Ver dataset (si código=DS-01) | — | Público (throttle:60,1) | Mostrar catálogo de datasets publicados; solo DS-01 tiene enlace "Ver datos" (próximamente otros) |
| **Detalle dataset (DS-01)** | `GET /transparencia/datasets/{codigo}` | `Portal\PortalDatasetController@show` / `portal/dataset/programas.blade.php` | Descargar CSV | — | Público (throttle:60,1) | Mostrar datos de DS-01 (Programas) paginados; solo validado para codigo=DS-01 |
| **Descargar CSV** | `GET /transparencia/datasets/{codigo}/descargar` | `Portal\PortalDownloadController@csv` | Streaming download | — | Público (throttle:60,1) | Descargar DS-01 completo como CSV (campos: ejercicio_fiscal, programa_clave, programa_nombre, unidad_responsable, modalidad, activo) |

### Flujo de Estados & Políticas

**Estados posibles**: `borrador` → `revision` → `aprobado` → `publicado` (o `retirado`)

**Roles**:
- **RDA (Responsable Datos Abiertos)**: Tiene `aprobar_datos_abiertos`, `ver_datasets_abiertos`, `gestionar_dataset_abierto`. Puede editar plantillas, aprobar, publicar, rechazar, retirar.
- **Editor dataset**: Tiene `gestionar_dataset_abierto`, `ver_datasets_abiertos`. Puede crear borradores, editar propios (si RDA no intervino), crear entregas, enviar a revisión propios.

**Permisos**:
- `ver_datasets_abiertos`: Acceso a índice y detalle
- `gestionar_dataset_abierto`: Crear/editar borradores, crear entregas, enviar a revisión
- `aprobar_datos_abiertos`: Editar plantillas, aprobar desde revisión, publicar, retirar, rechazar

**Transiciones**:
- `Enviar a revisión`: borrador → revision (editor + RDA)
- `Aprobar`: revision → aprobado (RDA)
- `Rechazar`: revision → borrador + motivo (RDA)
- `Publicar`: aprobado → publicado (RDA)
- `Retirar`: publicado → retirado + motivo (RDA)

### Activity Log

Todos los cambios de estado y ediciones son registrados en `Activity` (scope: DatasetAbierto) con causer (usuario), timestamp y descripción.


---

Perfecto. Ahora voy a armar el mapa completo basándome en toda la información recolectada:

## Área: dte-padron-cobertura (dte-spp)

### Padrón

| Campo | Valor |
|-------|-------|
| **URL** | `/{programa}/padron` |
| **Nombre de ruta** | `mml.padron` |
| **Componente Livewire** | `PadronPrograma` (`/home/eleacid/code/laravel/dte-spp-2026/app/Livewire/Mml/PadronPrograma.php`) |
| **Vista** | `livewire.mml.padron-programa` |
| **Permiso** | `ver_padron` (gate en mount) |
| **Propósito** | Gestionar snapshots del padrón de beneficiarios, activar/desactivar vínculo con GeoBase, visualizar KPIs por componente en modo vivo o histórico. |

**Acciones principales:**
- **Activar padrón en GeoBase** (wire:click="activarPadron") — requiere `generar_snapshot_padron`
- **Desactivar padrón en GeoBase** (wire:click="desactivarPadron") — requiere `generar_snapshot_padron`, abre modal de confirmación
- **Generar snapshot del trimestre** (wire:click="generarSnapshot") — requiere `generar_snapshot_padron`
- **Exportar Anexo 11** (route: `evaluation.anexo-11`) — requiere `exportar_reportes`
- **Exportar Padrón SHCP** (route: `evaluation.padron-shcp`) — requiere `exportar_padron_shcp`
- **Seleccionar Componente** (wire:click="seleccionarComponente") — carga snapshots y KPIs
- **Seleccionar Snapshot** (wire:click="seleccionarSnapshot") — cambia KPIs a snapshot histórico
- **Toggle Fuente** (wire:click="toggleFuente") — alterna entre modo "vivo" y "snapshot"

**Modales:**
- `desactivar-padron-{programa.id}` — confirmación antes de desactivar padrón (requiere `generar_snapshot_padron`)

**Datos consumidos:**
- GeoBaseClient: `getSnapshots()`, `getComponentCoverage()`, `getSnapshotKpis()`
- PadronProvisioningService: `register()`, `deactivate()`
- PadronSnapshotService: `generar()`
- Cache: KPIs cacheados 30s (vivo) / 60s (snapshot)

---

### Cobertura

| Campo | Valor |
|-------|-------|
| **URL** | `/{programa}/cobertura` |
| **Nombre de ruta** | `mml.cobertura` |
| **Componente Livewire** | `CoberturaPrograma` (`/home/eleacid/code/laravel/dte-spp-2026/app/Livewire/Mml/CoberturaPrograma.php`) |
| **Vista** | `livewire.mml.cobertura-programa` |
| **Permiso** | `ver_padron` (gate en mount) |
| **Propósito** | Panel de cobertura territorial (choropleth), alertas por trimestre/municipios, supuestos de Propósito y Componentes, métricas de inscripciones por estatus y municipio. |

**Acciones principales:**
- **Seleccionar Período** (wire:click="seleccionarPeriodo") — filtra cobertura por trimestre ('YYYY-QN') o all-time
- **Ver Mapa Cobertura** (ruta: `mml.cobertura.mapa`) — imagen PNG choropleth generada por MapaCoberturaProgramaController

**Estados:**
- `inactivo` — padrón no vinculado a GeoBase
- `vacio` — padrón activo pero sin beneficiarios
- `no_registrado` — programa no registrado en GeoBase
- `error` — GeoBase no disponible
- `ok` — cobertura disponible

**Alertas calculadas (solo si estado=ok):**
- **alertaTrimestre** — caída ≥10% de inscripciones vs Q anterior (amarilla si 10-24%, roja si ≥25%)
- **alertaMeta** — cobertura <50% de población_objetivo (roja si <25%, amarilla si 25-49%)
- **municipiosConDrop** — municipios con caída >30% vs Q anterior

**Datos mostrados:**
- Total beneficiarios
- Total inscripciones
- Por estatus (tabla con badges)
- Por municipio (tabla: Municipio, Inscripciones)
- Supuestos de Propósito y Componentes (si existen)
- Mapa choropleth (img con retry si falla)

**Datos consumidos:**
- GeoBaseClient: `getProgramCoverage()` (cached 60s), `getConsultaImage()`
- Períodos disponibles: Q actual + 3 previos
- Cache: coverage cacheado, mapa PNG cacheado 60s

---

### Mapa de Cobertura (Controlador)

| Campo | Valor |
|-------|-------|
| **URL** | `/{programa}/cobertura/mapa.png` |
| **Nombre de ruta** | `mml.cobertura.mapa` |
| **Controlador** | `MapaCoberturaProgramaController` (`/home/eleacid/code/laravel/dte-spp-2026/app/Http/Controllers/Mml/MapaCoberturaProgramaController.php`) |
| **Propósito** | Generar imagen PNG choropleth de cobertura por municipio, con filtro opcional por período. |

**Parámetros:**
- `period` (query string, opcional) — formato 'YYYY-QN' para filtrar por trimestre; ignorado si malformado

**Respuesta:**
- PNG choropleth con agregación por municipio (total_beneficiarios)
- Cache 60s por (programa.id, period)
- HTTP 503 si GeoBase no responde

---

### Embudo de Poblaciones

| Campo | Valor |
|-------|-------|
| **URL** | `/{programa}/etapa/5` |
| **Nombre de ruta** | `mml.etapa5` |
| **Componente Livewire** | `EmbudoPoblaciones` (`/home/eleacid/code/laravel/dte-spp-2026/app/Livewire/Mml/EmbudoPoblaciones.php`) |
| **Propósito** | Definir población de referencia, potencial y objetivo con fuentes y justificación por ejercicio fiscal. |

**Campos:**
- unidad_medida (required, string, max 100)
- referencia_cantidad (required, int ≥1)
- referencia_fuente (nullable, string, max 500)
- potencial_cantidad (required, int ≥1)
- potencial_fuente (nullable, string, max 500)
- objetivo_cantidad (required, int ≥1)
- objetivo_justificacion (nullable, string, max 1000)

**Modelo:** `PoblacionPrograma` (una por año fiscal per programa)

---

### Exportadores

| Acción | URL | Ruta | Controlador | Permiso | Formato |
|--------|-----|------|-------------|---------|---------|
| Exportar Anexo 11 | `/{programa}/anexo-11` | `evaluation.anexo-11` | `Anexo11Controller` | `exportar_reportes` | Descarga binaria (Anexo11ExportService) |
| Exportar Padrón SHCP | `/exportar/padron-shcp/{programa}` | `evaluation.padron-shcp` | `PadronShcpController` | `exportar_padron_shcp` | Descarga binaria (PadronShcpExportService) |

---


---

Perfecto. Ahora tengo toda la información necesaria. Voy a compilar el mapa del área dte-admin-acceso:

## Área: dte-admin-acceso (dte-spp)

### Dashboard Principal

| Elemento | Detalles |
|----------|----------|
| **URL** | `/dashboard` |
| **Nombre de ruta** | `dashboard` |
| **Vista/Componente** | `App\Livewire\Dashboard` (`livewire/dashboard.blade.php`) |
| **Propósito** | Dashboard principal adaptado al rol del usuario (admin, planeador, operador) con estadísticas de seguimiento, finanzas, semáforo de indicadores y notificaciones recientes |
| **Acciones principales** | Solo lectura: visualización de KPIs, estadísticas por rol, tendencias de captura, avances por programa |
| **Permiso/rol** | Sin protección específica; contenido filtrado por permisos: `revisar_avance`, `capturar_avance`, `ver_datos_financieros`, rol `admin` |
| **Modales** | Ninguno |

---

### Gestión de Usuarios

| Elemento | Detalles |
|----------|----------|
| **URL** | `/admin/usuarios` |
| **Nombre de ruta** | `admin.users` |
| **Vista/Componente** | `App\Livewire\Admin\GestionUsuarios` (`livewire/admin/gestion-usuarios.blade.php`) |
| **Propósito** | Gestionar cuentas de usuarios, invitar nuevos usuarios, filtrar por estado (activo, pendiente, inactivo), reenviar invitaciones |
| **Acciones principales** | Invitar usuario (+), reenviar invitación, activar/desactivar usuario, buscar por nombre/email, filtrar por estado |
| **Permiso/rol** | Protegido con `can:invitar_usuarios` |
| **Modales** | Formulario embebido (no modal): "Invitar Usuario" con campos: nombre, email, rol (operador/planeador/admin), unidad responsable (select teams) |
| **Datos mostrados** | KPI bar: Total usuarios, Activos (verde), Pendientes (amarillo), Inactivos (rojo); tabla con avatar, nombre, email, rol, unidad, estado, acciones |

---

### Monitoreo de IA

| Elemento | Detalles |
|----------|----------|
| **URL** | `/admin/monitoreo-ia` |
| **Nombre de ruta** | `admin.monitoreo-ia` |
| **Vista/Componente** | `App\Livewire\Admin\MonitoreoIa` (`livewire/admin/monitoreo-ia.blade.php`) |
| **Propósito** | Monitorear uso de APIs de IA (embedding), presupuestos de IA, costos, tokens consumidos por tipo, usuario y unidad responsable |
| **Acciones principales** | Filtrar por periodo (hoy/semana/mes), probar conexión API embedding, visualizar alertas de presupuesto, ver uso por tipo/usuario/UR/tendencia |
| **Permiso/rol** | Protegido con `can:administrar_usuarios` |
| **Modales** | Resultado de prueba de conexión API (embebido): estado, URL, API Key preview, latencia, modelo, dimensiones, tokens usados, costo estimado o HTTP status/error |
| **Datos mostrados** | KPI bar: Llamadas, Tokens (azul), Costo USD (ámbar), Tasa de error (rojo/verde); alertas por presupuesto excedido; tablas: uso por tipo, top 10 usuarios, por UR, tendencia diaria; presupuestos mensuales con barra de progreso |

---

### Auditoría del Sistema

| Elemento | Detalles |
|----------|----------|
| **URL** | `/admin/auditoria` |
| **Nombre de ruta** | `admin.auditoria` |
| **Vista/Componente** | `App\Livewire\Admin\Auditoria` (`livewire/admin/auditoria.blade.php`) |
| **Propósito** | Rastrear cambios en el sistema: crear, actualizar, eliminar; registros de auditoría con usuario causante, fecha, modelo afectado, descripción y cambios |
| **Acciones principales** | Filtrar por tipo de modelo, usuario, evento (creación/actualización/eliminación), rango de fechas; limpiar filtros |
| **Permiso/rol** | Protegido con `can:administrar_usuarios` |
| **Modales** | Ninguno |
| **Datos mostrados** | KPI bar: Eventos en rango, Creaciones (verde), Actualizaciones (ámbar), Eliminaciones (rojo); tabla con fecha, usuario, evento (badge), modelo (#id), descripción, cambios |

---

### Navegación Sidebar (Sección Administración)

| Elemento | Detalles |
|----------|----------|
| **Ubicación** | `resources/views/components/layout/sidebar-nav.blade.php` (líneas 228-254) |
| **Sección** | "Administración" (ícono de engranaje) |
| **Items visibles** | Usuarios (requiere `invitar_usuarios`), Monitor IA (requiere `administrar_usuarios`), Auditoría (requiere `administrar_usuarios`) |
| **Protección** | Grupo protegido: `@canany(['administrar_usuarios', 'invitar_usuarios'])` |
| **Tooltip** | Muestra enlaces a Usuarios, Monitor IA, Auditoría |

---

### Roles y Permisos Relacionados

| Rol | Descripción |
|-----|-------------|
| `admin` | Administrador del sistema (SystemRole enum) |
| `planeador` | Rol de planeación |
| `operador` | Rol operativo |
| `analista_financiero` | Acceso a datos financieros |
| `analista_juridico` | Acceso a jurídico |
| `responsable_datos_abiertos` | Acceso a transparencia |

| Permiso clave | Descripción |
|---------------|-------------|
| `administrar_usuarios` | Acceso a Monitor IA, Auditoría y funciones admin avanzadas |
| `invitar_usuarios` | Acceso a Gestión de Usuarios para invitar, reenviar, activar/desactivar |

---

### Notas de Arquitectura

- **Middleware de autenticación**: Todas las rutas requieren `auth:sanctum`, `verified`
- **Sistema de permisos**: Usa método `can()` en middleware y directivas `@can` en Blade
- **Teams/Unidades Responsables (UR)**: Integradas en invitación de usuarios, filtros del monitor IA
- **Paginación**: Usuarios (15 por página), Auditoría (25 por página)
- **Modelos auditados**: User, PedPlan, PedEje, PedTema, PedObjetivoEstrategico, MirNivel, Indicador, EvaluacionPrograma
- **Cache**: Dashboard (300s por usuario para geobase stats)


---

Perfecto. Ahora tengo suficiente información. Voy a compilar el mapa completo del área geo-padron:

## Área: geo-padron (geobase)

### BENEFICIARIOS

| URL | Nombre Ruta | Vista/Componente | Acciones Principales | Modales/Confirmaciones | Permiso/Rol | Propósito |
|-----|-------------|------------------|----------------------|----------------------|------------|----------|
| `/beneficiaries` | beneficiaries.index | Livewire: BeneficiaryList | Crear nuevo, Buscar, Filtrar (tipo, estado, municipio), Paginar | N/A | Gate: beneficiary.view | Listar beneficiarios con búsqueda por CURP hash y municipio |
| `/beneficiaries/create` | beneficiaries.create | beneficiaries/create.blade + Livewire: BeneficiaryForm | Crear beneficiario, Guardar, Seleccionar municipio | Detección automática de duplicados (P-02) | Gate: beneficiary.create | Formulario para registrar nuevo beneficiario (persona física/moral) |
| `/beneficiaries/{id}` | beneficiaries.show | beneficiaries/show.blade | Editar, Reubicar, Eliminar, Ver enrollments, Ver historial de datos, Revisar duplicados | Confirmación para eliminar | Gate: beneficiary.view | Detalle de beneficiario con PII cifrado, historial de cambios, lista de inscripciones |
| `/beneficiaries/{id}/edit` | beneficiaries.edit | beneficiaries/edit.blade + Livewire: BeneficiaryForm | Actualizar campos, Guardar cambios | N/A | Gate: beneficiary.update | Editar datos de beneficiario (sin CURP) con validación de coherencia |
| `/beneficiaries/{id}/relocate` | beneficiaries.relocate | beneficiaries/relocate.blade + Livewire: RelocateForm | Actualizar ubicación geográfica (lat/lng), Guardar | Validación coherencia (entre 0-1), Modal de confirmación | Gate: beneficiary.update | Cambiar ubicación del beneficiario para re-validar elegibilidad (observación OBSERVADO_DOMICILIO) |

**Middleware Web:** auth:sanctum, team.context, activated, require.2fa | **Middleware PII:** audit.pii (para store, update, destroy) | **Validaciones:** P-01 (desagregación PEF), P-02 (duplicidad CURP), P-05 (inconsistencias campos)

---

### INSCRIPCIONES (ENROLLMENTS)

| URL | Nombre Ruta | Vista/Componente | Acciones Principales | Modales/Confirmaciones | Permiso/Rol | Propósito |
|-----|-------------|------------------|----------------------|----------------------|------------|----------|
| `/enrollments` | enrollments.index | Livewire: EnrollmentList | Crear nueva, Buscar, Filtrar (programa, componente, estado), Paginar | N/A | Gate: enrollment.view | Listar inscripciones con estado, monto, folio evidencia |
| `/enrollments/create` | enrollments.create | enrollments/create.blade + Livewire: EnrollmentForm | Crear inscripción, Buscar beneficiario, Seleccionar programa/componente, Validar ubicación, Guardar | Busca en vivo de beneficiarios, validación geográfica en tiempo real | Gate: enrollment.create | Crear nueva inscripción (beneficiario + programa + componente) con validación P-06 (geo) |
| `/enrollments/create/{beneficiary}` | enrollments.createForBeneficiary | enrollments/create-for-beneficiary.blade | Crear inscripción para beneficiario específico, Seleccionar programa/componente | Validación geográfica | Gate: enrollment.create | Acceso directo: crear inscripción vinculada a beneficiario desde su detalle |
| `/enrollments/{id}` | enrollments.show | enrollments/show.blade | Editar (si no terminal), Eliminar, Aprobar, Rechazar, Finalizar, Cancelar, Resolver (excepciones), Ver historial estados | Formularios embebidos x-show para Aprobar/Rechazar/Finalizar/Cancelar con campo "reason" (requerido en rechazos/cancelaciones); Finalizar requiere folio_evidencia si ausente (P-07) | Gate: enrollment.view + action-specific (approve/reject/finalize/cancel) | Detalle de inscripción con validación geográfica, transiciones de estado, historial |
| `/enrollments/{id}/edit` | enrollments.edit | enrollments/edit.blade + Livewire: EnrollmentForm | Actualizar datos de inscripción (monto, observaciones, componente) | N/A | Gate: enrollment.update | Editar inscripción no-terminal (monto, observaciones) con validación P-08 (monto no excede componente) |
| POST `/enrollments/{id}/approve` | enrollments.approve | N/A (API action) | Transición SOLICITADO/EN_REVISION -> APROBADO con reason opcional | Formulario embebido en show.blade | Gate: enrollment.approve | Aprobar inscripción automáticamente (si ubicación OK) o previo a finalizar |
| POST `/enrollments/{id}/reject` | enrollments.reject | N/A (API action) | Transición -> RECHAZADO con reason requerido | Formulario embebido en show.blade | Gate: enrollment.reject | Rechazar inscripción con justificación (válido desde SOLICITADO/EN_REVISION) |
| POST `/enrollments/{id}/finalize` | enrollments.finalize | N/A (API action) | Transición APROBADO -> FINALIZADO, captura folio_evidencia si ausente (P-07), reason opcional | Formulario embebido requiere folio_evidencia (TRF/acta) si sin evidencia previa | Gate: enrollment.approve | Cerrar entrega: registra folio de comprobante (P-07: C-096 auditable proof) |
| POST `/enrollments/{id}/cancel` | enrollments.cancel | N/A (API action) | Transición APROBADO -> CANCELADO con reason requerido | Formulario embebido en show.blade | Gate: enrollment.approve | Cancelar inscripción aprobada con causa documentada |
| POST `/enrollments/{id}/resolve` | enrollments.resolve | N/A (API action) | Resuelve excepciones OBSERVADO_DOMICILIO con 3 acciones: aprobar, finalizar o cancelar + reason | Formulario embebido con hidden action field | Gate: enrollment.update | Resolver inscripciones en revisión por cambio de domicilio (acción administrativa) |

**Estados Terminales:** FINALIZADO, RECHAZADO, CANCELADO | **Middleware Web:** auth:sanctum, team.context, activated, require.2fa | **Middleware PII:** audit.pii (write ops) | **Validaciones:** P-06 (ubicación dentro área programa), P-07 (folio evidencia auditable), P-08 (monto <= unitario componente)

---

### API ENDPOINTS (Interno - Sanctum)

| URL | Controlador | Método | Habilidades Requeridas | Propósito |
|-----|------------|--------|----------------------|----------|
| POST `/api/v1/beneficiaries` | BeneficiaryController@store | store | padron:register | Registrar beneficiario (upsert CURP, detecta duplicados P-02) |
| GET `/api/v1/beneficiaries/{id}` | BeneficiaryController@show | show | padron:read | Consultar beneficiario con audit PII |
| PATCH `/api/v1/beneficiaries/{id}` | BeneficiaryController@update | update | padron:update-identity | Actualizar identidad beneficiario (nombre, dirección, contacto) |
| POST `/api/v1/enrollments` | EnrollmentController@store | store | padron:enroll | Crear inscripción con validaciones P-01, P-06 |
| GET `/api/v1/enrollments` | EnrollmentController@index | index | padron:read | Listar inscripciones filtradas por programa/status |
| GET `/api/v1/enrollments/{id}` | EnrollmentController@show | show | padron:read | Consultar inscripción con audit |
| PATCH `/api/v1/enrollments/{id}` | EnrollmentController@update | update | padron:enroll | Actualizar monto/observaciones inscripción (P-08) |
| POST `/api/v1/enrollments/{id}/approve` | EnrollmentController@approve | approve | padron:enroll | Transición a APROBADO |
| POST `/api/v1/enrollments/{id}/reject` | EnrollmentController@reject | reject | padron:enroll | Transición a RECHAZADO |
| POST `/api/v1/enrollments/{id}/finalize` | EnrollmentController@finalize | finalize | padron:enroll | Transición a FINALIZADO (P-07 evidencia) |
| POST `/api/v1/enrollments/{id}/cancel` | EnrollmentController@cancel | cancel | padron:enroll | Transición a CANCELADO |
| POST `/api/v1/validation/location` | ValidationController@location | location | padron:validate | Validar lat/lng dentro de área programa (P-06) |
| POST `/api/v1/validation/curp` | ValidationController@curp | curp | padron:validate | Verificar existencia y estado CURP |
| POST `/api/v1/programs` | ProgramController@register | register | padron:provision | Registrar programa (idempotente x spp_program_id) |
| GET `/api/v1/programs/{spp_program_id}/coverage` | ProgramController@coverage | coverage | padron:read | Cobertura de programa (enrollments por status/periodo) |
| POST `/api/v1/components` | ComponentController@register | register | padron:provision | Registrar componente MIR (idempotente x spp_mir_nivel_id) |
| GET `/api/v1/components/{spp_mir_nivel_id}/coverage` | ComponentController@coverage | coverage | padron:read | Cobertura de componente (enrollments) |

**Throttle:** geobase-read (índice), geobase-write (POST/PATCH), 15 req/min default | **Auth:** auth:sanctum (cross-team, enlace_mir allowed) | **Audit:** PiiAuditService logs read/write PII (CURP, ubicación) | **Scope:** sin team.context por diseño (dte-spp token global)

---

### VALIDACIONES PADRÓN (P-01 a P-08)

| Código | Descripción | Punto Aplicación | Recurso/Request |
|--------|-------------|------------------|-----------------|
| P-01 | Desagregación obligatoria Anexo 11 PEF | StoreBeneficiaryApiRequest, StoreBeneficiaryRequest | Validar campos requeridos según tipo beneficiario |
| P-02 | Duplicidad confirmada por CURP | BeneficiaryService::create, DuplicateDetectionService | Detecta automáticamente, marca para revisión, notifica al usuario |
| P-03 | Validaciones ROP (latente) | N/A - requiere modelo ROP cross-sistema | C-098: decisión arquitectónica pendiente |
| P-04 | Datos interinstitucionales (latente) | N/A - sin fuente de datos integrada | Requiere acuerdo interinstitucional |
| P-05 | Inconsistencias campos mismo registro | StoreBeneficiaryRequest | Valida coherencia: coherencia de ubicación (lat/lng vs municipio), fecha_nacimiento < hoy |
| P-06 | Validación geográfica (ubicación dentro área) | GeographicValidationService::validateLocation | POST /validation/location, EnrollmentForm mount/saveEnrollment |
| P-07 | Evidencia auditable entrega (folio P-07) | EnrollmentController::finalize, Enrollment model | Requiere folio_evidencia o doc. soporte para FINALIZADO; capturado en formulario show.blade |
| P-08 | Monto entregado no excede unitario | MontoNoExcedeComponente rule, UpdateEnrollmentRequest | Valida monto_entregado <= component.monto_unitario |

---

### CAMPOS PII Y CIFRADO

| Tabla | Campos Cifrados | Método Acceso | Auditoría |
|-------|-----------------|---------------|-----------|
| beneficiaries | curp_rfc, nombre, apellidos, razon_social, fecha_nacimiento, location (lat/lng), address_* | Automático (Eloquent accessors) | PiiAuditService: read/write log con user, action, timestamp |
| enrollments | Sin cifrado; audita monto_entregado, folio_evidencia, observaciones | Directo | Audit log PII si acceso a beneficiary |

---

### BÚSQUEDA Y FILTROS

**BeneficiaryList Livewire:** search (CURP hash exacto o municipio/email ilike), filterType (persona_fisica/moral), filterState, filterMunicipality, filterActivo (1/0) | **EnrollmentList Livewire:** (T.B.D - ver `/enrollments/index` Livewire) | **Paginación:** 15 items default

---

### REPORTES RELACIONADOS

| URL | Nombre Ruta | Propósito |
|-----|-------------|----------|
| GET `/api/v1/reportes/cobertura-componente` | reportes.cobertura-componente | Reporte cobertura por componente, monto_entregado, estados |
| GET `/api/v1/reportes/heatmap-points` | reportes.heatmap-points | Puntos geográficos beneficiarios para visualización |

---

### RESUMEN DE PROTECCIONES

- **Autenticación Web:** Sanctum + 2FA requerido
- **Autenticación API:** Sanctum token con habilidades (abilities:padron:*)
- **Autorización:** Policies (BeneficiaryPolicy, EnrollmentPolicy) + Gates (beneficiary.*, enrollment.*)
- **Auditoría PII:** Middleware audit.pii + PiiAuditService log read/write CURP, ubicación, datos personales
- **Roles Team-Scoped:** Analista local ve solo beneficiarios en su equipo; sysadmin/analista_global ve todos
- **Validaciones Críticas:** P-06 (geo), P-07 (evidencia), P-08 (monto)


---

Excelente. Ahora tengo toda la información que necesito. Voy a generar el mapa markdown con toda la información del área "geo-territorio":

## Área: geo-territorio (geobase)

### Módulos de Territorio

#### 1. Análisis Espacial
- **URL (path real)**: `/analisis-espacial`
- **Nombre de ruta**: `analisis-espacial`
- **Vista/Componente**: Livewire Component `App\Livewire\Analisis\AnalisisEspacial` → vista `livewire.analisis.analisis-espacial`
- **Propósito**: Interfaz interactiva para consultas geoestadísticas con filtros por municipios/regiones, capas de datos, dimensiones y agregados
- **Acciones principales**: 
  - Seleccionar municipios o regiones (toggle territorial)
  - Buscar/filtrar municipios
  - Seleccionar capas de análisis (GeoLayer)
  - Configurar dimensiones y agregados (ReportQueryBuilder)
  - Guardar consultas personalizadas
  - Exportar datos
- **Datos disponibles**: Municipios (InegiMunicipio), Regiones (RegionOaxaca), Programas, Años fiscales
- **Permiso/rol**: `report.view_all` o `layer.view_all`
- **Componentes de datos involucrados**:
  - InegiMunicipio (municipios con geometría PostGIS)
  - RegionOaxaca (8 regiones de Oaxaca con geometría)
  - InegiLocalidad (localidades dentro de municipios)
  - MarginacionIndice (índice de marginación municipal)
  - MarginacionIndiceLocalidad (índice de marginación local)
  - InegiCensoPoblacion (datos de censo de población por municipio)
  - ReporteTerritorial (vista materializada con agregados territoriales)

#### 2. API: Reporte Territorial
- **URL (path real)**: `/api/v1/geobase/territorial-report`
- **Método HTTP**: GET
- **Nombre de ruta**: (sin nombre directo)
- **Controlador**: `App\Http\Controllers\Api\Geobase\TerritorialReportController@index`
- **Propósito**: Consulta JSON de datos territoriales con filtros por programa, componente, municipio y marginación
- **Parámetros query**: `program_id`, `component_id`, `municipio_id`, `alta_marginacion` (boolean)
- **Respuesta**: JSON con array `data[]` y `meta` (total_rows, refreshed_at timestamp)
- **Fuente de datos**: Consulta en vista materializada `vw_reporte_territorial`
- **Permiso/rol**: `abilities:padron:read` (Sanctum token)

#### 3. API: Reportes Geoespaciales (6 endpoints)
- **URL base**: `/api/v1/geobase/reportes/`
- **Controlador**: `App\Http\Controllers\Api\Geobase\ReporteController`
- **Permiso/rol**: `abilities:padron:read` (Sanctum token)
- **Parámetros query comunes**: `municipio_ids[]=N`, `region_ids[]=N` (mutuamente excluyentes, opcionales)

**Endpoints por tipo de cobertura:**
1. `/heatmap-points` → GeoJSON de puntos para mapa de calor
2. `/cobertura-municipal` → Cobertura agregada por municipio
3. `/inversion-municipal` → Inversión por municipio
4. `/inversion-regional` → Inversión por región
5. `/cobertura-componente` → Cobertura por componente de programa
6. `/equidad-genero` → Desagregación por género
7. `/densidad-etnica` → Desagregación por etnia/población indígena
8. `/evolucion-temporal` → Series de tiempo

#### 4. API: Reportes Bulk (para grandes volúmenes)
- **URL base**: `/api/v1/geobase/reportes/`
- **Controlador**: `App\Http\Controllers\Api\Geobase\BulkReporteController`
- **Endpoints**:
  - `/cobertura-municipal-bulk` → Cobertura municipal en volumen
  - `/desagregacion-bulk` → Desagregaciones demográficas en volumen
  - `/cobertura-geografica-bulk` → Cobertura geográfica extendida
- **Permiso/rol**: `abilities:padron:read` (Sanctum token)

#### 5. Renderización de Polígonos (Municipios/Regiones)
- **URL (path real)**: `/render/mapa/poligono/{tipo}/{id}` (público)
- **Nombre de ruta**: `render.mapa.poligono`
- **Vista**: `render.mapa.poligono` (HTML+Leaflet con GeoJSON embebido)
- **Tipos soportados**: `municipio`, `region`
- **Propósito**: Renderizar mapa interactivo de un polígono territorial individual (municipio o región)
- **Datos**: Obtiene del mapeo ST_AsGeoJSON(geometry) de `inegi_municipios` o `regiones_oaxaca`
- **Autenticación**: Ninguna (público)

#### 6. API: Imagen de Polígono
- **URL (path real)**: `/geo/imagen/poligono/{tipo}/{id}`
- **Nombre de ruta**: `geo.imagen.poligono`
- **Controlador**: `App\Http\Controllers\Api\Geobase\GeoImageController@poligono`
- **Métodos**: GET con parámetro `format=svg|png` (default png)
- **Propósito**: Generar imagen estática (SVG o PNG) de un polígono territorial
- **Tipos**: `municipio`, `region`
- **Servicio**: `GeoImageService`
- **Permiso/rol**: Autenticado (Sanctum)

#### 7. API: Mapa de Polígono (captura Playwright)
- **URL (path real)**: `/geo/imagen/mapa/{tipo}/{id}`
- **Nombre de ruta**: `geo.imagen.mapa`
- **Controlador**: `App\Http\Controllers\Api\Geobase\GeoImageController@mapa`
- **Parámetros**: `width` (default 800), `height` (default 600)
- **Propósito**: Captura de pantalla con Playwright de mapa interactivo Leaflet para polígono
- **Servicio**: `MapImageService`
- **Permiso/rol**: Autenticado (Sanctum)

#### 8. Renderización de Consulta Temática
- **URL (path real)**: `/render/mapa/consulta?key={cache_key}`
- **Nombre de ruta**: `render.mapa.consulta`
- **Vista**: `render.mapa.poligono` (mapa coroplético de municipios)
- **Propósito**: Renderizar mapa temático de resultados de consulta con choropleth coloreado por agregado
- **Datos**: Lee configuración de consulta de caché Redis, ejecuta `ReportQueryBuilder`, obtiene geometrías de `inegi_municipios`
- **Visualización**: Leyenda de colores, panel de KPIs (total, promedio, municipios con datos)
- **Autenticación**: Ninguna (acceso por cache key)

#### 9. API: Imagen de Consulta Temática
- **URL (path real)**: `/geo/imagen/consulta` (POST)
- **Nombre de ruta**: `geo.imagen.consulta`
- **Controlador**: `App\Http\Controllers\Api\Geobase\GeoImageController@consulta`
- **Parámetros POST (JSON)**: 
  - `group_by[]` (requerido, array de dimensiones)
  - `aggregates[]` (requerido, array de agregados)
  - `filters[]` (opcional, condiciones)
- **Propósito**: Captura Playwright de mapa temático de resultados de consulta
- **Servicio**: `MapImageService`
- **Permiso/rol**: Autenticado (Sanctum), throttled geobase-write

#### 10. API: Datos Abiertos - GeoJSON (públicos)
- **URL (path real)**: `/api/v1/datos-abiertos/municipios/geojson`
- **Nombre de ruta**: (sin nombre)
- **Controlador**: `App\Http\Controllers\Api\DatosAbiertos\GeojsonController@municipalities`
- **Parámetros**: `zoom` (default 8), `programa_id` (opcional para filtrado)
- **Propósito**: Exportar GeoJSON de municipios de Oaxaca (público, sin autenticación)
- **Headers**: `Content-Type: application/geo+json`, `Cache-Control: public, max-age=3600`
- **Servicio**: `GeojsonBuilderService`

#### 11. API: Datos Abiertos - GeoJSON Regiones
- **URL (path real)**: `/api/v1/datos-abiertos/regiones/geojson`
- **Nombre de ruta**: (sin nombre)
- **Controlador**: `App\Http\Controllers\Api\DatosAbiertos\GeojsonController@regions`
- **Parámetros**: `zoom` (default 8)
- **Propósito**: Exportar GeoJSON de 8 regiones de Oaxaca (público)
- **Headers**: `Content-Type: application/geo+json`, `Cache-Control: public, max-age=3600`

#### 12. API: GeoJSON de Programa
- **URL (path real)**: `/api/v1/datos-abiertos/programas/{programa}/geojson`
- **Nombre de ruta**: (sin nombre)
- **Controlador**: `App\Http\Controllers\Api\DatosAbiertos\GeojsonController@show`
- **Parámetros**: `zoom` (default 8)
- **Propósito**: GeoJSON de cobertura de un programa específico por municipio (público)
- **Headers**: `Content-Type: application/geo+json`, `Cache-Control: public, max-age=3600`

#### 13. API: Capas Geoespaciales (Layers)
- **URL (path real)**: `/api/v1/geobase/layers/`
- **Nombre de ruta**: (sin nombre)
- **Controlador**: `App\Http\Controllers\Api\Geobase\GeoLayerController`
- **Métodos**: 
  - GET `/` → listar todas las capas activas
  - GET `/{geoLayer}` → detalle de una capa
- **Modelo**: `GeoLayer` (tabla `geo_layers`, propiedades: name, slug, category, is_universal)
- **Propósito**: Catálogo de capas disponibles para análisis espacial
- **Permiso/rol**: `abilities:padron:read` (Sanctum token)

#### 14. Web: Pantalla de Reportes
- **URL (path real)**: `/reportes/{slug}` (GET)
- **Nombre de ruta**: `reportes.show`
- **Vista**: `livewire.reportes.report-page-wrapper`
- **Slugs válidos**: `heatmap`, `cobertura`, `inversion`, `componente`, `equidad-genero`, `densidad-etnica`, `evolucion-temporal`
- **Propósito**: Dashboard de reportes territoriales por tema
- **Componente**: Livewire `App\Livewire\Reportes\ReportePage`
- **Permiso/rol**: `report.view_all` o `report.export`

#### 15. Web: Índice de Reportes
- **URL (path real)**: `/reportes` (GET)
- **Nombre de ruta**: `reportes.index`
- **Propósito**: Redirecciona a `/reportes/heatmap` (primer reporte)
- **Comportamiento**: Redireccionamiento 302

#### 16. Web: API Data Endpoints para Reportes
- **URL base**: `/reportes/data/`
- **Controlador**: `App\Http\Controllers\Api\Geobase\ReporteController`
- **Endpoints**: Los mismos 8 reportes que en API v1 pero accesibles desde sesión web (no requieren Sanctum)
  - `/heatmap-points`
  - `/cobertura-municipal`
  - `/inversion-municipal`
  - `/inversion-regional`
  - `/cobertura-componente`
  - `/equidad-genero`
  - `/densidad-etnica`
  - `/evolucion-temporal`
- **Autenticación**: Sesión web (auth:sanctum + verified + 2fa)

#### 17. Web: Exportación desde Análisis Espacial
- **URL (path real)**: `/analisis-espacial/export` (POST)
- **Nombre de ruta**: `analisis-espacial.export`
- **Controlador**: `App\Http\Controllers\Api\Geobase\ReportExportController@export`
- **Propósito**: Exportar resultados de consulta de análisis (CSV/Excel presumiblemente)
- **Permiso/rol**: Autenticado con sesión web
- **Throttle**: Interno (session auth, no Sanctum abilities)

#### 18. Web: Ejecución de Consultas (Análisis Espacial)
- **URL (path real)**: `/analisis-espacial/query` (POST)
- **Nombre de ruta**: `analisis-espacial.query`
- **Controlador**: `App\Http\Controllers\Web\AnalisisEspacialController@query`
- **Propósito**: Procesar y ejecutar consultas geoestadísticas desde panel de análisis
- **Parámetros**: Configuración de dimensiones, agregados, filtros territoriales
- **Respuesta**: JSON con resultados de consulta
- **Permiso/rol**: Autenticado

#### 19. API: Padrón SHCP (con CURP descifrado)
- **URL (path real)**: `/api/v1/geobase/padron/shcp`
- **Nombre de ruta**: (sin nombre)
- **Controlador**: `App\Http\Controllers\Api\Geobase\PadronShcpController@export`
- **Propósito**: Exportar padrón en formato SHCP con datos territoriales y PII descifrados
- **Nota**: Incluye geometría/municipio pero es un endpoint de padrón (no exclusivamente territorio)
- **Permiso/rol**: `abilities:padron:export-shcp` (alcance muy restrictivo)

### Modelos de Datos Territorio

**InegiMunicipio** (`inegi_municipios`)
- id, clave (clave INEGI), nombre, geometry (MultiPolygon PostGIS), estado_id, region_id, version, imported_at
- Relaciones: estado, región, localidades, censo población, marginación municipal

**InegiLocalidad** (`inegi_localidades`)
- id, municipio_id, clave (clave INEGI), nombre, geometry (Point/Polygon), version, imported_at
- Relaciones: municipio, marginación localidad

**InegiEstado** (`inegi_estados`)
- id, clave, nombre, geometry (MultiPolygon), version, imported_at
- Relaciones: municipios

**RegionOaxaca** (`regiones_oaxaca`)
- id, nombre, geometry (MultiPolygon)
- 8 regiones (Cañada, Costa, Istmo, Mixe, Tuxtepec, Sierra Norte, Sierra Sur, Valles Centrales)
- Relaciones: municipios

**InegiCensoPoblacion** (`inegi_censo_poblacion`)
- municipio_id, pobtot, pobfem, pobmas, pob0_14, pob15_64, pob65_mas, p3ym_hli, pob_afro, pcon_disc, graproes, psinder, pea, pdesocup, imported_at
- Desagregaciones demográficas: edad, género, población indígena, discapacidad

**MarginacionIndice** (`marginacion_indices`)
- municipio_id, indice_marginacion (decimal:5), grado (muy_alto, alto, medio, bajo), anio, fuente
- Indica marginación CONAPO municipal anual

**MarginacionIndiceLocalidad** (`marginacion_indices_localidad`)
- localidad_id, indice_marginacion (decimal:5), grado, anio, fuente
- Indica marginación CONAPO a nivel localidad

**ReporteTerritorial** (vista materializada `vw_reporte_territorial`)
- Agregaciones cruzadas: municipio_id, program_id, component_id, municipio_nombre, grado_marginacion, total_beneficiarios, total_enrollments, monto_total, por_genero, por_grupo_edad, por_indigena, por_pueblo, por_discapacidad, por_tipo_discapacidad, pobreza_porcentaje, refreshed_at

### Permisos/Gates Utilizados

- `report.view_all` - Ver todos los reportes (requerido para acceder a Análisis Espacial y Reportes)
- `layer.view_all` - Ver todas las capas (alternativa a report.view_all)
- `report.export` - Exportar datos de reportes
- `abilities:padron:read` - Lectura de padrón (API Sanctum)
- `abilities:padron:export` - Exportación de padrón (API Sanctum)
- `abilities:padron:export-shcp` - Exportación en formato SHCP (muy restrictivo)

### Middlewares de Protección

- `auth:sanctum` - Autenticación con token Sanctum (API)
- `verified`, `team.context`, `activated`, `require.2fa` - Autenticación web con sesión
- `abilities:padron:*` - Validación de permisos Sanctum por ability
- `throttle:geobase-read`, `throttle:geobase-write` - Rate limiting

### Servicios Clave

- `ReportQueryBuilder` - Constructor dinámico de consultas SQL con dimensiones y agregados
- `GeojsonBuilderService` - Construcción de FeatureCollections GeoJSON con PostGIS
- `GeoImageService` - Generación de imágenes SVG/PNG de polígonos territoriales
- `MapImageService` - Captura Playwright de mapas Leaflet interactivos


---

Perfecto. Tengo toda la información que necesito. Voy a generar el mapa del área geo-analisis:

## Área: geo-analisis (geobase)

### Página: Análisis Espacial (Consultas Interactivas)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `/analisis-espacial` |
| **Nombre ruta** | `analisis-espacial` |
| **Vista/Componente** | Livewire: `/app/Livewire/Analisis/AnalisisEspacial.php`<br>Blade: `/resources/views/livewire/analisis/analisis-espacial.blade.php` |
| **Acciones/Botones** | Ejecutar Consulta (POST /analisis-espacial/query), Guardar (saveQuery), Exportar CSV (POST /analisis-espacial/export), Restablecer Vista, Limpiar Mapa, Cargar Consulta Guardada |
| **Modales** | Prompt para nombre de consulta al guardar |
| **Permisos** | `auth:sanctum`, `config('jetstream.auth_session')`, `verified`, `team.context`, `activated`, `require.2fa` |
| **Propósito** | Panel interactivo de análisis territorial con choropleth dinámico, filtros (programa, género, estatus, fechas), selección de municipios/regiones, desglose por dimensiones, indicadores agregados y vista tabulada de resultados. |

---

### Endpoint: Query API

| Propiedad | Valor |
|-----------|-------|
| **URL** | `POST /analisis-espacial/query` |
| **Nombre ruta** | `analisis-espacial.query` |
| **Controlador** | `AnalisisEspacialController::query()` |
| **Parámetros** | `group_by[]` (array dimensiones), `aggregates[]` (array indicadores), `filters` (objeto), `sort` (opcional) |
| **Respuesta** | JSON: `{ data: [], columns: [] }` |
| **Permisos** | Web session auth (no Sanctum token) |
| **Propósito** | Construye y ejecuta consulta con ReportQueryBuilder, retorna datos tabulados agrupados por dimensiones seleccionadas. |

---

### Endpoint: Export CSV

| Propiedad | Valor |
|-----------|-------|
| **URL** | `POST /analisis-espacial/export` |
| **Nombre ruta** | `analisis-espacial.export` |
| **Controlador** | `ReportExportController::export()` |
| **Parámetros** | `group_by[]`, `aggregates[]`, `filters`, `sort` (opcional) |
| **Respuesta** | CSV streaming (UTF-8 BOM) con filename `reporte-YYYY-MM-DD-HHmmss.csv` |
| **Permisos** | Web session auth |
| **Propósito** | Exporta en streaming datos de análisis territorial como CSV con encoding Excel. |

---

### Endpoint: Render Polígono (Interno)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `GET /render/mapa/poligono/{tipo}/{id}` |
| **Nombre ruta** | `render.mapa.poligono` |
| **Parámetros** | `tipo` (municipio\|region), `id` (integer) |
| **Vista** | `/resources/views/render/mapa/poligono.blade.php` (Leaflet simple) |
| **Propósito** | Renderiza polígono geográfico en Leaflet para captura by Playwright. |

---

### Endpoint: Render Mapa de Consulta (Interno)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `GET /render/mapa/consulta` |
| **Nombre ruta** | `render.mapa.consulta` |
| **Parámetros** | `key` (cache key con config JSON) |
| **Vista** | `/resources/views/render/mapa/consulta.blade.php` (choropleth con KPI legend) |
| **Propósito** | Renderiza mapa choropleth municipal con datos de consulta y panel KPI para captura Playwright. |

---

### Endpoint: Imagen de Polígono (API v1)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `GET /geo/imagen/poligono/{tipo}/{id}` |
| **Nombre ruta** | `geo.imagen.poligono` |
| **Controlador** | `GeoImageController::poligono()` |
| **Parámetros** | `tipo` (municipio\|region), `id`, `format` (svg\|png, default png) |
| **Respuesta** | PNG o SVG binario |
| **Permisos** | Web session auth |
| **Propósito** | Captura y retorna geometría de polígono (municipio/región) como imagen PNG/SVG via Playwright. |

---

### Endpoint: Imagen de Mapa Polígono (API v1)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `GET /geo/imagen/mapa/{tipo}/{id}` |
| **Nombre ruta** | `geo.imagen.mapa` |
| **Controlador** | `GeoImageController::mapa()` |
| **Parámetros** | `tipo`, `id`, `width` (default 800), `height` (default 600) |
| **Respuesta** | PNG binario (mapa Leaflet renderizado) |
| **Permisos** | Web session auth |
| **Propósito** | Captura mapa Leaflet de polígono con tiles OSM via Playwright en dimensiones especificadas. |

---

### Endpoint: Imagen de Consulta (API v1)

| Propiedad | Valor |
|-----------|-------|
| **URL** | `POST /geo/imagen/consulta` |
| **Nombre ruta** | `geo.imagen.consulta` |
| **Controlador** | `GeoImageController::consulta()` |
| **Parámetros** | `group_by[]` (required), `aggregates[]` (required), `filters` (optional) |
| **Respuesta** | PNG binario (choropleth municipal con KPIs) |
| **Permisos** | Web session auth |
| **Propósito** | Captura choropleth municipal de consulta con cálculos en tiempo real via Playwright. |

---

### API v1 Geobase (Sanctum)

| Propiedad | Valor |
|-----------|-------|
| **Namespace** | `routes/api/geobase.php` (prefix `/api/v1/geobase`) |
| **Autenticación** | `auth:sanctum` + `throttle:geobase-read` + `TrackSystemTokenUsage` |
| **Nota** | Team context middleware omitido intencionalmente para acceso por tokens globales (ej. enlace_mir) |

**Endpoints relacionados a geo-análisis:**

- **Imagen** (prefix `/imagen`, require `padron:read`)
  - `GET /poligono/{tipo}/{id}` — Captura polígono (SVG/PNG)
  - `GET /mapa/{tipo}/{id}` — Captura mapa polígono
  - `POST /consulta` — Captura choropleth consulta (throttle geobase-write)

- **Reportes** (prefix `/reportes`, require `padron:read`)
  - `GET /heatmap-points` — Puntos de calor
  - `GET /cobertura-municipal` — Cobertura municipal
  - `GET /inversion-municipal` — Inversión municipal
  - `GET /inversion-regional` — Inversión regional
  - `GET /cobertura-componente` — Cobertura por componente
  - `GET /equidad-genero` — Equidad de género
  - `GET /densidad-etnica` — Densidad étnica
  - `GET /evolucion-temporal` — Evolución temporal
  - `GET /cobertura-municipal-bulk`, `/desagregacion-bulk`, `/cobertura-geografica-bulk` — Bulk exports

- **Consultas Guardadas** (prefix `/saved-queries`)
  - `GET /` (require `padron:read`) — Lista consultas guardadas
  - `GET /{id}` (require `padron:read`) — Obtiene una consulta
  - `POST /` (require `padron:export`) — Crea consulta
  - `DELETE /{id}` (require `padron:export`) — Elimina consulta
  - `POST /{id}/execute` (require `padron:export`) — Ejecuta y exporta como CSV

- **Report Export** (prefix `/reports`)
  - `POST /export` (require `padron:export`) — Exporta reporte CSV
  - `GET /export/metadata` (require `padron:read`) — Dimensiones, agregados, filtros disponibles

---

### Servicios Core

| Servicio | Ubicación | Propósito |
|----------|-----------|----------|
| **ReportQueryBuilder** | `/app/Services/ReportQueryBuilder.php` | Constructor de queries con: dimensiones (municipio, región, programa, componente, género, etnia, tipo_apoyo, grupo_edad, discapacidad, ejercicio, status), agregados (total_beneficiarios, total_enrollments, monto_total, monto_promedio), filtros (program_id, spp_program_id, component_id, municipio_ids, region_ids, genero, etnia_id, es_indigena, discapacidad, date_from, date_to, status, ejercicio) |
| **MapImageService** | `/app/Services/MapImageService.php` | Captura Playwright: `capturePolygon()`, `captureConsulta()` con caching temporal y rendering via routes internas |
| **GeoImageService** | (no mostrado) | Genera SVG/PNG de geometrías via PostGIS |
| **TeamContext** | (referenciado) | Scoping por team en queries y SavedQuery |

---

### Modelos Clave

| Modelo | Relaciones | Propósito |
|--------|-----------|----------|
| **SavedQuery** | belongsTo Team, belongsTo User (creator) | Almacena configuración de consultas (group_by, aggregates, filters) con TeamScope global |
| **GeoLayer** | (no mostrado) | Capas geográficas activas (id, name, slug, category, is_universal) |
| **InegiMunicipio** | hasMany RegionOaxaca | Municipios de Oaxaca con geometrías ST_Geometry |
| **RegionOaxaca** | (8 regiones) | Regiones de Oaxaca con geometrías |

---

### Flujos Principales

1. **Análisis Interactivo:** Usuario selecciona territorio (municipios/regiones), desglose (dimensiones), indicadores → POST `/analisis-espacial/query` → ReportQueryBuilder ejecuta → Leaflet choropleth con tooltips de datos
2. **Guardar Consulta:** Livewire `saveQuery()` → SavedQuery::create() → Recarga lista → Alert
3. **Exportar CSV:** POST `/analisis-espacial/export` → StreamedResponse chunk por 500 filas
4. **Captura de Mapa:** `MapImageService::capture*()` → Playwright Node.js en container → `/render/mapa/*` → screenshot PNG
5. **API Consultas Guardadas:** Sanctum authenticated, ejecuta y exporta CSV via `/api/v1/geobase/saved-queries/{id}/execute`


---

Perfecto. Ya tengo suficiente información. Voy a crear el mapa del área "geo-api-integracion".

## Área: geo-api-integracion (geobase)

### API Interna M2M (v1/geobase) - Sanctum + Abilities

#### Padron y Registro de Beneficiarios
- **URL**: `/api/v1/geobase/beneficiaries` | **POST** | Crear beneficiario
  - **Permiso**: `abilities:padron:register` + `throttle:geobase-write`
  - **Propósito**: Registrar nuevo beneficiario en el padrón

- **URL**: `/api/v1/geobase/beneficiaries/{beneficiary}` | **GET** | Consultar beneficiario
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener datos del beneficiario

- **URL**: `/api/v1/geobase/beneficiaries/{beneficiary}` | **PATCH** | Actualizar identidad
  - **Permiso**: `abilities:padron:update-identity` + `throttle:geobase-write`
  - **Propósito**: Actualizar identidad del beneficiario (CURP, identidad oficial)

#### Inscripciones y Validación de Estado
- **URL**: `/api/v1/geobase/enrollments` | **POST** | Crear inscripción
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Registrar inscripción de beneficiario en programa

- **URL**: `/api/v1/geobase/enrollments` | **GET** | Listar inscripciones
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Consultar listado de inscripciones con paginación cursor

- **URL**: `/api/v1/geobase/enrollments/{enrollment}` | **GET** | Ver inscripción
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener detalles de una inscripción

- **URL**: `/api/v1/geobase/enrollments/{enrollment}` | **PATCH** | Actualizar inscripción
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Modificar datos de inscripción

- **URL**: `/api/v1/geobase/enrollments/{enrollment}/approve` | **POST** | Aprobar
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Transición de estado: aprobación de inscripción

- **URL**: `/api/v1/geobase/enrollments/{enrollment}/reject` | **POST** | Rechazar
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Transición de estado: rechazar inscripción

- **URL**: `/api/v1/geobase/enrollments/{enrollment}/finalize` | **POST** | Finalizar
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Transición de estado: marcar como completada

- **URL**: `/api/v1/geobase/enrollments/{enrollment}/cancel` | **POST** | Cancelar
  - **Permiso**: `abilities:padron:enroll` + `throttle:geobase-write`
  - **Propósito**: Transición de estado: cancelación

#### Validación de Datos
- **URL**: `/api/v1/geobase/validation/location` | **POST** | Validar geolocalización
  - **Permiso**: `abilities:padron:validate` + `throttle:geobase-write`
  - **Propósito**: Validar si coordenadas (lat/lng) aplican a cobertura de programa/componente

- **URL**: `/api/v1/geobase/validation/curp` | **POST** | Validar CURP existencia
  - **Permiso**: `abilities:padron:validate` + `throttle:geobase-write`
  - **Propósito**: Verificar existencia de beneficiario por CURP

#### Programas y Componentes
- **URL**: `/api/v1/geobase/programs` | **POST** | Registrar programa
  - **Permiso**: `abilities:padron:provision` + `throttle:geobase-write`
  - **Propósito**: Registrar/actualizar programa desde dte-spp (lookup: spp_program_id)

- **URL**: `/api/v1/geobase/programs/{program:spp_program_id}/coverage` | **GET** | Cobertura programa
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener cobertura por estado, período, municipios

- **URL**: `/api/v1/geobase/programs/{program:spp_program_id}/geometry` | **GET** | Geometría programa
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener GeoJSON de cobertura geográfica

- **URL**: `/api/v1/geobase/programs/{program:spp_program_id}/atendida-proposito` | **GET** | Población atendida
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Beneficiarios únicos que recibieron apoyo (ejercicio fiscal)

- **URL**: `/api/v1/geobase/programs/{program:spp_program_id}/montos-entregados` | **GET** | Montos entregados
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Totales de montos por período y agregaciones

- **URL**: `/api/v1/geobase/components` | **POST** | Registrar componente
  - **Permiso**: `abilities:padron:provision` + `throttle:geobase-write`
  - **Propósito**: Registrar/actualizar componente desde dte-spp (lookup: spp_mir_nivel_id)

- **URL**: `/api/v1/geobase/components/{component:spp_mir_nivel_id}/coverage` | **GET** | Cobertura componente
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Cobertura de componente

#### Snapshots Criptográficos
- **URL**: `/api/v1/geobase/snapshots` | **GET** | Listar snapshots
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Listar snapshots con paginación

- **URL**: `/api/v1/geobase/snapshots/{snapshot}` | **GET** | Ver snapshot
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener detalles de snapshot (periodo, hash SHA256)

- **URL**: `/api/v1/geobase/snapshots/generate` | **POST** | Generar snapshot
  - **Permiso**: `abilities:padron:snapshot` + `throttle:geobase-write`
  - **Propósito**: Crear snapshot criptográfico de programa/componente para período (idempotente)
  - **Evento**: Dispara `snapshot.generated` webhook

- **URL**: `/api/v1/geobase/snapshots/{snapshot}/verify` | **POST** | Verificar integridad
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Validar hash SHA256 de snapshot

- **URL**: `/api/v1/geobase/snapshots/{snapshot}/download` | **GET** | Descargar archivo
  - **Permiso**: `abilities:padron:export`
  - **Propósito**: Obtener snapshot en formato comprimido

#### Reportes Geoespaciales (Públicos con auth:sanctum)
- **URL**: `/api/v1/geobase/reportes/heatmap-points` | **GET** | Puntos de calor
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Puntos georreferenciados para mapas de calor

- **URL**: `/api/v1/geobase/reportes/cobertura-municipal` | **GET** | Cobertura municipal
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Agregación de cobertura por municipio

- **URL**: `/api/v1/geobase/reportes/inversion-municipal` | **GET** | Inversión municipal
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Montos invertidos por municipio

- **URL**: `/api/v1/geobase/reportes/inversion-regional` | **GET** | Inversión regional
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Montos invertidos por región

- **URL**: `/api/v1/geobase/reportes/cobertura-componente` | **GET** | Cobertura por componente
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Desagregación de cobertura por componente

- **URL**: `/api/v1/geobase/reportes/equidad-genero` | **GET** | Equidad de género
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Análisis desagregado por género

- **URL**: `/api/v1/geobase/reportes/densidad-etnica` | **GET** | Densidad étnica
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Distribución de beneficiarios por etnia

- **URL**: `/api/v1/geobase/reportes/evolucion-temporal` | **GET** | Evolución temporal
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Series temporales de cobertura

#### Reportes Bulk (Procesamiento por lotes)
- **URL**: `/api/v1/geobase/reportes/cobertura-municipal-bulk` | **GET** | Cobertura bulk
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Múltiples programas, múltiples ejercicios fiscales en una consulta

- **URL**: `/api/v1/geobase/reportes/desagregacion-bulk` | **GET** | Desagregación bulk
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Desagregación para múltiples programas en ejercicio

- **URL**: `/api/v1/geobase/reportes/cobertura-geografica-bulk` | **GET** | Cobertura geográfica bulk
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Cobertura geográfica para múltiples programas

#### Exportación de Reportes (CSV)
- **URL**: `/api/v1/geobase/reports/export` | **POST** | Exportar reporte
  - **Permiso**: `abilities:padron:export` + `throttle:geobase-write`
  - **Propósito**: Exportar consulta personalizada a CSV con streaming
  - **Parámetros**: group_by[], aggregates[], filters[], sort

- **URL**: `/api/v1/geobase/reports/export/metadata` | **GET** | Metadatos de export
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener dimensiones, agregados y filtros disponibles

#### Padrón en Formato SHCP
- **URL**: `/api/v1/geobase/padron/shcp` | **GET** | Exportar padrón SHCP
  - **Permiso**: `abilities:padron:export-shcp`
  - **Propósito**: Exportar padrón con CURP descifrado (scope estricto)

#### Imágenes y Mapas (Server-side rendering)
- **URL**: `/api/v1/geobase/imagen/poligono/{tipo}/{id}` | **GET** | Imagen polígono
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Renderizar polígono (municipio/región) como SVG o PNG
  - **Parámetros**: format (svg|png, default: png)

- **URL**: `/api/v1/geobase/imagen/mapa/{tipo}/{id}` | **GET** | Imagen mapa
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Renderizar mapa interactivo capturado con Playwright
  - **Parámetros**: width, height

- **URL**: `/api/v1/geobase/imagen/consulta` | **POST** | Imagen de consulta
  - **Permiso**: `abilities:padron:read` + `throttle:geobase-write`
  - **Propósito**: Capturar mapa de consulta personalizada como PNG
  - **Parámetros**: group_by[], aggregates[], filters[]

#### Capas Geográficas
- **URL**: `/api/v1/geobase/layers` | **GET** | Listar capas
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener capas geográficas disponibles

- **URL**: `/api/v1/geobase/layers/{geoLayer}` | **GET** | Ver capa
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener detalle de una capa

#### Asociaciones Espaciales
- **URL**: `/api/v1/geobase/spatial-associations` | **GET** | Listar asociaciones
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener asociaciones geoespaciales

- **URL**: `/api/v1/geobase/spatial-associations/{spatialAssociation}` | **GET** | Ver asociación
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Detalles de asociación

- **URL**: `/api/v1/geobase/spatial-associations` | **POST** | Crear asociación
  - **Permiso**: `abilities:padron:register` + `throttle:geobase-write`
  - **Propósito**: Crear nueva asociación espacial

- **URL**: `/api/v1/geobase/spatial-associations/{spatialAssociation}` | **DELETE** | Eliminar
  - **Permiso**: `abilities:padron:register` + `throttle:geobase-write`
  - **Propósito**: Eliminar asociación

#### Consultas Guardadas
- **URL**: `/api/v1/geobase/saved-queries` | **GET** | Listar consultas
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Obtener consultas guardadas

- **URL**: `/api/v1/geobase/saved-queries/{savedQuery}` | **GET** | Ver consulta
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Detalles de consulta guardada

- **URL**: `/api/v1/geobase/saved-queries` | **POST** | Guardar consulta
  - **Permiso**: `abilities:padron:export` + `throttle:geobase-write`
  - **Propósito**: Crear nueva consulta reutilizable

- **URL**: `/api/v1/geobase/saved-queries/{savedQuery}` | **DELETE** | Eliminar
  - **Permiso**: `abilities:padron:export` + `throttle:geobase-write`
  - **Propósito**: Eliminar consulta guardada

- **URL**: `/api/v1/geobase/saved-queries/{savedQuery}/execute` | **POST** | Ejecutar
  - **Permiso**: `abilities:padron:export` + `throttle:geobase-write`
  - **Propósito**: Ejecutar consulta guardada

#### Reporte Territorial
- **URL**: `/api/v1/geobase/territorial-report` | **GET** | Reporte territorial
  - **Permiso**: `abilities:padron:read`
  - **Propósito**: Agregación territorial trimestral

---

### API Pública (v1/datos-abiertos) - Sin autenticación

#### Beneficiarios Públicos (Anonimizados)
- **URL**: `/api/v1/datos-abiertos/beneficiarios` | **GET** | Listar beneficiarios
  - **Permiso**: Público (`throttle:public-api`)
  - **Propósito**: Acceso público a datos abiertos anonimizados de beneficiarios
  - **Filtros**: municipio, tipo, per_page

- **URL**: `/api/v1/datos-abiertos/beneficiarios/{beneficiario}` | **GET** | Ver beneficiario
  - **Permiso**: Público
  - **Propósito**: Obtener detalle de beneficiario anonimizado

#### Inscripciones Públicas (Anonimizadas)
- **URL**: `/api/v1/datos-abiertos/inscripciones` | **GET** | Listar inscripciones
  - **Permiso**: Público
  - **Propósito**: Acceso público a inscripciones anonimizadas con paginación cursor
  - **Filtros**: per_page

- **URL**: `/api/v1/datos-abiertos/inscripciones/{inscripcion}` | **GET** | Ver inscripción
  - **Permiso**: Público
  - **Propósito**: Obtener detalle de inscripción anonimizada

#### GeoJSON Públicos
- **URL**: `/api/v1/datos-abiertos/programas/{programa}/geojson` | **GET** | GeoJSON programa
  - **Permiso**: Público
  - **Propósito**: Geometría de cobertura en formato GeoJSON

- **URL**: `/api/v1/datos-abiertos/municipios/geojson` | **GET** | GeoJSON municipios
  - **Permiso**: Público
  - **Propósito**: Límites administrativos de municipios

- **URL**: `/api/v1/datos-abiertos/regiones/geojson` | **GET** | GeoJSON regiones
  - **Permiso**: Público
  - **Propósito**: Límites administrativos de regiones

#### Estadísticas Públicas
- **URL**: `/api/v1/datos-abiertos/stats` | **GET** | Estadísticas generales
  - **Permiso**: Público
  - **Propósito**: Totales y agregaciones generales

- **URL**: `/api/v1/datos-abiertos/programas/{programa}/cobertura` | **GET** | Cobertura programa
  - **Permiso**: Público
  - **Propósito**: Estadísticas de cobertura por programa

#### Exportaciones CSV Públicas
- **URL**: `/api/v1/datos-abiertos/export/beneficiarios.csv` | **GET** | Exportar beneficiarios
  - **Permiso**: Público
  - **Propósito**: Descarga CSV de beneficiarios anonimizados

- **URL**: `/api/v1/datos-abiertos/export/inscripciones.csv` | **GET** | Exportar inscripciones
  - **Permiso**: Público
  - **Propósito**: Descarga CSV de inscripciones anonimizadas

---

### Sistema de Tokens Sanctum y Webhooks

#### Tokens del Sistema (SystemToken)
- **Modelo**: `/home/eleacid/code/laravel/geobase/app/Models/SystemToken.php`
- **Seeder**: `/home/eleacid/code/laravel/geobase/database/seeders/SystemTokenSeeder.php`
- **Relación**: belongsTo PersonalAccessToken (Sanctum)
- **Tokens predefinidos**:
  - `mir`: Scopes [padron:read, padron:snapshot, padron:provision, padron:export-shcp]
  - `operadores-ur`: Scopes [padron:register, padron:enroll, padron:read, padron:update-identity]
  - `sistema-tramites`: Scopes [padron:validate]
  - `contraloria`: Scopes [padron:read, padron:export]
  - `test`: Scopes [todos]

#### Panel de Gestión de Tokens Web
- **URL**: `/user/api-tokens` | **GET** | Panel de tokens (Jetstream)
  - **Vista**: `/home/eleacid/code/laravel/geobase/resources/views/api/index.blade.php`
  - **Componente Livewire**: `@livewire('api-token-manager')` + `@livewire('webhook-subscription-manager')`
  - **Acciones**: Crear token, ver scopes disponibles, eliminar token
  - **Protección**: auth:sanctum + team.context (solo propietarios de equipo)

#### Webhooks y Suscripciones
- **Componente Web**: `WebhookSubscriptionManager` (`/home/eleacid/code/laravel/geobase/app/Livewire/WebhookSubscriptionManager.php`)
  - **Ubicación**: Renderizado en `/user/api-tokens` bajo `@livewire('webhook-subscription-manager')`
  - **Acciones principales**:
    - **Crear suscripción**: POST (nombre, endpoint_url, eventos seleccionados)
    - **Activar/Desactivar**: PATCH (is_active)
    - **Eliminar**: DELETE con modal de confirmación
    - **Ver secreto**: Modal de visualización (una sola vez)

- **Eventos disponibles** (WebhookSubscriptionManager::$availableEvents):
  - `enrollment.status_changed`: Cambio de estado de inscripción
  - `enrollment.observed`: Inscripción observada por domicilio
  - `beneficiary.relocated`: Beneficiario cambió de ubicación
  - `snapshot.generated`: Snapshot criptográfico generado
  - `sync.processed`: Sincronización offline procesada

- **Listener**: `DispatchWebhooks` (`/home/eleacid/code/laravel/geobase/app/Listeners/DispatchWebhooks.php`)
  - Mapea eventos de aplicación a payloads de webhook
  - Eventos: EnrollmentStatusChanged, EnrollmentObserved, BeneficiaryCreated, BeneficiaryLocationUpdated, OfflineSyncProcessed, SnapshotGenerated
  - Llamadas a `WebhookService::dispatch(eventType, payload)`

- **Modelos**:
  - `WebhookSubscription`: Almacena configuración de suscripciones (nombre, endpoint_url, eventos[], signing_secret, is_active)
  - `WebhookDelivery`: Registro de entregas (webhook_subscription_id, event_type, payload, status, attempt_count)

- **Servicio**: `WebhookService` (`/home/eleacid/code/laravel/geobase/app/Services/WebhookService.php`)
  - Encola entregas en `WebhookDeliveryJob`
  - Manejo de reintentos y firma HMAC

---

### Middleware y Autenticación
- **Middleware global API**: `auth:sanctum` + `throttle:geobase-read` + `TrackSystemTokenUsage`
- **Abilities (permisos granulares)**: `abilities:padron:*` (read, register, enroll, update-identity, validate, export, export-shcp, snapshot, provision)
- **Team context**: Omitido intencionalmente en API v1/geobase (M2M cross-team)
- **Throttle rates**: `geobase-read`, `geobase-write`, `public-api`
