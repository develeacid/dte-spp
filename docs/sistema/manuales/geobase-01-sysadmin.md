# Manual de Usuario — Rol Sysadmin (GeoBase)

> Sistema: **GeoBase** (Laravel + PostGIS). Padrón de beneficiarios y reportes territoriales del ecosistema PbR-SED.
> Este rol NO existe en dte-spp; vive y opera exclusivamente en GeoBase.
> Repo: `/home/eleacid/code/laravel/geobase` · App web (dev): `http://localhost:8081`

---

## 1. Quién es este rol y qué permisos tiene

El **Sysadmin** es el administrador global de la plataforma GeoBase. Es un **rol global** (`team_id = null`, "Level 1" en el seeder de roles), no confinado a ninguna dependencia/UR. Es el responsable de la infraestructura multi-tenant: equipos (teams = UR), tokens máquina-a-máquina (M2M), almacenamiento de objetos (snapshots), hidratación/sincronización del padrón demo y la configuración territorial (capas INEGI, marginación, vistas materializadas).

### Permisos reales (confirmados en `database/seeders/RolesAndPermissionsSeeder.php`)

El sysadmin **NO recibe permisos explícitos en el seeder**. En su lugar **bypassa todas las verificaciones** vía `Gate::before` en `app/Providers/AppServiceProvider.php`:

```php
// AppServiceProvider::boot()
Gate::before(function (User $user, string $ability) {
    // ... consulta roles globales (team_id = null) por DB directa
    if ($globalRoles->contains('sysadmin')) {
        return true;   // ← sysadmin pasa CUALQUIER gate/permiso
    }
});
```

Esto significa que el sysadmin tiene, de facto, **los 21 permisos del sistema** (los que se asignan explícitamente a otros roles):

- **Beneficiarios:** `beneficiary.view`, `beneficiary.create`, `beneficiary.update`, `beneficiary.delete`
- **Inscripciones:** `enrollment.view`, `enrollment.create`, `enrollment.update`, `enrollment.approve`, `enrollment.reject`
- **Programas:** `program.view`, `program.manage`
- **Reportes:** `report.view_team`, `report.view_all`, `report.export`
- **Capas:** `layer.view_all`, `layer.request`, `layer.manage`
- **Snapshots:** `snapshot.generate`, `snapshot.verify`
- **Usuarios:** `user.invite`, `user.manage`

> **Nota de diseño importante:** El bypass es a nivel **Gate** (web). La autenticación de la **API interna** (`/api/v1/...`) es por **token Sanctum con _abilities_** (`padron:read`, `padron:provision`, etc.), NO por rol. Es decir: el sysadmin es todopoderoso en la UI web, pero el acceso máquina-a-máquina depende de las _abilities_ del token, que el sysadmin **provisiona** (ver Tarea 2). Un sysadmin logueado en web no puede "saltarse" las abilities de un token M2M; gestiona los tokens, no los suplanta.

### Diferencia con los otros roles GeoBase

| Rol | Alcance | Para qué |
|-----|---------|----------|
| **sysadmin** | Global (team_id null), bypass total | Infraestructura, tenants, tokens, territorio |
| `analista_global` | Global, solo lectura de reportes de todos los teams | Visión transversal sin operar padrón |
| `admin_dependencia` | Confinado a su team/UR | Operación + invitar/gestionar usuarios de su UR |
| `operador` | Confinado a su team/UR | Captura de beneficiarios e inscripciones |
| `enlace_mir` | M2M (lo usa dte-spp) | report.view_all + snapshots |

---

## 2. A qué entra al iniciar sesión y cómo navega

### Login y landing
1. URL de login: `http://localhost:8081/login` (Jetstream).
2. **2FA obligatorio:** toda la web está protegida por `require.2fa` (más `auth:sanctum`, `verified`, `team.context`, `activated`). Si el sysadmin no tiene 2FA configurado, el sistema lo forzará antes de dejarlo navegar.
3. Tras login aterriza en `/` → al estar autenticado, su destino real es el **Dashboard**: `GET /dashboard` (ruta `dashboard`, vista `dashboard.blade.php`). El dashboard muestra widgets de estadísticas geobase (cacheados ~300s) que dependen del `currentTeam` activo.

### Sidebar / barra de navegación (`navigation-menu.blade.php`)
La barra superior expone los enlaces principales (visibles para sysadmin porque bypassa los gates):

- **Dashboard** → `route('dashboard')`
- **Beneficiarios** → `route('beneficiaries.index')` (`/beneficiaries`)
- **Inscripciones** → `route('enrollments.index')` (`/enrollments`)
- **Reportes** → `route('reportes.index')` (`/reportes`, redirige a `/reportes/heatmap`)
- **Sincronización** → `route('sync.index')` (`/sync`, cola offline)
- **Análisis Espacial** → `route('analisis-espacial')` (`/analisis-espacial`)

En el **menú de usuario (dropdown, arriba a la derecha)**:
- **Teams** → `route('teams.show', currentTeam->id)` / **Create Team** → `route('teams.create')` (gestión multi-tenant Jetstream)
- **API Tokens** → `route('api-tokens.index')` (tokens personales/M2M Sanctum)
- **Profile** → `route('profile.show')` (incluye configuración 2FA)
- **Log Out**

> **Importante:** Buena parte del trabajo de sysadmin **no es por UI** sino por **línea de comandos (`artisan`)** dentro del contenedor Sail de geobase. La UI cubre tenants, tokens y consulta; los comandos cubren storage, hidratación, territorio y mantenimiento.

---

## 3. Tareas principales paso a paso

> Convención de comandos: ejecutar dentro del proyecto geobase. En dev se usa Sail; en VPS de prod se usa `docker compose -f docker-compose.prod.yml exec ...`. Aquí se muestra la forma `php artisan ...`; antepón `./vendor/bin/sail artisan` en local.

---

### FLUJO A — Administración multi-tenant (teams / usuarios)

#### A1. Crear un equipo (tenant = dependencia/UR)
- **Objetivo:** dar de alta una nueva Unidad Responsable como tenant aislado.
- **Ruta/URL:** `route('teams.create')` → `/teams/create` (Jetstream Teams).
- **Vista/componente:** páginas Teams de Jetstream/Livewire.
- **Pasos:** menú usuario → **Create Team** → capturar nombre → **Create**.
- **Resultado:** team con `team_id` propio. Los roles `operador`/`admin_dependencia` y los beneficiarios/inscripciones quedan _scoped_ a ese team (middleware `team.context` + `SetTeamContext`).

#### A2. Invitar / gestionar usuarios de un team
- **Objetivo:** sumar operadores o un admin de dependencia a un team.
- **Ruta/URL:** `route('teams.show', {team})` → `/teams/{id}`.
- **Acciones:** sección "Team Members" → **Add Team Member** (email + rol). Jetstream Teams tiene `invitations => true`, así que se envía invitación por correo.
- **Resultado:** usuario asociado al team con el rol elegido. El sysadmin puede hacerlo sobre cualquier team gracias al bypass.

#### A3. Asignar el rol global `sysadmin` o `analista_global` a un usuario
- **Objetivo:** otorgar privilegios globales (no se hace por la UI de Teams porque esos roles son `team_id = null`).
- **Vía:** seeder/Tinker (no hay pantalla dedicada). Ejemplo por Tinker:
  ```php
  $u = App\Models\User::where('email','...')->first();
  // Rol global: setPermissionsTeamId(null) antes de asignar
  app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(null);
  $u->assignRole('sysadmin');
  ```
- **Resultado:** el usuario aparece en la consulta de `Gate::before` (que lee `model_has_roles` con `team_id IS NULL`) y obtiene bypass total (sysadmin) o lectura global de reportes (analista_global).

---

### FLUJO B — Tokens M2M / Sanctum (integración con dte-spp y otros sistemas)

GeoBase expone su API interna (`/api/v1/...`) autenticada por **tokens Sanctum con abilities**. dte-spp consume el padrón usando el token del "Sistema MIR". Provisionar y rotar estos tokens es responsabilidad del sysadmin.

#### B1. Provisionar/rotar los tokens M2M del catálogo (recomendado)
- **Objetivo:** crear/regenerar los 5 tokens de sistema con sus abilities exactas.
- **Comando:** `php artisan db:seed --class='Database\Seeders\SystemTokenSeeder'`
- **Componente real:** `database/seeders/SystemTokenSeeder.php`.
- **Qué hace:** por cada sistema crea (idempotente) un `User` M2M (`*@geobase.internal`), le garantiza un **personal team** (load-bearing para seguridad y widgets), emite un token Sanctum con sus scopes y registra metadatos en la tabla `system_tokens` (`is_active`, `expires_at = +1 año`, contacto). **Imprime el plain-text token en consola** (única vez que es visible).
- **Catálogo de tokens y abilities (del seeder):**

  | token_name | Abilities (scopes) | Consumidor |
  |---|---|---|
  | `mir` | `padron:read`, `padron:snapshot`, `padron:provision`, `padron:export-shcp` | **dte-spp** (MIR) |
  | `operadores-ur` | `padron:register`, `padron:enroll`, `padron:read`, `padron:update-identity` | Operadores UR |
  | `sistema-tramites` | `padron:validate` | Trámites |
  | `contraloria` | `padron:read`, `padron:export` | Contraloría |
  | `test` | todas las abilities padron:* | QA/dev |

- **Resultado:** copiar el plain-text del token `mir` y configurarlo en dte-spp como `GEOBASE_API_TOKEN`. **Tras un reset de geobase (`migrate:fresh`) este token cambia: re-sincronizarlo en dte-spp es paso obligatorio** (caveat conocido del ecosistema).

#### B2. Emitir un token desde la UI (tokens personales / ad-hoc)
- **Objetivo:** generar un token Sanctum manual con abilities seleccionadas.
- **Ruta/URL:** `route('api-tokens.index')` → `/user/api-tokens` (Jetstream API).
- **Acciones:** **Create API Token** → nombre + checkboxes de permisos → **Create**. Modal muestra el token **una sola vez**.
- **Resultado:** token bajo el usuario actual. Para M2M productivo, preferir B1 (queda auditado en `system_tokens`).

#### B3. Revocar/desactivar un token
- **Objetivo:** cortar acceso de un sistema comprometido o dado de baja.
- **Vía UI:** `/user/api-tokens` → **Delete** sobre el token del usuario.
- **Vía datos:** marcar `is_active = false` (o expirar) en `system_tokens`, y/o borrar el `personal_access_token` correspondiente.
- **Resultado:** las llamadas API de ese token reciben 401/403.

---

### FLUJO C — Storage de objetos (snapshots del padrón)

#### C1. Inicializar los buckets de almacenamiento
- **Objetivo:** crear los buckets MinIO/S3 que usa el disco de snapshots (necesario tras `sail up` por primera vez o tras `down -v`).
- **Comando:** `php artisan geobase:init-storage`
- **Componente real:** `app/Console/Commands/InitStorage.php` — _"Idempotently create the MinIO/S3 buckets used by the snapshots disk"_.
- **Resultado:** buckets creados (idempotente). Sin esto, generar snapshots del padrón falla por bucket inexistente.

---

### FLUJO D — Hidratación y datos del padrón (demo / QA)

#### D1. Inyectar padrón demo (enrollments) en programas vinculados a dte-spp
- **Objetivo:** poblar el padrón para que dte-spp muestre cobertura/KPIs reales (tab Padrón, mapa cobertura, dashboard card "Padrón").
- **Comando:** `php artisan geobase:seed-demo-padron`
- **Componente real:** `app/Console/Commands/SeedDemoPadron.php`.
- **Opciones:** `--per-component=50` (enrollments por componente), `--ejercicio=2026` (año de `enrollment_date`), `--team=` (team_id a asignar), `--force` (permitir fuera de local/testing).
- **Comportamiento:** idempotente; solo inyecta en programas con `spp_program_id` que estén **vacíos**. **Solo demo/QA** (requiere `--force` fuera de local). Es el paso de hidratación que hace durable el "padrón demo" del VPS de pruebas (caveat documentado: moverlo a seeder).
- **Resultado:** beneficiarios/inscripciones demo; la cobertura aparece en dte-spp.

> **Lado dte-spp (no es comando de geobase, pero el sysadmin lo coordina):** el _registro_ de programas/componentes en geobase lo dispara dte-spp con `geobase:hydrate-padron` y `geobase:hydrate-indicador-variables`. El sysadmin de geobase aporta el storage (C1), el padrón demo (D1) y el worker de cola que procesa los jobs.

#### D2. Refrescar las vistas materializadas territoriales
- **Objetivo:** recalcular agregados de `vw_reporte_territorial` (principal + trimestral) tras cargar/cambiar padrón o territorio.
- **Comando:** `php artisan geobase:refresh-territorial`
- **Componente real:** `app/Console/Commands/RefreshTerritorialView.php`.
- **Cuándo:** post-deploy (obligatorio para que los publishers DS-G0X de dte-spp no reciban datos viejos), tras importar capas/marginación, tras inyectar padrón demo.
- **Resultado:** reportes territoriales y endpoints bulk (`/reportes/*-bulk`) reflejan datos actuales.

---

### FLUJO E — Configuración territorial (capas INEGI, marginación, CONEVAL)

Estas cargas pueblan el catálogo geográfico (municipios, localidades, regiones) y los índices socioeconómicos que cruzan los reportes. Todos los comandos aceptan `--truncate` para reemplazar la tabla.

#### E1. Importar capas del Marco Geoestadístico (INEGI / PostGIS)
- **Objetivo:** cargar estados/municipios/localidades como geometría PostGIS.
- **Comando:** `php artisan geobase:import-inegi {layer} {file} [--mgn-version=] [--truncate]`
- **Componente real:** `ImportInegiLayer.php` (SHP → PostGIS vía `ogr2ogr`).
- **`layer`:** `estados` | `municipios` | `localidades`. **`file`:** ruta al `.shp` relativa a `storage/app/geo/`.

#### E2. Importar índices de marginación CONAPO (municipal)
- **Comando:** `php artisan geobase:import-marginacion {file.csv} [--anio=] [--fuente=CONAPO] [--truncate]`
- **Componente:** `ImportMarginacionData.php`.

#### E3. Importar marginación CONAPO a nivel localidad
- **Comando:** `php artisan geobase:import-marginacion-localidad {file.csv} [--anio=] [--fuente=CONAPO] [--truncate]`
- **Componente:** `ImportMarginacionLocalidadData.php`.

#### E4. Importar pobreza municipal CONEVAL
- **Comando:** `php artisan geobase:import-coneval {file.csv} [--anio=] [--fuente=CONEVAL] [--truncate]`
- **Componente:** `ImportConevalData.php`.
- **Tras cualquier E1–E4:** correr `geobase:refresh-territorial` (D2) para recalcular agregados.

---

### FLUJO F — Sincronización offline y webhooks (integración)

#### F1. Provisionar la suscripción de webhooks (notifica a dte-spp)
- **Objetivo:** registrar el endpoint receptor de dte-spp (M5) que recibe eventos de snapshot del padrón.
- **Comando:** `php artisan geobase:provision-webhook-subscription --name= --url= --events= [--secret-env=GEOBASE_WEBHOOK_SECRET] [--inactive] [--show]`
- **Componente:** `ProvisionWebhookSubscription.php` (idempotente por `--name`).
- **Resultado:** suscripción creada/actualizada; los eventos se firman con HMAC usando el secreto del env indicado.

#### F2. Procesar la cola de sincronización offline
- **Objetivo:** drenar entries pendientes de captura offline.
- **Comando:** `php artisan geobase:process-sync [--batch=]`
- **UI relacionada:** `/sync` (`SyncQueueList`, ruta `sync.index`) para inspeccionar la cola.

---

### FLUJO G — Mantenimiento, retención y privacidad (PII)

#### G1. Anonimizar PII vencida (LGPDPPSO Art. 14)
- **Comando:** `php artisan geobase:purge-pii-retention [--dry-run] [--limit=1000]`
- **Componente:** `PurgePiiRetention.php`. Usar `--dry-run` primero para auditar candidatos sin mutar.

#### G2. Purgar logs de auditoría por política de retención
- **Comando:** `php artisan geobase:audit-purge [--days=]`  (`PurgeAuditLogs.php`).

#### G3. Purgar entries expiradas de sync_queue y webhook_deliveries
- **Comando:** `php artisan geobase:purge-expired`  (`PurgeExpired.php`).

> Estos comandos suelen ir agendados (`schedule`). El sysadmin valida que el worker/scheduler de prod (`laravel.worker`) los esté corriendo.

---

## 4. Qué NO puede hacer

Aunque el sysadmin **bypassa todos los gates web**, hay límites reales:

1. **No suplanta abilities de tokens M2M.** El bypass es solo `Gate::before` (web). Las rutas `/api/v1/...` exigen un token Sanctum con la ability concreta (`padron:provision`, `padron:export-shcp`, etc.). El sysadmin **gestiona** tokens, pero una sesión web no autoriza llamadas API; necesita el token correcto.
2. **No salta el 2FA ni `activated`/`verified`.** El middleware de sesión (`require.2fa`, `activated`, `verified`) se aplica antes que cualquier gate. Sin 2FA configurado, no entra.
3. **No descifra PII fuera de los accessors auditados.** El acceso a CURP/nombre/ubicación pasa por `PiiAuditService` (middleware `audit.pii`); todo read/write de PII queda registrado. No hay "modo silencioso".
4. **No exporta padrón SHCP desde la web.** El padrón SHCP con CURP descifrado (`/api/v1/geobase/padron/shcp`) requiere la ability `padron:export-shcp` en el token; no es una pantalla web.
5. **No edita ROP (Reglas de Operación) reales todavía.** Las validaciones P-03 (ROP) y P-04 (interinstitucional) están **latentes** — no hay modelo ROP implementado (decisión C-098 pendiente). El sysadmin no encontrará pantalla para ello.
6. **El bypass no aplica a otros usuarios.** Asignar el rol `sysadmin` requiere `team_id = null`; hacerlo mal (con team scope) NO produce bypass (la consulta de `Gate::before` solo lee roles con `team_id IS NULL`).

---

## 5. Errores y validaciones comunes

### Operativos / infraestructura
- **Snapshots fallan: bucket inexistente.** Causa: no se corrió `geobase:init-storage` tras levantar/recrear el entorno. Solución: Flujo C1.
- **dte-spp muestra 404/503 en padrón o cobertura.** Causas típicas: (a) padrón vacío → correr `geobase:seed-demo-padron`; (b) programas no registrados → dte-spp `geobase:hydrate-padron`; (c) token `mir` desincronizado tras `migrate:fresh` → re-correr `SystemTokenSeeder` y actualizar `GEOBASE_API_TOKEN` en dte-spp; (d) worker de cola apagado → los jobs `RegisterProgramOnGeoBase` quedan pendientes en Redis.
- **Reportes territoriales muestran datos viejos/vacíos.** Falta `geobase:refresh-territorial` tras cargar padrón o capas (Flujo D2). Los publishers DS-G0X de dte-spp recibirán 404 si geobase se deployó **después** que dte-spp.
- **`seed-demo-padron` no inyecta nada.** Es idempotente: solo llena programas **vacíos** con `spp_program_id`. Si ya tienen enrollments, no duplica. Fuera de local exige `--force`.

### Validaciones de dominio que verá al operar padrón (P-01…P-08)
Si además captura beneficiarios/inscripciones (puede, por bypass), encontrará las reglas duras del padrón:
- **P-02 Duplicidad CURP:** al crear beneficiario, el sistema detecta CURP duplicada y marca el registro para revisión (no bloquea, notifica).
- **P-05 Inconsistencias:** coherencia lat/lng vs municipio, `fecha_nacimiento < hoy`.
- **P-06 Geográfica:** la ubicación de la inscripción debe caer dentro del área del programa; fuera de área → `OBSERVADO_DOMICILIO` (resolver con acción administrativa en `enrollments.resolve`).
- **P-07 Evidencia auditable:** **Finalizar** una inscripción exige `folio_evidencia` (TRF/acta) si no existe previamente.
- **P-08 Monto:** `monto_entregado` no puede exceder el `monto_unitario` del componente.
- **Estados terminales** (`FINALIZADO`, `RECHAZADO`, `CANCELADO`) no son editables; rechazar/cancelar exige `reason`.

### Tokens / seguridad
- **Token plain-text perdido.** Sanctum solo muestra el token **una vez** (al crearlo). Si se perdió, hay que regenerarlo (re-correr `SystemTokenSeeder` o crear uno nuevo en `/user/api-tokens`) y re-distribuirlo.
- **401/403 en API pese a token válido.** Casi siempre es **ability faltante**: el token no tiene el scope que la ruta exige (ej. llamar `/programs` register sin `padron:provision`). Revisar el catálogo de B1.
- **Usuario M2M sin team.** El seeder garantiza un personal team a cada usuario de sistema (es load-bearing para seguridad y widgets). Un M2M sin team romperá `team.context`/dashboard; re-correr `SystemTokenSeeder` lo repara.
