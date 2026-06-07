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

## Hardening MIR (sprint 2026-06-06)

Invariantes endurecidos a nivel BD + app (migraciones `2026_06_06_0000XX`, backfills idempotentes safe en prod):

- `mir_niveles.resumen_narrativo`, `indicadores.formula_texto` NOT NULL DEFAULT `''`; `indicadores.sentido` NOT NULL DEFAULT `'ascendente'`; `indicadores.unidad_medida_id` NOT NULL (fallback app-level al registro de catálogo `ND`/"No definida" vía hook `creating` de `Indicador`).
- `SentidoIndicador::REGULAR` **deprecado** (extensión fuera de norma); datos legacy migrados a `ascendente`.
- UNIQUE parcial: 1 FIN y 1 PROPOSITO por programa (`mir_niveles`), 1 problema/objetivo central por árbol (`arbol_nodos`), 1 team `rol='coordinadora'` por programa (`programa_team`). **Convención: `rol='coordinadora'` = UR administradora del temario (C-036)** — helper `ProgramaPresupuestario::urAdministradora()`.
- **Ventana de captura normativa SHCP**: cierre del periodo + `config('tracking.dias_ventana_captura', 30)` días (`TRACKING_DIAS_VENTANA_CAPTURA`). `CalendarizacionService::confirmar()` puebla `fecha_apertura/fecha_cierre` y re-confirmar recalcula fechas; los MetaPeriodo creados antes del sprint no se recalcularon.
- **Análisis de desviación estructurado**: captura amarillo/rojo exige `avances.analisis_desviacion` JSONB con keys `dato/causa/accion/proyeccion`; `justificacion_final` se mantiene como resumen concatenado para reportes legacy.
- **Revisiones de meta**: cambiar metas ya calendarizadas exige justificación → audit trail en `revisiones_meta`.
- Dataset transparencia **DS-07 Medios de Verificación** (`pub_medios_verificacion`); post-deploy: `sail artisan migrate --path=database/migrations/public --database=pgsql_public` + re-seed `sail artisan db:seed --class='Database\Seeders\Transparencia\DatasetsCatalogoSeeder'`.

## Semaforización 4 rangos (sprint 2026-06-07)

- Semáforo de avances tiene **4 valores**: `verde|amarillo|rojo|rojo_alto`. `rojo_alto` = sobrecumplimiento (señal de mala planeación, norma CONEVAL); color UI **púrpura** (`bg-purple-500` / `#a855f7`).
- Sin rangos definidos, el fallback por meta marca rojo_alto cuando cumplimiento > `config('tracking.umbral_sobrecumplimiento', 130)` % (`TRACKING_UMBRAL_SOBRECUMPLIMIENTO`; requiere valor > 100). Descendente: espejo (resultado < meta×(2−U/100)).
- Rangos capturables en el editor MIR (sección "Semáforo (rangos)") con validaciones duras B3-B6: meta∈verde, sin solapamiento, PCT⊂[0,100], rojo_min≠0 (`IndicadorReglasService::validarRangosSemaforo`).
- `medios_verificacion.frecuencia` normalizada al enum `FrecuenciaMedicion` (select en UI); regla B7: el MV debe publicarse al menos tan frecuentemente como se mide el indicador (`orden()` del enum).
- `conteo_semaforos` JSONB y `pub_evaluaciones_anuales` incluyen `rojo_alto`/`semaforos_rojo_alto`. Captura amarillo/rojo/**rojo_alto** exige análisis de desviación.
- Las reglas B3-B7 también son hallazgos `advertencia` en el diagnóstico de import (latentes hasta que el parser emita rangos/clave_unidad/frecuencia de MV).
- Post-deploy: `migrate` (privada: 2 migraciones) + `migrate --path=database/migrations/public --database=pgsql_public` (columna nueva).

## Evaluación Externa estructurada (sprint 2026-06-07)

- Entidad **`EvaluacionExterna`** (separada del cálculo interno `EvaluacionPrograma`): tipo enum `TipoEvaluacionExterna` (diseno/procesos/consistencia_resultados/impacto/eed), evaluador, fechas, estado; varios tipos por programa+ejercicio. FK opcional al cálculo interno (scoped por programa+ejercicio).
- **`InformeEvaluacion`** 1:1 (nace al crear la evaluación): 6 secciones del temario M10 — 4 texto (resumen ejecutivo/metodología/conclusiones/fichas) + 2 estructuradas (`Hallazgo` con severidad → `Recomendacion` con prioridad).
- **Cadena normativa C-143**: Hallazgo → Recomendación (evaluador) → ASM (compromiso UR) vía `asms.recomendacion_id` nullable (sin backfill: legacy desvinculado es estado normal). Borrar hallazgo desvincula ASMs (nullOnDelete, confirm avisa).
- UI: `/evaluacion/externas` (Index/Form/InformeEditor full-page). Permisos: `ver_evaluacion_externa` (todos los roles), `gestionar_evaluacion_externa` (planeador+admin, NO operador). Seeder en Fase0.
- **C-146**: `indicadores.meta` ahora editable en MirEditor; cambiar una meta existente exige justificación → audit trail en `revisiones_meta` **generalizada** (XOR meta_periodo_id/indicador_id por CHECK constraint). Import/snapshot exentos.
- Post-deploy: `migrate` + re-run `php artisan db:seed --class='Database\Seeders\Evaluation\EvaluacionExternaPermissionsSeeder'` (o Fase0).

## MV CREMA + Supuestos estructurados (sprint 2026-06-07, V2-A8/B8-B10)

- **`mir_supuestos`** reemplaza al texto libre `mir_niveles.supuestos`: descripción + 3 booleans de validez del temario (`es_externo/es_relevante/probabilidad_razonable`, C-075) + orden. Backfill idempotente en la migración (texto legacy → 1 supuesto con booleans false). **Columna legacy deprecada: sin lectores ni escritores** (drop en sprint futuro); todos los consumidores (Excel/PDF/snapshots/publishers DS-02/prompts IA/tab Cobertura/import) usan la relación `supuestosEstructurados()` o el accessor `MirNivel::supuestos_texto` (concatenado "; "). Snapshots restauran texto como supuesto estructurado (semántica backfill). CRUD en MirEditor (`agregar/guardar/eliminarSupuesto`) scoped al programa; badge UI Válido/Incompleto según `esValido()`.
- **`crema_validaciones_mv`** (C-072): checklist CREMA 1:1 del MV (Confiable/Relevante/Económico/Monitoreable/**Asequible**) — análoga a la CREMAA del indicador, con captura manual + botón "Validar CREMA" vía IA (prompt `prompts/mir/validar-crema-mv`).
- **`medios_verificacion.tipo_fuente`** (C-074): enum app-level `TipoFuenteMv` (`externa/administrativa_propia/evaluacion_externa`), NULL = legacy sin clasificar. **Regla B9 dura** (C-073): MV de FIN/PROPÓSITO exigen `tipo_fuente=externa` al guardar en MirEditor (`IndicadorReglasService::validarTipoFuenteMv`); NULL no bloquea pero genera `advertencia` en diagnóstico de import (latente, igual que B3-B7). Hallazgo B10 latente: supuestos importados sin atributos de validez.
- DS-07 (`pub_medios_verificacion`) gana columna `tipo_fuente`.
- Post-deploy: `migrate` (3 privadas) + `migrate --path=database/migrations/public --database=pgsql_public` (1 columna) + re-publicar DS-07 (`transparencia:sync-public DS-07`).
- Sprint paralelo en geobase: validaciones P-01..P-08 + folio evidencia (ver CLAUDE.md de geobase).

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
- DS-07 → `pub_medios_verificacion`
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
