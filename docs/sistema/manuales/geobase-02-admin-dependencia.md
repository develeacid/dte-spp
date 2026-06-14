# Manual de usuario — Admin de dependencia (GeoBase)

> Sistema: **GeoBase** (padrón de beneficiarios + reportes territoriales).
> Rol del seeder: `admin_dependencia` (Nivel 2 — confinado a equipo/dependencia).
> Este manual cubre **solo GeoBase**. La planeación MIR, seguimiento, evaluación y transparencia viven en el sistema hermano dte-spp (otro manual).

---

## 1. Quién es este rol y qué permisos tiene

El **Admin de dependencia** es la persona que administra el padrón y la operación territorial **dentro de su propia dependencia (Unidad Responsable / team)**. No es un super-administrador: todo lo que ve y modifica está limitado a su equipo activo (`currentTeam`). Es el rol de mayor poder *dentro* de una dependencia, pero está confinado a ella.

Permisos reales asignados a `admin_dependencia` (fuente: `geobase/database/seeders/RolesAndPermissionsSeeder.php`):

| Permiso | Qué habilita |
|---|---|
| `beneficiary.view` | Ver listado y detalle de beneficiarios de su dependencia |
| `beneficiary.create` | Registrar nuevos beneficiarios |
| `beneficiary.update` | Editar datos y reubicar beneficiarios |
| `enrollment.view` | Ver inscripciones (entregas) |
| `enrollment.create` | Crear inscripciones |
| `enrollment.update` | Editar inscripciones no terminales y resolver excepciones |
| `enrollment.approve` | Aprobar, finalizar y cancelar inscripciones |
| `enrollment.reject` | Rechazar inscripciones |
| `program.view` | Ver programas |
| `program.manage` | Gestionar programas a nivel local |
| `report.view_team` | Ver reportes **de su equipo** (no de todos) |
| `report.export` | Exportar datos de reportes |
| `layer.request` | Solicitar capas geoespaciales (no crearlas/administrarlas) |
| `snapshot.generate` | Generar snapshots del padrón |
| `snapshot.verify` | Verificar snapshots |
| `user.invite` | Invitar usuarios a su dependencia |
| `user.manage` | Gestionar usuarios/miembros de su dependencia |

**No tiene** (reservado a `sysadmin` global): `beneficiary.delete`, `report.view_all`, `layer.view_all`, `layer.manage`, `program`/usuarios de **otras** dependencias.

> Nota importante: `beneficiary.delete` **no** está en tu rol. La eliminación de beneficiarios es exclusiva de `sysadmin`.

---

## 2. Qué ves al iniciar sesión y cómo navegas

### Acceso
- GeoBase corre en su propio host (puerto local `8081`; en producción, dominio propio).
- El login exige **2FA** (autenticación de dos factores): si no la tienes activada, el sistema te obliga a configurarla antes de entrar (middleware `require.2fa`). Configúrala desde el menú de perfil de Jetstream.

### Landing: Panel de control
Al entrar caes en el **Dashboard** (`/dashboard`, vista `dashboard.blade.php`, título "Panel de control"). Contiene widgets Livewire:
- **KPIs** del padrón (`dashboard.stats-widget`).
- **Actividad reciente** y **Alertas** (`dashboard.recent-activity`, `dashboard.alerts-widget`).
- **Vista previa de mapa**, **cobertura** e **inversión** (`dashboard.reportes-map-preview`, `reportes-cobertura`, `reportes-inversion`).
- **Resumen geográfico** (`dashboard.geographic-summary`).

Todos los datos están acotados a tu **equipo activo** (la dependencia que tengas seleccionada arriba a la derecha).

### Sidebar (componente `layout/sidebar-nav.blade.php`)
Los grupos se muestran según tus permisos:

| Grupo | Entradas | Visible para ti porque... |
|---|---|---|
| **Dashboard** | Panel de control | siempre |
| **Padrón** | Beneficiarios (`beneficiaries.index`), Inscripciones (`enrollments.index`) | tienes `beneficiary.view` / `enrollment.view` |
| **Territorio** | Análisis espacial (`analisis-espacial`) | requiere `report.view_all` o `layer.view_all` → **NO te aparece** (solo tienes `report.view_team`) |
| **Reportes** | Reportes territoriales (`reportes.index`) | requiere `report.view_all` o `report.export` → te aparece por `report.export` |
| **Integración** | Sincronización (`sync.index`), API y webhooks (`api-tokens.index`) | grupo siempre visible |
| **Administración** | Equipo y usuarios (`teams.show`) | tienes rol `admin_dependencia` |

> Cambio de dependencia: si perteneces a varias, usa el selector de equipo de Jetstream (arriba a la derecha). Al cambiar, todo el contexto (`SetTeamContext`) y tus permisos team-scoped se recalculan.

---

## 3. Tareas principales paso a paso

### Flujo A — Gestión de beneficiarios (el padrón)

#### A.1 Listar y buscar beneficiarios
- **Objetivo:** encontrar un beneficiario de tu dependencia.
- **Ruta:** `/beneficiaries` (`beneficiaries.index`) — Livewire `BeneficiaryList`.
- **Acciones:** Buscar (por hash de CURP exacto, o municipio/email con coincidencia parcial), Filtrar por tipo (persona física/moral), estado, municipio, activo (sí/no), Paginar (15 por página).
- **Resultado:** lista de beneficiarios **de tu equipo únicamente**.

#### A.2 Registrar un beneficiario nuevo
- **Objetivo:** dar de alta una persona/empresa en el padrón.
- **Ruta:** `/beneficiaries/create` (`beneficiaries.create`) — vista `beneficiaries/create.blade.php` + Livewire `BeneficiaryForm`.
- **Pasos:** Captura datos (nombre/razón social, CURP/RFC, fecha de nacimiento, contacto), selecciona municipio, captura ubicación → **Guardar**.
- **Validaciones en juego:** P-01 (desagregación obligatoria Anexo 11 PEF según tipo), P-02 (detección automática de duplicados por CURP), P-05 (coherencia de campos: lat/lng vs municipio, fecha de nacimiento < hoy).
- **Resultado:** beneficiario creado, con PII cifrada. Si P-02 detecta un duplicado, te avisa y lo marca para revisión.

#### A.3 Ver / editar un beneficiario
- **Ruta detalle:** `/beneficiaries/{id}` (`beneficiaries.show`).
- **Ruta editar:** `/beneficiaries/{id}/edit` (`beneficiaries.edit`) — Livewire `BeneficiaryForm`.
- **Acciones desde el detalle:** Editar (la **CURP no es editable**), ver inscripciones del beneficiario, ver historial de cambios de datos, revisar duplicados.
- **Resultado:** datos actualizados con validación de coherencia. Toda lectura/escritura de PII queda auditada (`audit.pii` + `PiiAuditService`).

#### A.4 Reubicar un beneficiario
- **Objetivo:** corregir la ubicación geográfica (re-evaluar elegibilidad).
- **Ruta:** `/beneficiaries/{id}/relocate` (`beneficiaries.relocate`) — Livewire `RelocateForm`.
- **Pasos:** captura nueva latitud/longitud → **modal de confirmación** → Guardar.
- **Resultado:** ubicación actualizada; puede disparar la observación `OBSERVADO_DOMICILIO` en inscripciones afectadas (se resuelven en el flujo B.5).

> **No puedes eliminar beneficiarios.** El botón/acción de borrado pertenece a `sysadmin` (`beneficiary.delete`).

---

### Flujo B — Inscripciones / entregas (enrollments)

El ciclo de vida es: `SOLICITADO`/`EN_REVISION` → `APROBADO` → `FINALIZADO`, con ramas `RECHAZADO` y `CANCELADO`. Estados **terminales**: `FINALIZADO`, `RECHAZADO`, `CANCELADO`.

#### B.1 Listar inscripciones
- **Ruta:** `/enrollments` (`enrollments.index`) — Livewire `EnrollmentList`.
- **Acciones:** Buscar, Filtrar por programa/componente/estado, Paginar. Ves monto y folio de evidencia.

#### B.2 Crear una inscripción
- **Ruta:** `/enrollments/create` (`enrollments.create`) — Livewire `EnrollmentForm`.
  - Atajo desde el detalle de un beneficiario: `/enrollments/create/{beneficiary}` (`enrollments.createForBeneficiary`).
- **Pasos:** busca el beneficiario, selecciona programa y componente, el sistema valida la ubicación **en vivo** → Guardar.
- **Validaciones:** P-06 (la ubicación debe caer dentro del área del programa), P-01 (desagregación PEF).
- **Resultado:** inscripción creada en estado inicial.

#### B.3 Aprobar / rechazar
- **Ruta:** detalle `/enrollments/{id}` (`enrollments.show`); las acciones son formularios embebidos.
- **Aprobar:** `POST /enrollments/{id}/approve` — transición desde SOLICITADO/EN_REVISION → APROBADO. `reason` opcional.
- **Rechazar:** `POST /enrollments/{id}/reject` — `reason` **requerido**.

#### B.4 Finalizar (cerrar la entrega) / cancelar
- **Finalizar:** `POST /enrollments/{id}/finalize` — APROBADO → FINALIZADO. Si no hay evidencia previa, el formulario **exige `folio_evidencia`** (TRF/acta) — validación **P-07** (prueba auditable C-096).
- **Cancelar:** `POST /enrollments/{id}/cancel` — APROBADO → CANCELADO, `reason` **requerido**.

#### B.5 Resolver excepciones por domicilio
- **Objetivo:** desatorar inscripciones marcadas `OBSERVADO_DOMICILIO` (típicamente tras una reubicación).
- **Acción:** `POST /enrollments/{id}/resolve` con una de tres acciones: **aprobar**, **finalizar** o **cancelar** + `reason`.
- **Permiso:** `enrollment.update`.

#### B.6 Editar una inscripción
- **Ruta:** `/enrollments/{id}/edit` (`enrollments.edit`) — Livewire `EnrollmentForm`.
- **Editable solo si NO es terminal:** monto, observaciones, componente.
- **Validación:** P-08 (el monto entregado no puede exceder el `monto_unitario` del componente).

---

### Flujo C — Reportes territoriales (de tu equipo)

- **Objetivo:** consultar cobertura, inversión, equidad de género, densidad étnica, evolución temporal de **tu dependencia**.
- **Ruta:** `/reportes` (redirige a `/reportes/heatmap`) → `/reportes/{slug}` (`reportes.show`). Slugs: `heatmap`, `cobertura`, `inversion`, `componente`, `equidad-genero`, `densidad-etnica`, `evolucion-temporal`.
- **Componente:** Livewire `Reportes\ReportePage`. Los datos se cargan vía `/reportes/data/{tipo}` (sesión web).
- **Exportar:** botón de exportación (permiso `report.export`).
- **Alcance:** ves reportes **de tu equipo** (`report.view_team`). No el agregado estatal completo.

> El grupo **Territorio → Análisis espacial** y los reportes "de todos los equipos" **no te aparecen**: requieren `report.view_all` / `layer.view_all`, que son de `analista_global`/`sysadmin`.

---

### Flujo D — Snapshots del padrón

- **Objetivo:** congelar una foto del padrón (para seguimiento/transparencia/sincronización con dte-spp).
- **Permisos:** `snapshot.generate` (generar), `snapshot.verify` (verificar).
- Los snapshots se consumen también desde dte-spp (tab Padrón del programa). En GeoBase, la generación/verificación se dispara desde la operación del padrón y desde la **Integración → Sincronización** (`sync.index`).

---

### Flujo E — Administración de tu dependencia (usuarios y equipo)

- **Objetivo:** invitar y gestionar a los miembros de tu UR (operadores, etc.).
- **Ruta:** **Administración → Equipo y usuarios** → `teams.show` (`/teams/{team}`, página de Jetstream Teams con invitaciones habilitadas).
- **Permisos:** `user.invite`, `user.manage`.
- **Pasos típicos:**
  1. Invitar usuario: capturas correo y rol del equipo → se envía invitación.
  2. Gestionar miembros: cambiar rol de un miembro, removerlo.
- **Resultado:** el usuario invitado, al aceptar, queda confinado a tu dependencia con el rol que le asignaste.
- **Límite:** solo administras **tu** equipo (el `currentTeam`). No ves ni tocas usuarios de otras dependencias.

---

### Flujo F — Integración (consulta)

- **Sincronización:** `sync.index` — estado del pipeline de sincronización entre GeoBase y dte-spp.
- **API y webhooks:** `api-tokens.index` — tokens y webhooks. Estos endpoints máquina-a-máquina (M2M) usan **abilities Sanctum** (`padron:read`, `padron:enroll`, etc.), no tus permisos web. Como Admin de dependencia consultas este panel; la provisión de tokens M2M globales es operación de plataforma (`sysadmin`).

---

## 4. Qué NO puedes hacer (acciones bloqueadas o invisibles)

| Acción | Por qué no |
|---|---|
| **Eliminar beneficiarios** | Te falta `beneficiary.delete` (solo `sysadmin`). |
| **Ver "Análisis espacial" (grupo Territorio)** | El grupo se gatea con `report.view_all`/`layer.view_all`; tú solo tienes `report.view_team`. No aparece en el sidebar. |
| **Ver reportes de TODAS las dependencias** | `report.view_team` te limita a tu equipo. El agregado estatal es de `analista_global`/`sysadmin`. |
| **Crear/administrar capas geoespaciales** | Solo tienes `layer.request` (solicitar). `layer.manage`/`layer.view_all` no son tuyos. |
| **Administrar usuarios de otra dependencia** | `user.manage` está acotado a tu `currentTeam`. |
| **Operar sobre datos de otro equipo** | El middleware `team.context` + `SetTeamContext` fuerza el alcance de tu equipo activo en todo el sistema. |
| **Editar la CURP de un beneficiario** | La CURP es inmutable en edición (re-alta vía duplicados P-02 si fuera necesario). |
| **Editar/aprobar inscripciones terminales** | FINALIZADO/RECHAZADO/CANCELADO no admiten edición ni nuevas transiciones. |

---

## 5. Errores y validaciones comunes que verás

Reglas duras del padrón (P-01 … P-08) que te bloquearán o pedirán datos:

| Código | Cuándo salta | Qué tienes que hacer |
|---|---|---|
| **P-01** | Al crear/editar beneficiario sin la desagregación obligatoria (Anexo 11 PEF) según el tipo | Completa todos los campos demográficos requeridos. |
| **P-02** | Al registrar un beneficiario con CURP ya existente | El sistema marca el registro como **posible duplicado** y lo manda a revisión; revisa el duplicado antes de continuar. |
| **P-05** | Datos incoherentes en el mismo registro (lat/lng que no corresponden al municipio, fecha de nacimiento futura) | Corrige la ubicación o la fecha. |
| **P-06** | Al crear inscripción cuya ubicación cae **fuera del área del programa** | Verifica/reubica al beneficiario o corrige el programa/componente. La validación es en vivo en el formulario. |
| **P-07** | Al **finalizar** una inscripción sin evidencia previa | Captura el `folio_evidencia` (TRF/acta) en el formulario de finalizar; sin él no cierra. |
| **P-08** | Al editar/crear inscripción con `monto_entregado` > `monto_unitario` del componente | Reduce el monto al máximo del componente. |
| **P-03 / P-04** | (Latentes) Validaciones ROP e interinstitucionales | No bloquean hoy; dependen de integración cross-sistema pendiente (C-098). No requieren acción de tu parte. |

Otros mensajes/comportamientos esperables:
- **Rechazar / Cancelar** sin `reason` → el formulario no envía: el motivo es obligatorio.
- **2FA no configurado** → el sistema te redirige a habilitarla; no podrás operar hasta confirmarla.
- **Acción sobre otro equipo** → el contexto de equipo te devolverá tus propios datos, no los de la otra dependencia (no es un error: es el alcance por diseño).
- Toda lectura/escritura de PII (CURP, ubicación, datos personales) queda **auditada**; opera con datos reales solo cuando corresponda.

---

### Referencias (fuentes verificadas)
- Permisos del rol: `geobase/database/seeders/RolesAndPermissionsSeeder.php` (rol `admin_dependencia`).
- Scoping global/by-pass: `geobase/app/Providers/AppServiceProvider.php` (`Gate::before`, solo `sysadmin`/`analista_global`).
- Sidebar y gating de menús: `geobase/resources/views/components/layout/sidebar-nav.blade.php`.
- Rutas/vistas/validaciones P-01..P-08: `docs/sistema/_mapa-sistema.md` (áreas geo-padron, geo-territorio).
