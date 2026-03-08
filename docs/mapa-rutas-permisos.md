# Mapa de Rutas y Permisos — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## Leyenda

| Símbolo | Significado |
|---------|-------------|
| ✓ | Acceso permitido |
| — | Sin acceso |
| (auth) | Requiere autenticación pero sin permiso específico |

---

## 1. Rutas Públicas (sin autenticación)

| Método | URI | Controlador | Nombre |
|--------|-----|-------------|--------|
| GET | `/` | Closure → welcome | — |
| GET | `/branding` | Closure → branding.index | branding.index |
| GET | `/activar/{token}` | OnboardingController@showSetPassword | activar.show |
| POST | `/activar/{token}/password` | OnboardingController@storePassword | activar.password |
| GET | `/activar/{token}/2fa` | OnboardingController@showSetup2fa | activar.2fa |
| POST | `/activar/{token}/2fa` | OnboardingController@confirm2fa | activar.2fa.confirm |

---

## 2. Rutas Autenticadas — General

**Middleware:** `auth:sanctum`, `jetstream.auth_session`, `verified`

| Método | URI | Componente/Controller | Nombre | Admin | Planeador | Operador |
|--------|-----|----------------------|--------|:-----:|:---------:|:--------:|
| GET | `/dashboard` | Livewire\Dashboard | dashboard | ✓ | ✓ | ✓ |
| GET | `/notifications` | Livewire\NotificationsIndex | notifications.index | ✓ | ✓ | ✓ |

---

## 3. Rutas Admin

### 3.1 Administración (`can:administrar_usuarios`)

| Método | URI | Componente | Nombre | Admin | Planeador | Operador |
|--------|-----|-----------|--------|:-----:|:---------:|:--------:|
| GET | `/admin/monitoreo-ia` | Admin\MonitoreoIa | admin.monitoreo-ia | ✓ | — | — |
| GET | `/admin/auditoria` | Admin\Auditoria | admin.auditoria | ✓ | — | — |

### 3.2 Gestión de Usuarios (`can:invitar_usuarios`)

| Método | URI | Componente | Nombre | Admin | Planeador | Operador |
|--------|-----|-----------|--------|:-----:|:---------:|:--------:|
| GET | `/admin/usuarios` | Admin\GestionUsuarios | admin.users | ✓ | — | — |

---

## 4. Rutas Cascada

**Middleware adicional:** `permission:gestionar_catalogos`

### 4.1 PED (Plan Estatal de Desarrollo)

| Método | URI | Controller@action | Nombre | Admin | Planeador | Operador |
|--------|-----|-------------------|--------|:-----:|:---------:|:--------:|
| GET | `/cascade/ped` | PedController@index | cascade.ped.index | ✓ | ✓ | — |
| GET | `/cascade/ped/plan/create` | PedController@createPlan | cascade.ped.plan.create | ✓ | ✓ | — |
| POST | `/cascade/ped/plan` | PedController@storePlan | cascade.ped.plan.store | ✓ | ✓ | — |
| GET | `/cascade/ped/plan/{plan}/edit` | PedController@editPlan | cascade.ped.plan.edit | ✓ | ✓ | — |
| PUT | `/cascade/ped/plan/{plan}` | PedController@updatePlan | cascade.ped.plan.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/plan/{plan}` | PedController@destroyPlan | cascade.ped.plan.destroy | ✓ | ✓ | — |
| POST | `/cascade/ped/eje` | PedController@storeEje | cascade.ped.eje.store | ✓ | ✓ | — |
| PUT | `/cascade/ped/eje/{eje}` | PedController@updateEje | cascade.ped.eje.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/eje/{eje}` | PedController@destroyEje | cascade.ped.eje.destroy | ✓ | ✓ | — |
| POST | `/cascade/ped/tema` | PedController@storeTema | cascade.ped.tema.store | ✓ | ✓ | — |
| PUT | `/cascade/ped/tema/{tema}` | PedController@updateTema | cascade.ped.tema.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/tema/{tema}` | PedController@destroyTema | cascade.ped.tema.destroy | ✓ | ✓ | — |
| POST | `/cascade/ped/objetivo` | PedController@storeObjetivo | cascade.ped.objetivo.store | ✓ | ✓ | — |
| PUT | `/cascade/ped/objetivo/{obj}` | PedController@updateObjetivo | cascade.ped.objetivo.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/objetivo/{obj}` | PedController@destroyObjetivo | cascade.ped.objetivo.destroy | ✓ | ✓ | — |
| POST | `/cascade/ped/estrategia` | PedController@storeEstrategia | cascade.ped.estrategia.store | ✓ | ✓ | — |
| PUT | `/cascade/ped/estrategia/{e}` | PedController@updateEstrategia | cascade.ped.estrategia.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/estrategia/{e}` | PedController@destroyEstrategia | cascade.ped.estrategia.destroy | ✓ | ✓ | — |
| POST | `/cascade/ped/linea` | PedController@storeLinea | cascade.ped.linea.store | ✓ | ✓ | — |
| PUT | `/cascade/ped/linea/{linea}` | PedController@updateLinea | cascade.ped.linea.update | ✓ | ✓ | — |
| DELETE | `/cascade/ped/linea/{linea}` | PedController@destroyLinea | cascade.ped.linea.destroy | ✓ | ✓ | — |
| GET | `/cascade/ped/nodo/create` | PedController@createNodo | cascade.ped.nodo.create | ✓ | ✓ | — |
| GET | `/cascade/ped/nodo/{tipo}/{id}/edit` | PedController@editNodo | cascade.ped.nodo.edit | ✓ | ✓ | — |
| GET | `/cascade/ped/import` | PedController@importForm | cascade.ped.import | ✓ | ✓ | — |
| POST | `/cascade/ped/import` | PedController@import | cascade.ped.import.store | ✓ | ✓ | — |

### 4.2 Matriz de Alineación

| Método | URI | Controller@action | Nombre | Admin | Planeador | Operador |
|--------|-----|-------------------|--------|:-----:|:---------:|:--------:|
| GET | `/cascade/alineacion` | MatrizAlineacionController@index | cascade.alineacion.index | ✓ | ✓ | — |
| POST | `/cascade/alineacion/ped-pnd` | @storePedPnd | cascade.alineacion.ped-pnd.store | ✓ | ✓ | — |
| DELETE | `/cascade/alineacion/ped-pnd/{p}/{n}` | @destroyPedPnd | cascade.alineacion.ped-pnd.destroy | ✓ | ✓ | — |
| POST | `/cascade/alineacion/pnd-ods` | @storePndOds | cascade.alineacion.pnd-ods.store | ✓ | ✓ | — |
| DELETE | `/cascade/alineacion/pnd-ods/{p}/{o}` | @destroyPndOds | cascade.alineacion.pnd-ods.destroy | ✓ | ✓ | — |
| POST | `/cascade/alineacion/linea-programa` | @storeLineaPrograma | cascade.alineacion.linea-programa.store | ✓ | ✓ | — |
| DELETE | `/cascade/alineacion/linea-programa/{l}/{p}` | @destroyLineaPrograma | cascade.alineacion.linea-programa.destroy | ✓ | ✓ | — |
| GET | `/cascade/alineacion/search/*` | @search* | cascade.alineacion.search.* | ✓ | ✓ | — |
| GET | `/cascade/alineacion/cadena/{linea}` | @showCadena | cascade.alineacion.cadena.show | ✓ | ✓ | — |

### 4.3 Programas Derivados

| Método | URI | Controller@action | Nombre | Admin | Planeador | Operador |
|--------|-----|-------------------|--------|:-----:|:---------:|:--------:|
| GET | `/cascade/programas-derivados` | ProgramaDerivadoController@index | cascade.programas-derivados.index | ✓ | ✓ | — |
| POST | `/cascade/programas-derivados` | @store | cascade.programas-derivados.store | ✓ | ✓ | — |
| PUT | `/cascade/programas-derivados/{p}` | @update | cascade.programas-derivados.update | ✓ | ✓ | — |
| DELETE | `/cascade/programas-derivados/{p}` | @destroy | cascade.programas-derivados.destroy | ✓ | ✓ | — |
| POST | `/cascade/programas-derivados/{p}/objetivos` | @storeObjetivo | cascade.programas-derivados.objetivos.store | ✓ | ✓ | — |
| PUT | `/cascade/programas-derivados/{p}/objetivos/{o}` | @updateObjetivo | cascade.programas-derivados.objetivos.update | ✓ | ✓ | — |
| DELETE | `/cascade/programas-derivados/{p}/objetivos/{o}` | @destroyObjetivo | cascade.programas-derivados.objetivos.destroy | ✓ | ✓ | — |

---

## 5. Rutas MML

**Middleware:** `auth:sanctum`, `jetstream.auth_session`, `verified` (sin permiso de middleware, verificación interna)

| Método | URI | Componente | Nombre | Admin | Planeador | Operador |
|--------|-----|-----------|--------|:-----:|:---------:|:--------:|
| GET | `/mml/programas` | Mml\ListaProgramas | mml.programas | ✓ | ✓ | ✓ |
| GET | `/mml/importar` | Mml\DashboardImportaciones | mml.importaciones | ✓ | ✓ | ✓ |
| GET | `/mml/importar/nuevo` | Mml\ImportarPrograma | mml.importar.nuevo | ✓ | ✓ | ✓ |
| GET | `/mml/importar/{imp}/completar` | Mml\CompletarHuecos | mml.importar.completar | ✓ | ✓ | ✓ |
| GET | `/mml/importar/{imp}/vincular` | Mml\VincularAlineacion | mml.importar.vincular | ✓ | ✓ | ✓ |
| GET | `/mml/importar/{imp}/calendarizar` | Mml\CalendarizarMetas | mml.importar.calendarizar | ✓ | ✓ | ✓ |
| GET | `/mml/{programa}/etapa/1` | Mml\DefinicionProblema | mml.etapa1 | ✓ | ✓ | ✓ |
| GET | `/mml/{programa}/etapa/2` | Mml\ArbolProblemaBuilder | mml.etapa2 | ✓ | ✓ | ✓ |
| GET | `/mml/{programa}/etapa/3` | Mml\ArbolObjetivosBuilder | mml.etapa3 | ✓ | ✓ | ✓ |
| GET | `/mml/{programa}/etapa/4` | Mml\SeleccionAlternativas | mml.etapa4 | ✓ | ✓ | ✓ |
| GET | `/mml/{programa}/etapa/5/mir` | Mml\MirEditor | mml.mir | ✓ | ✓ | ✓ |

---

## 6. Rutas Seguimiento

**Middleware:** `auth:sanctum`, `jetstream.auth_session`, `verified`

| Método | URI | Componente/Controller | Nombre | Admin | Planeador | Operador |
|--------|-----|----------------------|--------|:-----:|:---------:|:--------:|
| GET | `/seguimiento` | Tracking\PanelSeguimiento | tracking.panel | ✓ | ✓ | ✓ |
| GET | `/seguimiento/pendientes` | Tracking\MisIndicadoresPendientes | tracking.pendientes | ✓ | ✓ | ✓ |
| GET | `/seguimiento/vencidos` | Tracking\IndicadoresVencidos | tracking.vencidos | ✓ | ✓ | ✓ |
| GET | `/seguimiento/captura/{avance}` | Tracking\CapturaAvance | tracking.captura | ✓ | ✓ | ✓ |
| GET | `/seguimiento/avance/{avance}/evidencias` | Tracking\EvidenciaAvance | tracking.evidencia.index | ✓ | ✓ | ✓ |
| GET | `/seguimiento/evidencia/{evidencia}/download` | EvidenciaController@download | tracking.evidencia.download | ✓ | ✓ | ✓ |
| GET | `/seguimiento/flujo/{avance}` | Tracking\FlujosAvance | tracking.flujo | ✓ | ✓ | ✓ |
| GET | `/seguimiento/desbloqueo/{avance}` | Tracking\SolicitarDesbloqueo | tracking.desbloqueo.solicitar | ✓ | ✓ | ✓ |
| GET | `/seguimiento/desbloqueos` | Tracking\GestionarDesbloqueos | tracking.desbloqueos | ✓ | ✓ | ✓ |

---

## 7. Rutas Evaluación

**Middleware:** `auth:sanctum`, `jetstream.auth_session`, `verified`

| Método | URI | Componente/Controller | Nombre | Permiso | Admin | Planeador | Operador |
|--------|-----|----------------------|--------|---------|:-----:|:---------:|:--------:|
| GET | `/evaluacion/programa/{eval}` | Evaluation\EvaluacionProgramaView | evaluation.programa | (auth) | ✓ | ✓ | ✓ |
| GET | `/evaluacion/transversal` | Evaluation\PanelTransversal | evaluation.transversal | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/exportar/pdf/{tipo}/{id?}` | ExportController@pdf | evaluation.exportar.pdf | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/exportar/excel/{tipo}/{id?}` | ExportController@excel | evaluation.exportar.excel | exportar_reportes | ✓ | ✓ | ✓ |
| POST | `/evaluacion/exportar/async/{fmt}/{tipo}` | ExportController@async | evaluation.exportar.async | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/exportar/descargar/{file}` | ExportController@descargar | evaluation.exportar.descargar | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/datos-abiertos/csv/{ej}` | DatosAbiertosController@csv | evaluation.datos-abiertos.csv | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/datos-abiertos/json/{ej}` | DatosAbiertosController@json | evaluation.datos-abiertos.json | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/datos-abiertos/diccionario` | DatosAbiertosController@diccionario | evaluation.datos-abiertos.diccionario | exportar_reportes | ✓ | ✓ | ✓ |
| GET | `/evaluacion/datos-abiertos/zip/{ej}` | DatosAbiertosController@zip | evaluation.datos-abiertos.zip | exportar_reportes | ✓ | ✓ | ✓ |

---

## 8. Middleware Custom

| Middleware | Clase | Aplicación | Descripción |
|-----------|-------|-----------|-------------|
| SecurityHeaders | `App\Http\Middleware\SecurityHeaders` | Global | CSP, X-Frame-Options, etc. |
| EnsureUserIsActivated | `App\Http\Middleware\EnsureUserIsActivated` | Auth routes | Verifica activación de cuenta |
| RequireTwoFactorAuthentication | `App\Http\Middleware\RequireTwoFactorAuthentication` | Auth routes | Obliga configurar 2FA |
| AislamientoMultiUR | `App\Http\Middleware\AislamientoMultiUR` | Selectivo | Filtra datos por UR/Team |

---

## 9. Resumen de Permisos por Rol

| Permiso | Descripción | Admin | Planeador | Operador |
|---------|-------------|:-----:|:---------:|:--------:|
| gestionar_catalogos | CRUD PED, alineación, programas derivados | ✓ | ✓ | — |
| crear_programa | Crear programas presupuestarios | ✓ | ✓ | — |
| editar_mir | Editar MIR y sus componentes | ✓ | ✓ | — |
| capturar_avance | Capturar avances de indicadores | ✓ | — | ✓ |
| revisar_avance | Revisar avances capturados | ✓ | ✓ | — |
| aprobar_avance | Aprobar avances revisados | ✓ | ✓ | — |
| exportar_reportes | Generar PDF, Excel, datos abiertos | ✓ | ✓ | ✓ |
| administrar_usuarios | Monitoreo IA, auditoría | ✓ | — | — |
| invitar_usuarios | Gestión de cuentas de usuario | ✓ | — | — |
