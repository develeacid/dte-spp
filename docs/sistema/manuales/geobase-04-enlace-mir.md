# Manual de Usuario — Enlace MIR (geobase)

> Sistema: **geobase** (padrón de beneficiarios + reportes territoriales).
> Rol técnico: `enlace_mir`. Es un rol **puente** entre el padrón operativo (geobase) y la planeación/seguimiento de la MIR (dte-spp).
> Usuario QA de referencia: `qa.enlace@eleaciddev.cloud` (definido en `QaTestingSeeder`).

---

## 1. Quién es este rol y qué permisos tiene

El **Enlace MIR** es el responsable de asegurar la **coherencia entre el padrón de beneficiarios (geobase) y la MIR de los programas (dte-spp)**: que las variables de los indicadores tengan su contraparte de cobertura en el padrón, que la cobertura por programa/componente se pueda consultar y verificar, y que los **snapshots criptográficos del padrón** (la evidencia que sustenta los Medios de Verificación de los indicadores de Componente) se generen y verifiquen correctamente.

Es un rol de **lectura transversal + generación/verificación de snapshots**. No captura beneficiarios ni inscripciones, no administra usuarios.

### Permisos reales del seeder (`database/seeders/RolesAndPermissionsSeeder.php`)

El rol `enlace_mir` es un rol **global** (Nivel 3 — "Machine-to-machine", `team_id = null`) y tiene asignados **exactamente 3 permisos**:

| Permiso | Qué habilita |
|---|---|
| `report.view_all` | Ver **todos** los reportes territoriales (cobertura, inversión, equidad, etc.) de **todos los programas/equipos**, sin confinamiento por team. Da acceso a las pantallas de Reportes y Análisis Espacial. |
| `snapshot.generate` | Generar snapshots criptográficos del padrón (programa/componente para un período). |
| `snapshot.verify` | Verificar la integridad (hash SHA256) de un snapshot existente. |

> **Importante (no inventado):** el rol `enlace_mir` **NO** tiene `beneficiary.*`, `enrollment.*`, `program.manage`, `layer.*` ni `user.*`. Solo `report.view_all`, `snapshot.generate`, `snapshot.verify`.

### Contraparte de máquina (token M2M)

El trabajo "real" del puente padrón↔MIR ocurre vía API entre los dos sistemas usando el **token de sistema `mir`** (`SystemTokenSeeder.php`), con las abilities Sanctum:

```
padron:read, padron:snapshot, padron:provision, padron:export-shcp
```

El Enlace MIR es la **persona** que supervisa y opera ese puente. Cuando entra por navegador a geobase usa los 3 permisos web de arriba; cuando dte-spp llama a geobase (activar padrón, generar snapshot, consultar cobertura), lo hace con el token `mir` configurado en `GEOBASE_API_TOKEN`.

---

## 2. A qué entra al iniciar sesión y cómo navega

Tras login (Sanctum + 2FA + equipo activo — middleware `auth:sanctum`, `verified`, `team.context`, `activated`, `require.2fa`), el Enlace MIR llega a:

- **Landing / Dashboard:** `GET /dashboard` (ruta `dashboard`, vista `dashboard.blade.php`). Resumen general de geobase.

### Barra de navegación (top-bar, `navigation-menu.blade.php`)

La navegación es una barra superior horizontal con estos enlaces:

| Enlace | Ruta | ¿El Enlace MIR lo usa? |
|---|---|---|
| Dashboard | `dashboard` | Sí (landing). |
| Beneficiarios | `beneficiaries.index` | **Verá el enlace pero entrar le da 403**: requiere `beneficiary.view` que NO tiene. |
| Inscripciones | `enrollments.index` | **403**: requiere `enrollment.view` que NO tiene. |
| Reportes | `reportes.index` | **Sí** — su zona principal de trabajo (gated por `report.view_all`). |
| Sync (offline) | `sync.index` | Operativo de campo; fuera de su alcance. |
| Análisis Espacial | `analisis-espacial` | **Sí** (gated por `report.view_all` / `layer.view_all`). |

> Los enlaces de la barra **no están ocultos por permiso individual**; el control real está en cada ruta (Gate/Policy). Si el Enlace MIR hace clic en Beneficiarios o Inscripciones, recibirá **403** porque no tiene esos permisos. Lo esperado es que trabaje en **Reportes** y **Análisis Espacial**, y que las operaciones de snapshot las dispare desde **dte-spp** (que llama a geobase por API).

---

## 3. Tareas principales paso a paso

> Convención: el Enlace MIR **consulta y verifica en geobase**, y **dispara las acciones de padrón/snapshot desde dte-spp** (que ejecuta las llamadas API con el token `mir`). Por eso varias tareas tienen un "lado geobase" (lo que ve/verifica) y un "lado dte-spp" (desde dónde se dispara).

### Flujo A — Vínculo variables de indicador ↔ endpoints del padrón

**Objetivo:** garantizar que la primera variable de cada indicador de Componente (numerador) esté vinculada al endpoint de cobertura del componente en geobase, para que el seguimiento alimente la fórmula desde el padrón.

**Cómo se establece el vínculo (lado dte-spp, operativo/comando):**
- Comando idempotente en dte-spp:
  - `sail artisan geobase:hydrate-indicador-variables` — vincula la primera variable (orden=1) de cada indicador de nivel **COMPONENTE** (+ PROPÓSITO para ISM-001) a su endpoint geobase.
  - `sail artisan geobase:hydrate-indicador-variables --dry-run` — solo lista, no escribe. No sobreescribe vínculos existentes.
- Requisito previo: el programa y sus componentes deben estar registrados en geobase (`geobase:hydrate-padron`).

**Cómo se verifica que el vínculo funciona (lado dte-spp, UI):**
- **Ruta:** `/{programa}/cobertura` (ruta `mml.cobertura`, componente `CoberturaPrograma`) — permiso `ver_padron` en dte-spp.
- **Ruta:** `/seguimiento/captura/{avance_id}` (ruta `tracking.captura`, componente `CapturaAvance`) — botón **"Sincronizar variables desde GeoBase"**: trae el valor del numerador desde el padrón para la fórmula del indicador del Componente.

**Lado geobase (lo que confirma el Enlace MIR):**
- El endpoint que alimenta la variable es `GET /api/v1/geobase/components/{spp_mir_nivel_id}/coverage` (ability `padron:read`).
- **Resultado:** la variable del indicador queda alimentada por la cobertura real del padrón; si el dashboard de dte-spp muestra "Variables vinculadas 0/N", el vínculo no se hidrató → re-correr el comando.

---

### Flujo B — Consultar cobertura por programa y por componente

**Objetivo:** revisar cuántos beneficiarios/inscripciones hay por programa, componente, municipio y período (insumo del numerador de indicadores y del análisis de supuestos).

**Lado geobase — Reportes (UI):**
- **Ruta:** `/reportes` → redirige a `/reportes/heatmap`; o directamente `/reportes/{slug}` (ruta `reportes.show`, componente `ReportePage`).
- **Slugs disponibles:** `heatmap`, `cobertura`, `inversion`, `componente`, `equidad-genero`, `densidad-etnica`, `evolucion-temporal`.
- **Permiso:** `report.view_all` (lo tiene) o `report.export`.
- **Acciones:** filtrar por municipio/región/programa, ver choropleth y tablas agregadas.
- **Resultado:** cobertura municipal/por componente/desagregada visible para todos los programas.

**Lado geobase — Análisis Espacial (UI):**
- **Ruta:** `/analisis-espacial` (ruta `analisis-espacial`, componente `AnalisisEspacial`).
- **Acciones:** seleccionar municipios/regiones, capas, dimensiones y agregados; **Ejecutar Consulta** (`POST /analisis-espacial/query`); **Guardar** (pide nombre); **Exportar CSV** (`POST /analisis-espacial/export`).
- **Resultado:** consulta territorial a la medida, exportable.

**Lado geobase — Endpoints de cobertura (API, token `mir`, ability `padron:read`):**
- `GET /api/v1/geobase/programs/{spp_program_id}/coverage` — cobertura del programa por estado/período/municipios.
- `GET /api/v1/geobase/components/{spp_mir_nivel_id}/coverage` — cobertura del componente.
- `GET /api/v1/geobase/programs/{spp_program_id}/atendida-proposito` — población atendida (beneficiarios únicos del Propósito, por ejercicio).
- `GET /api/v1/geobase/reportes/cobertura-municipal`, `/equidad-genero`, `/densidad-etnica`, `/evolucion-temporal`, `/cobertura-componente`.

**Lado dte-spp (espejo de lo que se consume):**
- `/{programa}/cobertura` (`CoberturaPrograma`) consume `getProgramCoverage()` y muestra inscripciones por estatus/municipio + **alertas por caída ≥10 % / ≥25 %** (señal de un supuesto fallando) + supuestos de Propósito y Componentes.

---

### Flujo C — Generar y verificar snapshots del padrón (su tarea distintiva)

**Objetivo:** producir la "fotografía" criptográfica del padrón de un programa/componente para un período. Ese snapshot (hash SHA256) es la **evidencia que sustenta el Medio de Verificación** del indicador de Componente.

**Generar snapshot — se dispara desde dte-spp:**
- **Ruta dte-spp:** `/{programa}/padron` (ruta `mml.padron`, componente `PadronPrograma`).
- **Botón:** **"Generar snapshot del trimestre"** (`wire:click="generarSnapshot"`) — requiere permiso dte-spp `generar_snapshot_padron`.
- **Qué ocurre:** `PadronSnapshotService::generar()` llama `POST /api/v1/geobase/snapshots/generate` (ability `padron:snapshot`). geobase produce un hash **SHA256 idempotente** y dispara el webhook `snapshot.generated`. dte-spp persiste la evidencia en `avance_evidencias`.
- **Permiso web en geobase:** `snapshot.generate` (lo tiene el Enlace MIR).
- **Resultado:** snapshot creado, hash registrado, MV del Componente respaldado.

**Verificar / listar snapshots (lado geobase, API):**
- `GET /api/v1/geobase/snapshots` — listar (ability `padron:read`).
- `GET /api/v1/geobase/snapshots/{snapshot}` — ver período + hash.
- `POST /api/v1/geobase/snapshots/{snapshot}/verify` — **verificar integridad** del hash SHA256 (permiso `snapshot.verify`, ability `padron:read`).
- `GET /api/v1/geobase/snapshots/{snapshot}/download` — descargar archivo (ability `padron:export`; **el token `mir` no la incluye** → ver sección 4).

**Lado dte-spp (consulta histórica):**
- En `/{programa}/padron`: **Seleccionar Componente** → carga snapshots; **Seleccionar Snapshot** → KPIs históricos; **Toggle Fuente** (vivo ↔ snapshot). Usa `getSnapshots()`, `getSnapshotKpis()`, `getComponentCoverage()`.

---

### Flujo D — Registro del programa/componente en el padrón (provisión)

**Objetivo:** que el programa y sus componentes existan en geobase para poder consultar cobertura y generar snapshots. **Idempotente.**

**Se dispara desde dte-spp:**
- **Ruta:** `/{programa}/padron` → **"Activar padrón en GeoBase"** (`wire:click="activarPadron"`) / **"Desactivar padrón"** (modal `desactivar-padron-{id}`). Requiere `generar_snapshot_padron` en dte-spp.
- **Comando idempotente (dev/emergencia):** `sail artisan geobase:hydrate-padron` (o `--dry-run`). Itera los programas con `padron_geobase_activo=true` y llama `PadronProvisioningService::register()`.

**Lado geobase (API, ability `padron:provision`):**
- `POST /api/v1/geobase/programs` (lookup `spp_program_id`) — registrar/actualizar programa.
- `POST /api/v1/geobase/components` (lookup `spp_mir_nivel_id`) — registrar/actualizar componente.
- **Resultado:** programa y componentes provisionados (upsert por `spp_program_id` / `spp_mir_nivel_id`). Sin esto, los endpoints de cobertura y los snapshots devuelven 404.

> **Operativo importante:** en prod el servicio `laravel.worker` procesa los jobs de registro async. En dev sin worker corriendo, los jobs quedan pendientes en Redis → la card "Padrón" del dashboard aparece en 404 → re-hidratar con `geobase:hydrate-padron`.

---

## 4. Qué NO puede hacer (acciones bloqueadas o que no verá)

Por **permisos web** (rol `enlace_mir` solo tiene `report.view_all`, `snapshot.generate`, `snapshot.verify`):

- **No puede ver ni gestionar beneficiarios.** `/beneficiaries`, `/beneficiaries/create`, `/{id}/edit`, `/relocate` → **403** (falta `beneficiary.view/create/update`).
- **No puede ver ni gestionar inscripciones.** `/enrollments` y todas sus transiciones (crear, aprobar, rechazar, finalizar, cancelar, resolver) → **403** (falta `enrollment.*`).
- **No puede administrar programas en geobase** (`program.manage`) ni **capas** (`layer.request/manage`).
- **No puede invitar ni administrar usuarios** (`user.invite/user.manage`).
- **No exporta padrón con CURP descifrado** desde web: el flujo SHCP (`/api/v1/geobase/padron/shcp`) exige ability `padron:export-shcp`; ese formato se descarga **desde dte-spp** (`/evaluacion/exportar/padron-shcp/{programa}`) con el permiso restrictivo `exportar_padron_shcp` (planeador), no con el rol Enlace MIR.

Por **abilities del token M2M `mir`** (`padron:read, padron:snapshot, padron:provision, padron:export-shcp`):

- **No puede crear/editar beneficiarios ni inscripciones por API**: faltan `padron:register`, `padron:enroll`, `padron:update-identity` (esas son del token `operadores-ur`).
- **No puede validar CURP/ubicación por API**: falta `padron:validate` (token `sistema-tramites`).
- **No puede `download` de snapshot ni `reports/export` ni guardar saved-queries por API con el token `mir`**: requieren ability `padron:export`, que el token `mir` **no incluye** (es del token `contraloria`). La verificación de snapshot sí (usa `padron:read`); la descarga del archivo no.

---

## 5. Errores y validaciones comunes que encontrará

| Síntoma / Error | Causa | Acción |
|---|---|---|
| **403 al entrar a Beneficiarios o Inscripciones** | El rol `enlace_mir` no tiene `beneficiary.*` ni `enrollment.*`. El enlace del menú existe pero el acceso está gated. | Es comportamiento esperado. Trabajar en **Reportes** y **Análisis Espacial**. |
| **404 en cobertura / "Variables vinculadas 0/N" / card Padrón rota** | El programa/componentes no están registrados en geobase (jobs async pendientes sin worker), o las variables no se hidrataron. | dte-spp: `sail artisan geobase:hydrate-padron` y luego `geobase:hydrate-indicador-variables`. En prod verificar `laravel.worker`. |
| **Snapshot no genera / "GeoBase no disponible" (HTTP 503)** | geobase caído o token `mir` (`GEOBASE_API_TOKEN`) inválido/expirado, o el programa no provisionado. | Verificar token y conectividad; provisionar con `geobase:hydrate-padron`; reintentar "Generar snapshot del trimestre". |
| **Snapshot "duplicado" no crea uno nuevo** | La generación es **idempotente** por programa/componente+período: re-generar el mismo período devuelve el mismo hash. | Es correcto: misma fotografía = mismo hash SHA256. Para otra foto, cambiar el período. |
| **403 al descargar archivo de snapshot por API** | `GET /snapshots/{id}/download` exige ability `padron:export`, ausente en el token `mir`. | La **verificación** (`/verify`) sí funciona (usa `padron:read`). La descarga requiere otro token (p. ej. `contraloria`). |
| **429 Too Many Requests al consultar la API** | Rate limit `throttle:geobase-read` / `geobase-write` (≈15 req/min por defecto) en `/api/v1/geobase/*`. | Espaciar las consultas / reintentar tras la ventana de throttle. |
| **Alertas de caída ≥10 % / ≥25 % en cobertura (dte-spp)** | El tab Cobertura señala caídas de inscripciones por trimestre como posible **supuesto fallando** (Vínculo 4). | No es un error del sistema: es una alerta de gestión. Revisar el supuesto del Propósito/Componente y documentar. |
| **Snapshot pedido pero la evidencia no aparece en dte-spp** | El webhook `snapshot.generated` no fue recibido/procesado (worker o suscripción de webhook). | Verificar `laravel.worker` y la suscripción de webhooks; re-disparar la generación (idempotente, no duplica). |

---

### Resumen de rutas clave para el Enlace MIR

| Acción | Sistema | Ruta / Endpoint | Permiso / Ability |
|---|---|---|---|
| Ver reportes territoriales | geobase | `/reportes/{slug}` | `report.view_all` |
| Análisis espacial + export CSV | geobase | `/analisis-espacial`, `.../query`, `.../export` | `report.view_all` |
| Verificar integridad de snapshot | geobase | `POST /api/v1/geobase/snapshots/{id}/verify` | `snapshot.verify` / `padron:read` |
| Cobertura programa/componente (API) | geobase | `GET /api/v1/geobase/{programs,components}/.../coverage` | `padron:read` |
| Generar snapshot del trimestre | dte-spp → geobase | `/{programa}/padron` → `POST /api/v1/geobase/snapshots/generate` | `generar_snapshot_padron` / `padron:snapshot` (+ `snapshot.generate` web) |
| Activar/Provisionar padrón | dte-spp → geobase | `/{programa}/padron`; `geobase:hydrate-padron` | `generar_snapshot_padron` / `padron:provision` |
| Vincular variables a endpoints | dte-spp | `geobase:hydrate-indicador-variables` | (comando) |
| Sincronizar variable en captura | dte-spp | `/seguimiento/captura/{id}` (botón GeoBase) | `ver_padron` |
