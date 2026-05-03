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

## Pipeline N2-03 — Sync al portal público

`DatasetAbierto::publicar()` y `retirar()` disparan eventos que gatillan el job `SyncPublicDatasetJob`. El job invoca un Publisher concreto por `dataset_clave`:

- DS-01 → `pub_programas`
- DS-02 → `pub_mir_indicadores`
- DS-03 → `pub_avances_trimestrales`
- DS-04 → `pub_evaluaciones_anuales`
- DS-05 → `pub_alineacion_estrategica`
- DS-G04 → `pub_evolucion_temporal`
- (siempre, post-otro-publish/retire) → `pub_datasets_catalogo`
- DS-G01..G03 → pendiente sub-sprint **N2-03b** (depende de M1/M2/M3 GeoBase)
- DS-00 (políticas) y otras claves no mapeadas → loggea warning, termina sin error

Cada Publisher: DELETE+INSERT en transacción `pgsql_public` + cálculo de hash sha256 (excluye `id`/`created_at`/`updated_at` para idempotencia entre corridas).

Auditoría histórica en tabla `transparencia_publicaciones` (en `pgsql` privada): hash, count, success/error_message, user_id, action.

Recovery manual:

```bash
sail artisan transparencia:sync-public DS-01
```
