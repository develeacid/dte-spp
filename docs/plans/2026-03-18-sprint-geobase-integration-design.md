# Sprint: Integración MIR ↔ GeoBase — Diseño

> **Fecha:** 2026-03-18
> **Proyecto:** dte-spp-2026
> **Dependencia:** GeoBase Sprint 8 completado (hub operativo en localhost:8081)
> **Baseline tests:** 426 passed, 7 skipped

---

## Objetivo

Conectar la MIR con GeoBase para los 3 momentos de integración:
1. **Programación** — vincular componentes MIR con programas GeoBase
2. **Seguimiento** — consultar cobertura del padrón en tiempo real (valor read-only)
3. **Evaluación** — solicitar snapshots criptográficos como evidencia MIR

Además: recibir webhooks de GeoBase para actualización reactiva.

## Alcance

| Incluido | Excluido |
|----------|----------|
| GeoBaseClient HTTP service | CRUD de beneficiarios (lo hace GeoBase) |
| Config + env vars | UI de captura offline (lo hace GeoBase) |
| Migración: `geobase_program_id` en programas | Módulo presupuestal (sprint separado) |
| Webhook handler con HMAC verification | Reportes cruzados (requiere presupuestal) |
| Consulta de cobertura (Momento 2) | |
| Solicitud de snapshot (Momento 3) | |
| Tests de integración con HTTP fake | |

## Decisiones de diseño

### 1. GeoBaseClient como HTTP Service

Un service class que encapsula todas las llamadas HTTP a GeoBase. Usa `Http::` facade de Laravel con retry y timeout configurables. Se inyecta vía container.

### 2. Webhook handler en routes/api.php

Endpoint público `POST /api/webhooks/geobase` (sin auth Sanctum — usa HMAC verification en su lugar). Middleware propio `VerifyGeoBaseWebhook`.

### 3. Campo `geobase_program_id` nullable

Se agrega a `programa_presupuestarios`. Nullable porque no todos los programas tienen padrón. El Planeador lo configura en Momento 1.

### 4. Fake HTTP para tests

Los tests no llaman a GeoBase real. Usan `Http::fake()` con responses predefinidas. Esto permite TDD sin dependencia del servidor GeoBase.
