# Manual de Usuario — Rol Operador (geobase)

> Sistema: **geobase** (Laravel + PostGIS — padrón de beneficiarios y reportes territoriales).
> Rol: **`operador`** (rol confinado a equipo / Level 2).
> Este manual cubre tu trabajo diario: dar de alta beneficiarios, inscribirlos a programas y mantener sus datos. Es práctico: cada tarea trae la URL exacta, la pantalla, los botones y el resultado.

---

## 1. Quién eres y qué permisos tienes

El rol **`operador`** es un rol **confinado a tu equipo (UR/dependencia)**: solo ves y trabajas beneficiarios e inscripciones que pertenecen a programas de tu `currentTeam`. No ves el padrón completo del estado (eso lo ven `analista_global` y `sysadmin`).

Permisos reales asignados al rol `operador` (fuente: `database/seeders/RolesAndPermissionsSeeder.php`):

| Permiso | Qué te habilita |
|---------|-----------------|
| `beneficiary.view` | Ver el listado y el detalle de beneficiarios. |
| `beneficiary.create` | Dar de alta nuevos beneficiarios. |
| `beneficiary.update` | Editar datos de un beneficiario y reubicarlo (cambiar lat/lng). |
| `enrollment.view` | Ver el listado y el detalle de inscripciones. |
| `enrollment.create` | Crear nuevas inscripciones (beneficiario → programa → componente). |
| `enrollment.update` | Editar inscripciones no terminales (monto, observaciones) y **resolver** excepciones de domicilio. |
| `program.view` | Consultar los programas y componentes disponibles para inscribir. |
| `report.view_team` | Ver reportes acotados a tu equipo. |

**Permisos que NO tienes** (importante, ver sección 4): `beneficiary.delete`, `enrollment.approve`, `enrollment.reject`, `program.manage`, `report.view_all`, `report.export`, `layer.*`, `snapshot.*`, `user.*`.

Acceso a todo el sistema exige además: cuenta **activada**, correo **verificado** y **2FA habilitado** (`require.2fa`). Sin 2FA configurado no entras a ninguna pantalla operativa.

---

## 2. A qué entras al iniciar sesión y cómo navegas

Tras autenticarte (Sanctum + 2FA), aterrizas en el **Dashboard** (`/dashboard`, ruta `dashboard`, vista `dashboard.blade.php`).

La navegación es la **barra superior horizontal** (Jetstream, `navigation-menu.blade.php`). Como operador verás todos estos enlaces — pero algunas pantallas a las que llevan estarán bloqueadas o vacías de acciones para tu rol:

| Enlace | Ruta | ¿Lo usas? |
|--------|------|-----------|
| **Dashboard** | `dashboard` | Punto de entrada. |
| **Beneficiarios** | `beneficiaries.index` | **Sí — tu pantalla principal.** |
| **Inscripciones** | `enrollments.index` | **Sí — tu segunda pantalla principal.** |
| **Reportes** | `reportes.index` | Limitado: solo reportes de tu equipo (`report.view_team`); sin exportar. |
| **Sincronización** | `sync.index` | No es tu función (no tienes `snapshot.*`). |
| **Análisis Espacial** | `analisis-espacial` | No es tu función (requiere `report.view_all`/`layer.view_all`). |

> El menú **no oculta** los enlaces por permiso: si entras a una pantalla fuera de tu alcance verás la página vacía de acciones o un error 403 al intentar una operación gated. Esto es normal.

---

## 3. Tareas principales paso a paso

### Flujo A — Alta y mantenimiento de beneficiarios

#### A.1 Listar y buscar beneficiarios
- **Objetivo:** encontrar un beneficiario o confirmar que no existe antes de darlo de alta.
- **URL / ruta:** `/beneficiaries` — `beneficiaries.index`
- **Componente:** Livewire `BeneficiaryList`
- **Cómo:** usa la barra de **búsqueda** (CURP hash exacto, o municipio/email por coincidencia) y los **filtros**: tipo (persona física / moral), estado, municipio, activo. Paginación de 15.
- **Resultado:** lista filtrada. Desde aquí entras al detalle o pulsas **Crear nuevo**.

#### A.2 Dar de alta un beneficiario (P-01 / P-02 / P-05)
- **Objetivo:** registrar una persona física o moral en el padrón.
- **URL / ruta:** `/beneficiaries/create` — `beneficiaries.create`
- **Vista / componente:** `beneficiaries/create.blade.php` + Livewire `BeneficiaryForm`
- **Pasos:**
  1. Selecciona **Tipo**: `persona_fisica` o `persona_moral`.
  2. **CURP/RFC** (`curp_rfc`): obligatorio al crear (10–18 caracteres). Es la llave de identidad.
  3. **Persona física:** Nombre y Apellidos obligatorios. **Persona moral:** Razón social obligatoria.
  4. **Desagregación obligatoria (P-01, Anexo 11 PEF):** Fecha de nacimiento (anterior a hoy), Género (masculino/femenino/otro), Etnia (catálogo), checkbox **Es indígena**, checkbox **Discapacidad**. Captura estos campos de manera consistente — son los que alimentan los reportes de equidad de género y densidad étnica.
  5. **Domicilio:** Estado y Municipio son **obligatorios**; calle, número exterior/interior, colonia, CP (5), localidad, teléfono, email son opcionales. El selector de Estado/Municipio actualiza el formulario en vivo (eventos `state-changed` / `municipality-changed`).
  6. **Ubicación geográfica:** latitud (`-90..90`) y longitud (`-180..180`). Captúrala bien: de ella depende la validación geográfica de las inscripciones (P-06).
  7. Pulsa **Guardar**.
- **Resultado:** se crea el beneficiario y te redirige a su detalle (`beneficiaries.show`) con mensaje "Beneficiario creado exitosamente".
- **Duplicados (P-02):** el sistema detecta automáticamente posibles duplicados por CURP. Si los hay, el mensaje agrega "Se detectaron N posible(s) duplicado(s)" — el registro **sí se crea**, pero queda marcado para revisión. Revísalos desde el detalle.
- **Coherencia (P-05):** se valida que los campos del mismo registro sean coherentes (ej. fecha de nacimiento < hoy, coherencia ubicación vs. municipio).

#### A.3 Ver el detalle de un beneficiario
- **Objetivo:** consultar PII (cifrado), historial de cambios y sus inscripciones.
- **URL / ruta:** `/beneficiaries/{id}` — `beneficiaries.show`
- **Vista:** `beneficiaries/show.blade.php`
- **Qué ves / haces:** datos del beneficiario (PII descifrado para lectura, con auditoría `audit.pii`), **historial de datos**, **revisar duplicados**, y la lista de **inscripciones (enrollments)**. Botones: Editar, Reubicar, Ver inscripciones. (El botón **Eliminar** existe pero te dará 403, ver sección 4.)

#### A.4 Editar un beneficiario
- **Objetivo:** corregir nombre, domicilio, contacto o desagregación (sin tocar CURP).
- **URL / ruta:** `/beneficiaries/{id}/edit` — `beneficiaries.edit`
- **Vista / componente:** `beneficiaries/edit.blade.php` + `BeneficiaryForm` (modo edición)
- **Pasos:** modifica los campos editables (la CURP no se edita en este modo) y pulsa **Guardar cambios**.
- **Resultado:** "Beneficiario actualizado exitosamente", redirige al detalle. Toda escritura queda en auditoría PII.

#### A.5 Reubicar un beneficiario (cambio de domicilio)
- **Objetivo:** actualizar la ubicación (lat/lng) para re-validar elegibilidad geográfica.
- **URL / ruta:** `/beneficiaries/{id}/relocate` — `beneficiaries.relocate` (guarda vía `beneficiaries.updateLocation`, PATCH)
- **Vista / componente:** `beneficiaries/relocate.blade.php` + Livewire `RelocateForm`
- **Pasos:** ajusta latitud/longitud, confirma en el **modal de confirmación** y guarda.
- **Resultado:** la ubicación se actualiza. Si una inscripción queda fuera del área del programa, pasará a estado **OBSERVADO_DOMICILIO** y deberás resolverla (ver B.5).
- **Permiso:** usa `beneficiary.update` (lo tienes).

---

### Flujo B — Inscripciones (enrollments)

> Recuerda: como operador **creas y editas** inscripciones, y **resuelves** excepciones de domicilio. **No apruebas, no rechazas, no finalizas ni cancelas** (sección 4).

#### B.1 Listar inscripciones
- **Objetivo:** ver el estado de las inscripciones de tu equipo.
- **URL / ruta:** `/enrollments` — `enrollments.index`
- **Componente:** Livewire `EnrollmentList`
- **Cómo:** filtra por programa, componente y estado. Cada fila muestra estado, monto y folio de evidencia.

#### B.2 Crear una inscripción (P-06 validación geográfica)
- **Objetivo:** inscribir un beneficiario a un programa/componente.
- **URL / ruta:** `/enrollments/create` — `enrollments.create`
- **Vista / componente:** `enrollments/create.blade.php` + Livewire `EnrollmentForm`
- **Pasos:**
  1. **Busca el beneficiario** en vivo (campo `beneficiarySearch`) y selecciónalo.
  2. Selecciona **Programa** (solo programas activos) y **Componente**.
  3. **Validación geográfica en tiempo real (P-06):** al elegir programa, si el programa tiene `has_geo_restriction`, el formulario valida la ubicación del beneficiario contra el área del programa:
     - Verde / "ubicación dentro del área" → elegible.
     - "El beneficiario no tiene ubicación registrada" → primero captura su lat/lng (A.5).
     - "Programa sin restricción geográfica" → siempre elegible.
  4. Captura **Fecha de inscripción** (obligatoria), **Monto entregado** (≥ 0) y **Observaciones** (opcional, máx. 1000).
  5. Pulsa **Guardar**. El botón de envío se **bloquea** si el programa tiene restricción geográfica y la ubicación no es elegible (`canSubmit = false`).
- **Resultado:** se crea la inscripción en estado inicial **SOLICITADO** y te redirige a su detalle. Mensaje "Inscripción creada exitosamente".

#### B.3 Crear inscripción directamente desde un beneficiario
- **Objetivo:** atajo cuando ya estás en el detalle de un beneficiario.
- **URL / ruta:** `/enrollments/create/{beneficiary}` — `enrollments.createForBeneficiary`
- **Vista:** `enrollments/create-for-beneficiary.blade.php`
- **Resultado:** igual que B.2 pero con el beneficiario precargado.

#### B.4 Editar una inscripción (P-08 tope de monto)
- **Objetivo:** corregir monto u observaciones de una inscripción **no terminal**.
- **URL / ruta:** `/enrollments/{id}/edit` — `enrollments.edit`
- **Vista / componente:** `enrollments/edit.blade.php` + `EnrollmentForm` (modo edición)
- **Pasos:** edita **monto entregado** y/o **observaciones**, guarda.
- **Resultado:** "Inscripción actualizada exitosamente".
- **Regla P-08:** el monto entregado **no puede exceder el monto unitario del componente** (`MontoNoExcedeComponente`). Si lo excedes, la validación lo rechaza.
- **Restricción:** solo inscripciones no terminales. FINALIZADO, RECHAZADO y CANCELADO **no se editan**.

#### B.5 Resolver una inscripción observada por domicilio
- **Objetivo:** atender una inscripción que cayó en **OBSERVADO_DOMICILIO** (porque el beneficiario fue reubicado fuera del área).
- **URL / ruta:** acción `enrollments.resolve` (POST), desde el detalle `/enrollments/{id}` — `enrollments.show`
- **Vista:** formulario embebido en `enrollments/show.blade.php` con campo `action` y `reason`.
- **Cómo:** en el detalle de una inscripción OBSERVADO_DOMICILIO, elige una de las **3 acciones**: aprobar, finalizar o cancelar, con un motivo opcional.
- **Permiso:** `resolve` usa `enrollment.update` (lo tienes), por eso **esta acción sí está disponible para ti** aunque no tengas `enrollment.approve`.
- **Resultado:** la inscripción transiciona según la acción elegida y queda registrada en el historial de estados.

#### B.6 Consultar el detalle y el ciclo de vida de una inscripción
- **URL / ruta:** `/enrollments/{id}` — `enrollments.show`, vista `enrollments/show.blade.php`
- **Qué ves:** datos de la inscripción, resultado de validación geográfica, historial de transiciones de estado. Verás botones de Aprobar/Rechazar/Finalizar/Cancelar, pero al intentarlos recibirás 403 (no son de tu rol). El ciclo de estados es:

  `SOLICITADO → EN_REVISION → APROBADO → FINALIZADO`
  con ramas `RECHAZADO` (desde solicitado/revisión), `CANCELADO` (desde aprobado) y `OBSERVADO_DOMICILIO` (desde aprobado, por reubicación).
  Estados **terminales** (sin retorno): FINALIZADO, RECHAZADO, CANCELADO.

---

## 4. Qué NO puedes hacer (acciones bloqueadas)

Estas acciones requieren permisos que el rol `operador` **no tiene**. Verás el botón, pero recibirás **403** o la operación no se ejecutará:

| Acción | Pantalla / ruta | Permiso que falta | Quién sí puede |
|--------|-----------------|-------------------|----------------|
| **Eliminar beneficiario** | `beneficiaries.show` (botón Eliminar) | `beneficiary.delete` | `admin_dependencia`, `sysadmin` |
| **Eliminar inscripción** | `enrollments.destroy` | `beneficiary.delete` (la policy de delete de enrollment lo exige) | `admin_dependencia`, `sysadmin` |
| **Aprobar inscripción** | `enrollments.approve` | `enrollment.approve` | `admin_dependencia`, `sysadmin` |
| **Rechazar inscripción** | `enrollments.reject` | `enrollment.reject` | `admin_dependencia`, `sysadmin` |
| **Finalizar con folio (P-07)** | `enrollments.finalize` | `enrollment.approve` | `admin_dependencia`, `sysadmin` |
| **Cancelar inscripción** | `enrollments.cancel` | `enrollment.approve` | `admin_dependencia`, `sysadmin` |
| **Gestionar programas/componentes** | registro de programas | `program.manage` | `admin_dependencia` |
| **Exportar reportes / ver reportes globales** | `reportes.*`, análisis espacial | `report.export`, `report.view_all` | `analista_global`, `admin_dependencia` |
| **Generar/verificar snapshots, sincronización** | `sync.*` | `snapshot.generate`, `snapshot.verify` | `admin_dependencia`, `enlace_mir` |
| **Invitar/gestionar usuarios** | gestión de equipo | `user.invite`, `user.manage` | `admin_dependencia`, `sysadmin` |
| **Ver beneficiarios de otros equipos** | `beneficiaries.*` | scoping por equipo (policy) | `analista_global`, `sysadmin` |

> Nota sobre el alcance del puesto: el cierre de entregas con **folio de evidencia (P-07 / finalizar)** y las **bajas** (eliminar / cancelar) **no son tareas del operador** en geobase; las ejecuta el `admin_dependencia`. Tu papel termina al crear/editar la inscripción y, en su caso, **resolver** las observaciones de domicilio (B.5). Si necesitas finalizar o cancelar, escala al administrador de tu dependencia.

---

## 5. Errores y validaciones comunes que verás

| Situación | Regla | Qué hacer |
|-----------|-------|-----------|
| Falta CURP/RFC al crear beneficiario | obligatorio 10–18 caracteres (solo al crear) | Captura la CURP/RFC; no se puede crear sin ella. |
| Persona física sin nombre/apellidos, o moral sin razón social | `required_if:type` | Llena el campo según el tipo elegido. |
| Falta Estado o Municipio | `address_state` / `address_municipality` obligatorios | Selecciónalos en el formulario. |
| Fecha de nacimiento futura | P-05 (`before:today`) | Corrige la fecha. |
| "Se detectaron N posible(s) duplicado(s)" | P-02 duplicidad por CURP | El registro se creó pero quedó marcado; revisa duplicados desde el detalle. |
| Lat/Lng fuera de rango | `lat -90..90`, `lng -180..180` | Verifica las coordenadas. |
| No puedes guardar la inscripción (botón bloqueado) | P-06: programa con restricción geográfica y ubicación no elegible | Captura/corrige la ubicación del beneficiario (A.5) hasta que la validación dé elegible. |
| "El beneficiario no tiene ubicación registrada" | P-06 sin lat/lng | Reubica al beneficiario primero. |
| Monto entregado mayor al unitario del componente | P-08 `MontoNoExcedeComponente` | Reduce el monto al tope del componente. |
| No puedes editar una inscripción | estado terminal (FINALIZADO/RECHAZADO/CANCELADO) | No es editable; abre una nueva inscripción si procede. |
| 403 al aprobar/rechazar/finalizar/cancelar/eliminar | no tienes el permiso (sección 4) | Escala al `admin_dependencia`. |
| No entras a ninguna pantalla operativa | falta 2FA / cuenta no activada / correo no verificado | Configura 2FA, verifica tu correo y solicita activación. |
| No ves un beneficiario que esperabas | scoping por equipo (solo ves los de programas de tu `currentTeam`) | Confirma que el beneficiario tiene una inscripción en un programa de tu equipo. |

> Toda lectura y escritura de datos personales (CURP, ubicación, nombre) se **audita** automáticamente (`audit.pii` / `PiiAuditService`): queda registro de tu usuario, la acción y la fecha.
