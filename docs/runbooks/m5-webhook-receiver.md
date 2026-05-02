# Runbook — M5 Webhook Receiver (GeoBase → dte-spp)

## Qué hace y por qué existe

El receptor M5 acepta webhooks firmados con HMAC-SHA256 desde geobase ante eventos del padrón:

- `enrollment.status_changed` — cambio de estatus de un enrollment.
- `snapshot.generated` — snapshot trimestral creado en geobase.
- `sync.processed` — entry de sync offline procesado.

Es la única vía de coherencia eventual sin polling. Geobase reintenta entregas hasta 5 veces con backoff [60s, 300s, 900s, 1h, 24h] ante respuestas no-2xx.

**Spec del contrato:** `/home/eleacid/code/laravel/matrices de indicadores/integracion_geobase_dte.md` § Momento 5 (parcialmente desactualizada — el código y tests son la fuente de verdad).

**Diseño operativo del sprint:** `docs/plans/2026-05-02-m5-webhook-receiver-design.md` (gitignored).

**Ruta:** `POST /api/webhooks/geobase` (sin Sanctum; la firma HMAC es la autenticación).

**Tabla de auditoría:** `geobase_webhook_deliveries` con UNIQUE(`delivery_id`) — idempotente, prune automático a 90 días.

## Variables de entorno

En `.env` de **dte-spp**:

```
GEOBASE_WEBHOOK_SECRET=<32-byte-hex>          # mismo valor que en .env de geobase
GEOBASE_DELIVERY_RETENTION_DAYS=90            # opcional, default 90
```

En `.env` de **geobase**: el mismo `GEOBASE_WEBHOOK_SECRET`.

Generar nuevo secret: `openssl rand -hex 32`.

## Provisión en producción (one-time, idempotente)

1. Confirmar `GEOBASE_WEBHOOK_SECRET` con el mismo valor en `.env` de ambos repos.
2. Aplicar config: `php artisan config:clear && php artisan config:cache` en cada uno.
3. En el host de **geobase**:

```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan geobase:provision-webhook-subscription \
    --name=dte-spp-prod \
    --url=https://eleaciddev.cloud/api/webhooks/geobase \
    --events=enrollment.status_changed,snapshot.generated,sync.processed \
    --secret-env=GEOBASE_WEBHOOK_SECRET
```

Salida esperada:

```
✓ Webhook subscription "dte-spp-prod" creada (id=N)
✓ Eventos: enrollment.status_changed, snapshot.generated, sync.processed
✓ URL: https://eleaciddev.cloud/api/webhooks/geobase
✓ Activa: sí
```

4. Verificación cruzada:

```bash
# En geobase
php artisan tinker --execute="dump(\App\Models\WebhookSubscription::where('name','dte-spp-prod')->first()->toArray());"

# En dte-spp (después del primer evento real)
php artisan tinker --execute="dump(\App\Models\GeoBase\WebhookDelivery::latest('id')->limit(5)->get(['delivery_id','event_type','status_code','processed_at'])->toArray());"
```

## Rotación de secret

1. Generar nuevo secret: `openssl rand -hex 32`.
2. Setear en `.env` de **geobase** + `php artisan config:clear` + redeploy/restart workers.
3. Setear en `.env` de **dte-spp** + `php artisan config:clear` + redeploy.
4. Re-correr `geobase:provision-webhook-subscription` para que la fila de subscription pickee el nuevo secret.
5. **Ventana de inconsistencia:** tiempo de redeploy en ambos lados. Geobase reintenta hasta 24h con backoffs, así que pérdidas son recuperables si la ventana es < 24h.

## Investigar deliveries fallidos

```sql
-- En dte-spp (Postgres)
SELECT delivery_id, event_type, status_code, error_message, created_at, processed_at
FROM geobase_webhook_deliveries
WHERE status_code != 200 OR processed_at IS NULL
ORDER BY created_at DESC
LIMIT 50;

-- En geobase (Postgres)
SELECT id, event_type, status, response_code, response_body, attempt, next_retry_at
FROM webhook_deliveries
WHERE status != 'delivered'
ORDER BY created_at DESC
LIMIT 50;
```

**Cruce:** `geobase.webhook_deliveries.id` (entero) corresponde a `dte_spp.geobase_webhook_deliveries.delivery_id` (string del header `X-GeoBase-Delivery`).

**Códigos esperados:**
- `status_code=200`: procesado OK.
- `status_code=422`: payload inválido (campos faltantes); revisar `error_message` para los detalles del validator.
- `status_code=NULL` con `processed_at=NULL`: en flight (debería resolverse en segundos). Si persiste, listener cuelga o queue está saturada.

## Re-disparar manualmente desde geobase

Operación de emergencia (cuando un evento se perdió y geobase ya no tiene retry queue):

```php
// Tinker en geobase
$delivery = \App\Models\WebhookDelivery::find(<id>);
app(\App\Services\WebhookService::class)->retry($delivery);
```

O recrear el evento original desde el modelo afectado:

```php
// Tinker en geobase
$enrollment = \App\Models\Enrollment::find(<id>);
event(new \App\Events\EnrollmentStatusChanged($enrollment, $oldStatusEnum));
```

## Cómo deshabilitar temporalmente

**Preferido — desde geobase (quirúrgico, reversible):**

```sql
UPDATE webhook_subscriptions SET is_active = false WHERE name = 'dte-spp-prod';
```

Para reactivar: `is_active = true`.

**Último recurso — desde dte-spp** (comentar la ruta en `routes/api.php` y redeploy). Aplicable solo si geobase no responde a cambios de subscription.

## Métricas a vigilar (futuro, fuera de sprint)

- **Rate de 422** en `geobase_webhook_deliveries` → contratos rotos del emisor; coordinar con team de geobase.
- **Rate de 5xx** → bugs del receptor o queue saturada.
- **Latencia p95** de `processed_at - created_at` → degradación.
- **Crecimiento de filas con `processed_at IS NULL`** (deliveries en flight) → indicador de listeners colgados o DB bloqueada. El partial index `geobase_webhook_deliveries_in_flight_idx` está diseñado para que esta query sea barata.

## Comportamiento ante reintentos

Geobase usa el header `X-GeoBase-Delivery` como ID atómico de entrega. El receptor:

1. Verifica HMAC; si falla → 403 sin row en `geobase_webhook_deliveries`.
2. Busca delivery existente por `delivery_id`; si existe → 200 con `idempotent: true`, sin re-disparar Events ni re-loggear `activity_log`.
3. Persiste row in-flight con `status_code=NULL`, `processed_at=NULL`.
4. Valida payload; si inválido → 422 con row marcado `status_code=422`, `error_message=<json validator errors>`. Geobase puede reintentar 4xx según su política (verificar en `geobase.WebhookService::handleFailure`).
5. Loggea `activity_log` con `log_name='geobase-webhook'`, `description=<event_type>`.
6. Despacha Event interno (`EnrollmentStatusChanged` / `SnapshotGenerated` / `SyncProcessed`) si event_type es conocido.
7. Marca row `status_code=200`, `processed_at=now()`.

## Schedule de mantenimiento

`Schedule::command('model:prune', ['--model' => [WebhookDelivery::class]])` corre diario a `03:30` con `withoutOverlapping()`. Purga deliveries con `created_at < now() - GEOBASE_DELIVERY_RETENTION_DAYS` (default 90 días).

Verificar en producción: `php artisan schedule:list | grep prune-geobase` debe mostrar `30 3 * * *`.

## Brechas conocidas

- **Listener real para `sync.processed`**: hoy solo se loggea en `activity_log`; no hay acción downstream útil. Reabrir si en el futuro se decide invalidar caches o disparar reconciliación.
- **Eventos no manejados**: `enrollment.observed`, `beneficiary.created`, `beneficiary.relocated` (geobase los emite pero la subscription no los pide y el controller los acepta con 200 silencioso). Agregar handlers solo si negocio lo requiere.
- **Métricas estructuradas (Prometheus/StatsD)**: pendiente; hoy solo se ve por queries SQL.
- **Rate limiting**: no implementado; geobase mismo limita reintentos.
