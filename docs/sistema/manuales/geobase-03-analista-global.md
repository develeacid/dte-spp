# Manual de Usuario — Analista global (GeoBase)

> Sistema: **GeoBase** (padrón de beneficiarios y reportes territoriales).
> Stack: Laravel + PostGIS. Acceso: sesión web con 2FA obligatorio.
> Alcance del rol: reportes territoriales, mapas, análisis espacial, desagregación demográfica, consultas guardadas, vistas materializadas.

---

## 1. Quién es este rol y qué permisos tiene

El **Analista global** (`analista_global`) es un rol **global** (no confinado a un team/dependencia): ve los datos de **todas** las unidades responsables, pero **solo en modo lectura y análisis**. Es el perfil de inteligencia territorial: explora cobertura, inversión, equidad de género, densidad étnica y evolución temporal en mapas y reportes, exporta esos datos y guarda consultas reutilizables. **No** captura ni modifica el padrón.

Permisos reales asignados en el seeder `database/seeders/RolesAndPermissionsSeeder.php` (rol `analista_global`):

| Permiso | Qué habilita |
|---|---|
| `beneficiary.view` | Ver el padrón de beneficiarios (de todos los teams, lectura) |
| `enrollment.view` | Ver inscripciones (de todos los teams, lectura) |
| `program.view` | Ver programas registrados |
| `report.view_all` | Ver **todos** los reportes territoriales (no solo los de su team) |
| `report.export` | Exportar reportes a CSV |
| `layer.view_all` | Ver **todas** las capas geográficas para análisis espacial |
| `snapshot.verify` | Verificar la integridad de snapshots del padrón (hash) |

Lo que **NO** tiene (confirmado por ausencia en el seeder): `beneficiary.create/update/delete`, `enrollment.create/update/approve/reject`, `program.manage`, `layer.request/manage`, `snapshot.generate`, `user.invite/manage`. Tampoco es `sysadmin` (que haría bypass de todos los gates vía `Gate::before`).

> Diferencia clave con el `analista_local` / `operador`: ellos tienen `report.view_team` (solo su dependencia); el analista global tiene `report.view_all` (estatal completo).

---

## 2. A qué entra al iniciar sesión y cómo navega

- **2FA obligatorio**: el middleware `require.2fa` bloquea el acceso hasta completar el segundo factor. Si no lo has configurado, hazlo desde tu perfil antes de poder operar.
- **Landing**: tras autenticarte caes en el **Dashboard** (`/dashboard`, vista `dashboard.blade.php`).
- **Barra de navegación** (`navigation-menu.blade.php`), enlaces visibles para tu sesión:
  - **Dashboard** → `/dashboard`
  - **Beneficiarios** → `/beneficiaries` (solo lectura para ti)
  - **Inscripciones** → `/enrollments` (solo lectura para ti)
  - **Reportes** → `/reportes` (redirige a `/reportes/heatmap`) — **tu home de trabajo**
  - **Sync** → `/sync`
  - **Análisis Espacial** → `/analisis-espacial` — **tu herramienta principal**

> Nota de seguridad: los enlaces del menú **no** están ocultos por permiso; todos los usuarios autenticados los ven. La restricción real es por **acción interna**: cuando entres a Beneficiarios/Inscripciones verás botones de crear/editar/aprobar **bloqueados o ausentes** (gates `beneficiary.*`, `enrollment.*`). Tu trabajo vive en **Reportes** y **Análisis Espacial**.

---

## 3. Tareas principales paso a paso

### Flujo A — Reportes territoriales temáticos

**A1. Abrir el panel de reportes**
- **Objetivo**: explorar un reporte territorial por tema sobre mapa de Oaxaca.
- **Ruta/URL**: `/reportes` (te redirige a `/reportes/heatmap`) o directo `/reportes/{slug}`.
- **Slugs válidos**: `heatmap`, `cobertura`, `inversion`, `componente`, `equidad-genero`, `densidad-etnica`, `evolucion-temporal`.
- **Vista/Componente**: `livewire.reportes.report-page-wrapper` → Livewire `App\Livewire\Reportes\ReportePage`.
- **Permiso necesario**: `report.view_all` (lo tienes) o `report.export`.
- **Resultado**: dashboard del tema con mapa Leaflet/choropleth y datos agregados por municipio/región.

**A2. Cambiar de tema de reporte**
- **Objetivo**: pasar de mapa de calor a cobertura, inversión, equidad de género, etc.
- **Acción**: navega a `/reportes/{slug}` con el slug deseado (desde el menú lateral del panel o cambiando la URL).
- **Los 7 reportes disponibles** (cada uno tira de su endpoint de datos `/reportes/data/...`, autenticado por sesión, sin token):
  - `heatmap` → puntos de calor de beneficiarios (`/reportes/data/heatmap-points`)
  - `cobertura` → cobertura municipal (`/reportes/data/cobertura-municipal`)
  - `inversion` → inversión municipal y regional (`/reportes/data/inversion-municipal`, `/inversion-regional`)
  - `componente` → cobertura por componente del programa (`/reportes/data/cobertura-componente`)
  - `equidad-genero` → desagregación por género (`/reportes/data/equidad-genero`)
  - `densidad-etnica` → desagregación por etnia/población indígena (`/reportes/data/densidad-etnica`)
  - `evolucion-temporal` → series de tiempo (`/reportes/data/evolucion-temporal`)
- **Filtros comunes**: `municipio_ids[]` o `region_ids[]` (mutuamente excluyentes).
- **Resultado**: el mapa y los KPIs se recalculan para el tema y el territorio elegido.

> Estos reportes leen de la **vista materializada** `vw_reporte_territorial` (modelo `ReporteTerritorial`) y de tablas INEGI/CONAPO (`inegi_municipios`, `regiones_oaxaca`, `inegi_censo_poblacion`, `marginacion_indices`). Los datos traen un `refreshed_at`: si ves cifras desactualizadas, es que la vista materializada aún no se ha refrescado (lo hace `geobase:refresh-territorial`, fuera de tu alcance — repórtalo a un sysadmin / enlace técnico).

---

### Flujo B — Análisis espacial interactivo (consultas ad-hoc)

**B1. Construir una consulta geoestadística**
- **Objetivo**: cruzar dimensiones y agregados sobre un territorio seleccionado, en vivo.
- **Ruta/URL**: `/analisis-espacial` (route `analisis-espacial`).
- **Vista/Componente**: Livewire `App\Livewire\Analisis\AnalisisEspacial` → `livewire.analisis.analisis-espacial`.
- **Permiso necesario**: `report.view_all` o `layer.view_all` (tienes ambos).
- **Pasos**:
  1. **Selecciona territorio**: toggle entre **municipios** o **regiones**; busca/filtra municipios por nombre.
  2. **Selecciona capas** de análisis (catálogo `GeoLayer`, `layer.view_all`).
  3. **Configura el desglose** (dimensiones del `ReportQueryBuilder`): `municipio`, `region`, `programa`, `componente`, `genero`, `etnia`, `tipo_apoyo`, `grupo_edad`, `discapacidad`, `ejercicio`, `status`.
  4. **Configura los agregados**: `total_beneficiarios`, `total_enrollments`, `monto_total`, `monto_promedio`.
  5. **Aplica filtros** opcionales: `program_id`, `spp_program_id`, `component_id`, `municipio_ids`, `region_ids`, `genero`, `etnia_id`, `es_indigena`, `discapacidad`, `date_from/date_to`, `status`, `ejercicio`.
  6. Pulsa **Ejecutar Consulta** (POST `/analisis-espacial/query`).
- **Botones del panel**: Ejecutar Consulta, Guardar, Exportar CSV, Restablecer Vista, Limpiar Mapa, Cargar Consulta Guardada.
- **Resultado**: choropleth municipal con tooltips de datos y panel de KPIs (total, promedio, municipios con datos).

**B2. Exportar el resultado a CSV**
- **Objetivo**: bajar la tabla de la consulta actual.
- **Acción**: botón **Exportar CSV** → POST `/analisis-espacial/export` (route `analisis-espacial.export`, controlador `ReportExportController@export`).
- **Permiso**: requiere `report.export` (lo tienes); por sesión web, sin token Sanctum.
- **Resultado**: descarga CSV en streaming (chunks de 500 filas).

---

### Flujo C — Consultas guardadas (reutilizables)

**C1. Guardar la consulta actual**
- **Objetivo**: conservar una configuración (dimensiones + agregados + filtros) para reusarla.
- **Dónde**: dentro de `/analisis-espacial`, botón **Guardar**.
- **Mecánica**: `AnalisisEspacial::saveQuery(string $name, array $configuration)` → crea un registro `SavedQuery` (modelo `App\Models\SavedQuery`).
- **Importante (scoping)**: `SavedQuery` tiene `TeamScope` global y guarda `team_id` + `user_id`. Tus consultas quedan ligadas a tu **team actual**; si cambias de team, verás otra lista.
- **Resultado**: la consulta aparece en la lista de "Consultas Guardadas" del panel; un alert confirma el guardado.

**C2. Cargar / ejecutar una consulta guardada**
- **Acción**: botón **Cargar Consulta Guardada** en el panel → se repuebla el formulario y puedes Ejecutar.
- **Resultado**: el mapa se redibuja con la configuración recuperada.

> Las operaciones equivalentes por **API** (`/api/v1/geobase/saved-queries`, crear/ejecutar requieren ability `padron:export`) son para tokens M2M (rol `enlace_mir`), **no** para tu sesión web. Como analista global trabajas el guardado desde la UI de Análisis Espacial.

---

### Flujo D — Mapas e imágenes (captura de polígonos y choropleths)

**D1. Ver un polígono territorial individual**
- **Objetivo**: visualizar un municipio o región en mapa interactivo.
- **Ruta/URL**: `/render/mapa/poligono/{tipo}/{id}` con `tipo` = `municipio` | `region` (público, sin login).
- **Resultado**: mapa Leaflet del polígono con tiles OSM.

**D2. Obtener imagen estática (PNG/SVG) de un polígono**
- **Ruta/URL**: `/geo/imagen/poligono/{tipo}/{id}?format=png|svg` (route `geo.imagen.poligono`, auth de sesión).
- **Resultado**: imagen PNG/SVG generada vía `GeoImageService` (PostGIS) — útil para pegar en reportes.

**D3. Imagen renderizada de mapa (Playwright)**
- **Ruta/URL**: `/geo/imagen/mapa/{tipo}/{id}?width=800&height=600` (route `geo.imagen.mapa`).
- **Resultado**: captura PNG del mapa Leaflet (vía `MapImageService` + Playwright). Si tarda o falla, suele ser el contenedor de captura (Chromium) — repórtalo al equipo técnico.

> El choropleth de una consulta temática (`/render/mapa/consulta?key=...` y `/geo/imagen/consulta`) lo dispara internamente el panel de Análisis Espacial al guardar/exportar; normalmente no construyes esa URL a mano.

---

### Flujo E — Consultar padrón e inscripciones (solo lectura) y verificar snapshots

**E1. Revisar el padrón**
- **Objetivo**: inspeccionar beneficiarios para contextualizar un reporte (no para editar).
- **Ruta/URL**: `/beneficiaries` (lista), `/beneficiaries/{id}` (detalle).
- **Permiso**: `beneficiary.view` (lo tienes, alcance global).
- **Filtros** (BeneficiaryList): búsqueda por hash de CURP exacto o municipio/email, tipo (persona física/moral), estado, municipio, activo.
- **Resultado**: lista paginada (15) y ficha con PII cifrada (acceso auditado por `PiiAuditService`). **No verás** botones de crear/editar/reubicar/eliminar habilitados.

**E2. Revisar inscripciones**
- **Ruta/URL**: `/enrollments` (lista), `/enrollments/{id}` (detalle).
- **Permiso**: `enrollment.view`.
- **Resultado**: estado, monto, folio de evidencia, historial de estados — todo en lectura. Sin botones de aprobar/rechazar/finalizar/cancelar.

**E3. Verificar la integridad de un snapshot del padrón**
- **Objetivo**: confirmar que un snapshot no fue alterado (hash íntegro).
- **Permiso**: `snapshot.verify` (lo tienes).
- **Dónde**: en geobase la verificación de snapshot se expone vía acción/endpoint de snapshot (`snapshots/{snapshot}/verify`). Por sesión web podrás disparar **Verificar** donde aparezca la acción de snapshots; **no** podrás **Generar** snapshots (eso es `snapshot.generate`, que no tienes).
- **Resultado**: confirmación de integridad (OK / hash no coincide).

---

## 4. Qué NO puede hacer (acciones bloqueadas o no visibles)

Aunque el menú muestre Beneficiarios e Inscripciones, como analista global **no** puedes:

- **Crear/editar/reubicar/eliminar beneficiarios** → falta `beneficiary.create/update/delete`. Los botones aparecen deshabilitados o el formulario aborta 403.
- **Crear/editar inscripciones** ni **aprobar / rechazar / finalizar / cancelar / resolver** → falta `enrollment.create/update/approve/reject`. Las acciones de transición de estado en `/enrollments/{id}` no estarán disponibles.
- **Gestionar programas** (registrar/editar) → falta `program.manage`. Solo `program.view`.
- **Solicitar o administrar capas** (`layer.request`, `layer.manage`) → solo puedes **verlas** (`layer.view_all`).
- **Generar snapshots** del padrón (`snapshot.generate`) → solo **verificarlos** (`snapshot.verify`).
- **Invitar o administrar usuarios** (`user.invite`, `user.manage`).
- **Exportar el padrón SHCP** ni usar la **API M2M** (`/api/v1/geobase/...`): esos endpoints exigen abilities de token Sanctum (`padron:read`, `padron:export`, `padron:export-shcp`) propias del rol `enlace_mir`, no de tu sesión web.
- **Bypass de gates**: no eres `sysadmin`, así que no se te concede nada por la vía `Gate::before`.

Si necesitas alguna de estas acciones, escala con un `admin_dependencia` (operaciones de padrón en su team) o un `sysadmin`.

---

## 5. Errores y validaciones comunes que encontrarás

- **403 / acción no disponible** al intentar crear o aprobar en Beneficiarios/Inscripciones: es lo esperado por tu rol de solo lectura. No es un bug; usa los flujos de reportes/análisis.
- **Bloqueo por 2FA**: si `require.2fa` te saca, completa la configuración de doble factor en tu perfil; sin ella no entras a ninguna ruta protegida.
- **Lista de consultas guardadas "vacía" tras cambiar de team**: `SavedQuery` está scopeada por `team_id`. Cambiaste de team y por eso no ves tus consultas anteriores; vuelve a tu team original.
- **Reporte con datos viejos / `refreshed_at` antiguo**: la vista materializada `vw_reporte_territorial` no está al día. El refresh (`geobase:refresh-territorial`) es tarea de operación/sysadmin; repórtalo, no es algo que puedas forzar.
- **404 en `/reportes/{slug}`**: el slug no es válido. Usa solo: `heatmap`, `cobertura`, `inversion`, `componente`, `equidad-genero`, `densidad-etnica`, `evolucion-temporal`.
- **404 en `/render/mapa/poligono/{tipo}/{id}`**: `tipo` debe ser exactamente `municipio` o `region`, y el `id` debe existir y tener geometría.
- **Filtros territoriales que se ignoran o chocan**: `municipio_ids[]` y `region_ids[]` son **mutuamente excluyentes** en los reportes; elige uno.
- **Mapa PNG vacío o que no carga**: la captura usa Playwright/Chromium en contenedor; ante fallos de render (timeouts, imagen en blanco) es infraestructura de captura, no tus permisos — escala al equipo técnico.
- **Export CSV grande lento**: la exportación es en streaming por chunks de 500 filas; espera a que termine en vez de recargar.
