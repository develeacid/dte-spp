# DTE-SPP 2026

## Stack
- Laravel 12, PHP 8.2+, PostgreSQL (pgvector/pgvector:pg16)
- Jetstream + Livewire 3 + Teams
- barryvdh/laravel-dompdf, maatwebsite/excel, symfony/expression-language
- Tests: `./vendor/bin/sail artisan test`

## Arquitectura
- Estructura híbrida Laravel + dominio: Cascade/, Mml/, Tracking/, Evaluation/
- Frontend: Atomic Design con Slots (Layout → Page → Module)
- Rutas: `routes/web/` separado por dominio (cascade.php, mml.php, etc.)

## Convenciones
- Commits: `tipo(scope): descripción` + `Resolves DTE-<n>` para Linear
- Sin modales para flujos principales (crear/editar = páginas completas)
- Componentes Blade: x-page.container, x-page.header, x-forms.section, etc.
- Tests: baseline 426 passed, 7 skipped

## Dominio del Negocio
- Sistema de Planeación para Programas Presupuestales (PbR-SED)
- MIR (Matriz de Indicadores para Resultados) es el módulo central
- Wizard de planeación MML para construir MIR paso a paso
- Reportes: Sábana de Captura, Concentrado, MIR Aprobada
- Alineación: PND → PED → ODS → Programas Derivados

## BD Pública (Transparencia)

Tras `sail up` por primera vez (o tras `sail down -v`), aprovisionar la BD pública `spp_public` y migrar las tablas `pub_*`:

```bash
sail artisan transparencia:provision-public-db
sail artisan migrate --path=database/migrations/public --database=pgsql_public
```

Idempotentes: re-ejecuciones safe. Las tablas `pub_*` viven en una BD separada (`spp_public`) con un rol `spp_portal` que solo tiene SELECT — el portal público (N2-04) consume desde ahí; el pipeline de sync (N2-03) escribe usando la conexión `pgsql_public`.

## Padrón GeoBase post-deploy / post-reset

Tras `migrate:fresh --seed`, los seeders setean `padron_geobase_activo=true` en N programas y dispatchan `RegisterProgramOnGeoBase` a queue async via `ProgramaPresupuestarioGeoBaseObserver`. **En prod (VPS) el servicio `laravel.worker` del `docker-compose.prod.yml` procesa los jobs automáticamente.** En dev local sin worker corriendo, los jobs quedan pendientes en redis y se manifiesta como 404 en dashboard card Padrón, tab Cobertura "Ver en vivo", etc.

Comando idempotente para re-hidratar todo (dev local o emergency en prod):

```bash
sail artisan geobase:hydrate-padron                       # registrar programas+componentes en geobase
sail artisan geobase:hydrate-padron --dry-run             # solo lista
sail artisan geobase:hydrate-indicador-variables          # vincular primera variable de cada componente al endpoint
sail artisan geobase:hydrate-indicador-variables --dry-run
```

`hydrate-padron` itera `ProgramaPresupuestario::where('padron_geobase_activo', true)` y llama síncronamente `PadronProvisioningService::register()`. Re-ejecuciones safe (geobase upserts por `spp_program_id`/`spp_mir_nivel_id`). Errores parciales no rompen el loop, exit code 1 si algún programa falló.

`hydrate-indicador-variables` vincula la primera variable (orden=1) de cada indicador de niveles COMPONENTE (+ PROPOSITO para ISM-001) a su endpoint geobase correspondiente. Idempotente: no sobreescribe vinculaciones existentes. Necesario tras `migrate:fresh` o si el dashboard card "Variables vinculadas" muestra 0/N.

## Worker permanente (VPS)

`docker-compose.prod.yml` incluye servicio `laravel.worker` que corre `queue:work redis --queue=geobase-sync,default --sleep=3 --tries=3 --max-time=3600` con `restart: unless-stopped`. Procesa los 4 jobs GeoBase + `SyncPublicDatasetJob` (N2-03) + cualquier otro sin onQueue explícito. Reuses la imagen `dte-spp-app` sin rebuild adicional.

Deploy command tras pull:

```bash
docker compose -f docker-compose.prod.yml up -d laravel.worker
docker compose -f docker-compose.prod.yml logs --tail=20 laravel.worker  # validar
```

Sin worker permanente, el síntoma operacional es `geobase:hydrate-padron` requerido como paso manual post-deploy + jobs huérfanos en redis.

## Pipeline N2-03 — Sync al portal público

`DatasetAbierto::publicar()` y `retirar()` disparan eventos que gatillan el job `SyncPublicDatasetJob`. El job invoca un Publisher concreto por `dataset_clave`:

- DS-01 → `pub_programas`
- DS-02 → `pub_mir_indicadores`
- DS-03 → `pub_avances_trimestrales`
- DS-04 → `pub_evaluaciones_anuales`
- DS-05 → `pub_alineacion_estrategica`
- DS-G01 → `pub_cobertura_municipal` (vía bulk endpoint geobase, acumulado al cierre trimestre)
- DS-G02 → `pub_desagregacion_demografica` (vía bulk endpoint geobase, buckets PP mexicana NNA/juventud/adulto/adulto_mayor)
- DS-G03 → `pub_cobertura_geografica` (vía bulk endpoint geobase, polígono unión PostGIS)
- DS-G04 → `pub_evolucion_temporal`
- (siempre, post-otro-publish/retire) → `pub_datasets_catalogo`
- DS-00 (políticas) y otras claves no mapeadas → loggea warning, termina sin error

Cada Publisher: DELETE+INSERT en transacción `pgsql_public` + cálculo de hash sha256 (excluye `id`/`created_at`/`updated_at` para idempotencia entre corridas).

Los publishers DS-G01/G02/G03 hacen una llamada HTTP al endpoint bulk correspondiente en geobase (`/api/v1/geobase/reportes/{cobertura-municipal,desagregacion,cobertura-geografica}-bulk`) usando el token M2M con ability `padron:read`. Acoplamiento: si geobase está caído al momento del publish, el job reintenta 3× con backoff 30s. Errores permanentes (4xx auth/validación) NO se reintentan; transitorios (5xx) sí.

Auditoría histórica en tabla `transparencia_publicaciones` (en `pgsql` privada): hash, count, success/error_message, user_id, action.

Recovery manual:

```bash
sail artisan transparencia:sync-public DS-01
```

Comandos post-deploy obligatorios para N2-03b: en geobase aplicar migration de la mvw trimestral y refrescar (`php artisan migrate --force && php artisan geobase:refresh-territorial`). En dte-spp solo `migrate`. Geobase debe deployarse ANTES que dte-spp (si no, los publishers G0X reciben 404 al primer publish).
