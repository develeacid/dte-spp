# Manual de Usuario — Rol Administrador (dte-spp)

> Sistema: **dte-spp** (PbR-SED · planeación, MIR, seguimiento, evaluación, transparencia).
> Rol técnico: `admin`. Este manual describe lo que puede hacer un administrador, paso a paso, con rutas y componentes reales.

---

## 1. Quién es este rol y qué permisos tiene

El **Administrador** es el rol con mayor cobertura funcional del sistema. Por diseño recibe **todos los permisos del catálogo `SystemPermission` salvo uno** (`aprobar_datos_abiertos`, segregado al rol RDA — ver sección 4).

Su foco operativo principal es la **gestión de usuarios/roles/equipos, catálogos y configuración del sistema**, pero técnicamente también puede operar la mayoría de los módulos (planeación, seguimiento, presupuesto, jurídico, evaluación, padrón), porque hereda esos permisos.

### Permisos reales (confirmados en los seeders)

Fuente: `RolesAndPermissionsSeeder` (asigna a admin **todos los permisos menos `aprobar_datos_abiertos`**) + seeders de dominio que vuelven a confirmar la asignación a admin (`PadronPermissionsSeeder`, `JuridicoPermissionsSeeder`, `AsmPermissionsSeeder`, `PresupuestoPermissionsSeeder`).

| Grupo | Permiso | ¿Admin lo tiene? |
|---|---|---|
| **Administración** | `administrar_usuarios` | ✅ |
| | `invitar_usuarios` | ✅ |
| **Catálogos / Planeación** | `gestionar_catalogos` | ✅ |
| | `crear_programa` | ✅ |
| | `editar_mir` | ✅ |
| **Seguimiento** | `capturar_avance` | ✅ |
| | `revisar_avance` | ✅ |
| | `aprobar_avance` | ✅ |
| | `ver_sabana_captura` | ✅ |
| | `ver_concentrado_captura` | ✅ |
| **Reportes** | `exportar_reportes` | ✅ |
| **Presupuesto** | `gestionar_presupuesto` | ✅ |
| | `capturar_avance_financiero` | ✅ |
| | `ver_datos_financieros` | ✅ |
| | `exportar_cuenta_publica` | ✅ |
| **Jurídico** | `gestionar_sustento_legal` | ✅ |
| | `validar_sustento_legal` | ✅ |
| | `ver_sustento_legal` | ✅ |
| | `gestionar_reglas_operacion` | ✅ |
| **Evaluación** | `ver_asm` | ✅ |
| | `gestionar_asm` | ✅ |
| | `ver_evaluacion_externa` | ✅ |
| | `gestionar_evaluacion_externa` | ✅ |
| **Padrón (GeoBase)** | `ver_padron` | ✅ |
| | `generar_snapshot_padron` | ✅ |
| | `exportar_padron_shcp` | ✅ |
| **IAFF / Cierre fiscal** | `firmar_iaff` | ✅ |
| | `gestionar_cierre_fiscal` | ✅ |
| **Transparencia** | `ver_datasets_abiertos` | ✅ |
| | `gestionar_dataset_abierto` | ✅ |
| | `aprobar_datos_abiertos` | ❌ **NO** (segregado al rol RDA) |

> **Nota de diseño (segregación de funciones).** El único permiso que el admin NO obtiene es `aprobar_datos_abiertos`. Es intencional: la aprobación/publicación/retiro de datasets abiertos al portal público es responsabilidad exclusiva del **Responsable de Datos Abiertos (RDA)**. Ver detalle en la sección 4.

---

## 2. A qué entra al iniciar sesión y cómo navega

### Landing / Dashboard
- Tras autenticarse (Jetstream + sesión + verificación de correo), el destino estándar es `/dashboard` (`route: dashboard`, componente `App\Livewire\Dashboard`).
- También dispone de `/notifications` (`notifications.index`) para sus notificaciones.

### Navegación (sidebar)
La barra lateral (`components/layout/sidebar-nav.blade.php`) muestra grupos según los permisos del usuario. Como el admin tiene casi todos, **ve prácticamente todos los grupos**:

| Grupo del sidebar | Visible para admin porque tiene… | Entradas |
|---|---|---|
| **Planeación** | `crear_programa` / `editar_mir` | Programas, Importaciones |
| **Seguimiento** | `revisar_avance`, `capturar_avance`, `ver_sabana_captura`, `ver_concentrado_captura` | Panel, Mis Indicadores, Vencidos, Sábana, Concentrado |
| **Presupuesto** | `ver_datos_financieros`, `gestionar_presupuesto`, … | Panel, Partidas, Importar, POA, Cuenta Pública |
| **Jurídico** | `ver_sustento_legal` | Panel Jurídico |
| **Catálogos** | `gestionar_catalogos` | Plan Estatal (PED), Prog. Derivados, Alineación |
| **Reportes** | `exportar_reportes`, `ver_asm`, `ver_evaluacion_externa` | Transversal, Desviaciones, Acumulado Anual, ASMs, Evaluaciones externas, Datos Abiertos |
| **Transparencia** | `ver_datasets_abiertos` | Datos Abiertos |
| **Administración** | `administrar_usuarios` / `invitar_usuarios` | Usuarios, Monitor IA, Auditoría |

> El grupo **Administración** es el núcleo de este rol. Las tres entradas (`admin.users`, `admin.monitoreo-ia`, `admin.auditoria`) viven bajo el prefijo `/admin`.

---

## 3. Tareas principales paso a paso

### Flujo A — Gestión de usuarios, roles y equipos

#### A.1 Invitar a un nuevo usuario
- **Objetivo:** dar de alta a una persona y asignarle rol y equipo (UR).
- **Ruta / URL:** `/admin/usuarios` (`route: admin.users`) — middleware `can:invitar_usuarios`.
- **Vista / componente:** `App\Livewire\Admin\GestionUsuarios` → `livewire/admin/gestion-usuarios.blade.php`.
- **Pasos:**
  1. Click en el botón que abre el formulario de invitación (`openInviteForm()`).
  2. Captura: **Correo** (único en `users`), **Nombre** (mín. 3 caracteres), **Rol** (solo `admin`, `planeador` u `operador`) y **Equipo** (un team no personal).
  3. Click en enviar (`sendInvitation()`).
- **Resultado:** se crea la invitación vía `InvitacionUsuarioService::invitar()` y se envía correo de activación. Flash: *"Invitación enviada correctamente."* El usuario queda en estado **Pendiente** hasta que active su cuenta.

> **Importante:** desde este formulario solo se pueden asignar los roles `admin`, `planeador` u `operador` (validación dura `required|in:admin,planeador,operador`). Los roles `analista_financiero`, `analista_juridico` y `responsable_datos_abiertos` **no se asignan aquí** — se gestionan por seeders/dominio o asignación directa fuera de este formulario.

#### A.2 Reenviar invitación
- **Objetivo:** reenviar el correo de activación a un usuario que aún no activó o cuya invitación expiró.
- **Ruta:** misma pantalla `/admin/usuarios`.
- **Acción:** botón "Reenviar" en la fila del usuario (`resendInvitation($userId)`).
- **Regla:** solo permitido si el usuario está pendiente de activación o su invitación expiró (`isPendingActivation()` / `isInvitationExpired()`), de lo contrario aborta 403.
- **Resultado:** flash *"Invitación reenviada a {correo}."*

#### A.3 Activar / desactivar un usuario
- **Objetivo:** habilitar o bloquear el acceso de un usuario existente.
- **Ruta:** `/admin/usuarios`.
- **Acción:** toggle de estado en la fila (`toggleActive($userId)`).
- **Regla dura:** no puede desactivarse a sí mismo (`abort_if($user->id === auth()->id(), 403)`).
- **Resultado:** flash *"Usuario {nombre} activado/desactivado."*

#### A.4 Buscar y filtrar usuarios
- **Ruta:** `/admin/usuarios`.
- **Filtros:** búsqueda por nombre/correo (ilike) y filtro de estado (`activo`, `pendiente`, `inactivo`).
- **KPIs en cabecera:** Total usuarios, Activos, Pendientes (ámbar), Inactivos (rojo).

#### A.5 Gestión de equipos (UR) — Jetstream
- **Objetivo:** crear/editar equipos (Teams = Unidades Responsables) e invitaciones a equipo.
- **Dónde:** funcionalidad nativa de **Jetstream Teams** (`Features::teams(['invitations' => true])` está habilitado). Se opera desde el menú de cuenta/equipo de Jetstream (creación de equipo, miembros, cambio de equipo actual), no desde un componente custom de este dominio.
- **Nota:** el formulario de invitación de A.1 lista solo equipos **no personales** (`personal_team = false`) como destino del nuevo usuario.

---

### Flujo B — Auditoría (audit trail)

#### B.1 Consultar el registro de actividad
- **Objetivo:** revisar quién creó, actualizó o eliminó entidades sensibles.
- **Ruta / URL:** `/admin/auditoria` (`route: admin.auditoria`) — middleware `can:administrar_usuarios`.
- **Vista / componente:** `App\Livewire\Admin\Auditoria` → `livewire/admin/auditoria.blade.php` (sobre `spatie/activitylog`).
- **Filtros:** Tipo de entidad (Usuario, PED Plan/Eje/Tema/Objetivo Estratégico, MIR Nivel, Indicador, Evaluación Programa), usuario causante, evento (`created`/`updated`/`deleted`), rango de fechas (por defecto últimos 30 días).
- **KPIs:** Eventos (en el rango), Creaciones (verde), Actualizaciones (ámbar), Eliminaciones (rojo).
- **Acciones:** filtrar, limpiar filtros (`limpiarFiltros()`), paginar (25 por página).
- **Resultado:** tabla cronológica (más reciente primero) con causante, evento y entidad afectada. Es solo lectura.

---

### Flujo C — Monitoreo de IA

#### C.1 Vigilar consumo y salud de la integración IA
- **Objetivo:** monitorear uso, costo y alertas de las funciones de IA del sistema (validaciones MIR, sugerencias, etc.).
- **Ruta / URL:** `/admin/monitoreo-ia` (`route: admin.monitoreo-ia`) — middleware `can:administrar_usuarios`.
- **Vista / componente:** `App\Livewire\Admin\MonitoreoIa` → `livewire/admin/monitoreo-ia.blade.php`.
- **Contenido:** métricas y KPIs, uso por tipo de prompt, por usuario y por UR, tendencia temporal, presupuestos y **alertas**. Filtro por periodo.
- **Acción operativa:** botón **"Probar conexión"** (`probarConexion()`) para validar conectividad con el proveedor de IA (gated `administrar_usuarios`).
- **Resultado:** tableros de consumo + verificación de conexión; sin captura de datos de negocio.

---

### Flujo D — Catálogos (cascada estratégica PED)

> Todas las rutas del grupo `cascade.*` están protegidas por `permission:gestionar_catalogos`, que el admin posee.

#### D.1 Gestionar el Plan Estatal de Desarrollo (PED)
- **Objetivo:** administrar la jerarquía PED (Plan → Eje → Tema → Objetivo → Estrategia → Línea de acción).
- **Ruta:** `/cascade/ped` (`cascade.ped.index`), componente `PedTree`.
- **Acciones:** crear plan (`/cascade/ped/plan/create`, `PedPlanForm`), editar/eliminar plan, crear/editar/eliminar nodos (`PedNodoForm`), e **importar PED desde Markdown** (`/cascade/ped/import`, `PedImporter`).

#### D.2 Gestionar Programas Derivados
- **Ruta:** `/cascade/programas-derivados` (`cascade.programas-derivados.index`), componente `ProgramasDerivadosManager`.
- **Acciones:** crear/editar/eliminar programas derivados y sus objetivos; filtrar por tipo (Sectoriales/Especiales/Institucionales/Regionales).

#### D.3 Gestionar la Matriz de Alineación estratégica
- **Ruta:** `/cascade/alineacion` (`cascade.alineacion.index`), componente `MatrizAlineacionManager`.
- **Tabs:** PED↔PND, PND↔ODS, Línea↔Programa Derivado. Crear/eliminar vínculos con búsqueda asistida.
- **Cadena completa:** `/cascade/alineacion/cadena/{lineaAccion}` (`CadenaAlineacion`) muestra la cascada completa de una línea de acción.

---

### Flujo E — Otras capacidades heredadas (resumen)

El admin también puede operar, gracias a sus permisos:

- **Planeación MIR:** wizard MML y `MirEditor` (`/mml/...`, `editar_mir`), creación de programas (`crear_programa`).
- **Seguimiento:** panel, sábana, concentrado, y **gestión de desbloqueos** de avances congelados (`/seguimiento/desbloqueos`, `GestionarDesbloqueos`, gated `administrar_usuarios`).
- **Presupuesto:** partidas, captura financiera, POA, Cuenta Pública.
- **Jurídico:** gestionar y **validar** sustento legal, ROP/documentos normativos.
- **Evaluación:** ASM (crear/editar), evaluaciones externas (gestionar informe, hallazgos, recomendaciones).
- **Padrón:** ver, generar snapshot, exportar Padrón SHCP.
- **Transparencia:** ver y **gestionar** datasets (crear borradores, entregas, enviar a revisión) — pero **no aprobar/publicar** (ver sección 4).

> Para el detalle paso a paso de estos módulos, consulta los manuales de los roles operativos (Planeador / Operador) y los docs de módulo `M01–M10`.

---

## 4. Qué NO puede hacer (acciones bloqueadas o no visibles)

### 4.1 Aprobar / publicar / retirar datasets abiertos — **bloqueado por diseño**
Este es el único límite estructural del rol admin.

- El admin **NO tiene** el permiso `aprobar_datos_abiertos` (excluido explícitamente en `RolesAndPermissionsSeeder` vía `$permisosSegregados`).
- En consecuencia, en `/transparencia/datos-abiertos/{dataset}` (`Transparencia\Datasets\Show`) el admin **no puede** ejecutar las transiciones reservadas al RDA:
  - **Aprobar** (revisión → aprobado)
  - **Publicar** (aprobado → publicado)
  - **Rechazar** (revisión → borrador con motivo)
  - **Retirar** (publicado → retirado con motivo)
  - **Editar plantilla** del catálogo (`/editar-plantilla`, gated `aprobar_datos_abiertos`).
- **Sí puede:** crear borradores, crear entregas (clonar plantilla por periodo) y **enviar a revisión** (con `gestionar_dataset_abierto`).

> Por una decisión de diseño previa, `Gate::before` **no** hace bypass de admin para `DatasetAbierto`: la Policy es autoritativa. Esto significa que el admin **ni siquiera ve** los botones de aprobación/publicación; no aparecen estados engañosos.

### 4.2 No puede desactivarse a sí mismo
En `/admin/usuarios`, el toggle de estado aborta con 403 si intenta desactivar su propia cuenta.

### 4.3 Asignación de roles limitada desde el formulario de invitación
El formulario de invitación solo permite `admin`, `planeador`, `operador`. No puede crear desde ahí usuarios `analista_financiero`, `analista_juridico` ni `responsable_datos_abiertos`.

### 4.4 Scoping por equipo (no-admin)
No aplica como bloqueo al admin (el admin no está limitado al `currentTeam` en la mayoría de paneles), pero conviene saber que los demás roles sí lo están; el admin ve el universo de programas.

---

## 5. Errores y validaciones comunes para este rol

### Gestión de usuarios (`/admin/usuarios`)
- **Correo duplicado:** `required|email|unique:users,email` → no se puede invitar dos veces el mismo correo.
- **Nombre corto:** `required|string|min:3`.
- **Rol no permitido:** `required|in:admin,planeador,operador` → cualquier otro valor es rechazado.
- **Equipo inexistente:** `required|exists:teams,id`.
- **Reenviar a usuario ya activo:** 403 (solo pendientes/expirados).
- **Auto-desactivación:** 403.

### Auditoría (`/admin/auditoria`) y Monitor IA (`/admin/monitoreo-ia`)
- Acceso gated por `can:administrar_usuarios`. Si un usuario sin ese permiso intenta entrar, recibe 403 (defensa en profundidad: middleware de ruta + `abort_unless` en `mount()`).

### Transparencia
- Intentar una transición de aprobación sin el permiso RDA termina en **403 / DomainException** (sin daño): el admin no debe intentar aprobar/publicar; esa es responsabilidad del RDA.

### Reglas duras heredadas relevantes (si el admin opera otros módulos)
- **MIR — cambiar una meta** (`MirEditor`, `indicadores.meta`): exige justificación → genera audit trail en `revisiones_meta`. Import/snapshot exentos.
- **MIR — semáforo de rangos** (B3–B6): la meta debe caer en rango verde, sin solapamientos, PCT en [0,100], `rojo_min ≠ 0`.
- **MIR — Medio de Verificación** (B7/B9): el MV debe publicarse al menos tan frecuentemente como se mide el indicador; FIN/PROPÓSITO exigen `tipo_fuente = externa`.
- **Seguimiento — análisis de desviación:** capturar amarillo/rojo/rojo_alto exige `analisis_desviacion` (dato/causa/acción/proyección).
- **Presupuesto:** avances respetan orden `comprometido ≥ devengado ≥ pagado`; metas no exceden el monto efectivo.

---

### Referencias
- Permisos: `app/Enums/SystemPermission.php`, `app/Enums/SystemRole.php`
- Seeders fuente de verdad: `database/seeders/RolesAndPermissionsSeeder.php`, `PadronPermissionsSeeder.php`, `JuridicoPermissionsSeeder.php`, `AsmPermissionsSeeder.php`, `PresupuestoPermissionsSeeder.php`, `TransparenciaPermissionsSeeder.php`
- Rutas admin: `routes/web/admin.php` · Sidebar: `resources/views/components/layout/sidebar-nav.blade.php`
- Componentes: `app/Livewire/Admin/{GestionUsuarios,Auditoria,MonitoreoIa}.php`
- Módulos: `docs/sistema/modulos/M01..M10-*.md`
