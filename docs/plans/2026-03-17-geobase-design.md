# Diseño: GeoBase — Hub Geoespacial de la Secretaría

**Fecha:** 2026-03-17
**Estado:** Aprobado — pendiente de plan de implementación
**Reemplaza:** `docs/plans/2026-03-08-padron-beneficiarios-design.md` (renombrado de "Padrón Único de Beneficiarios" a "GeoBase")

---

## 1. Identidad del Proyecto

**GeoBase** es una aplicación Laravel independiente que actúa como hub geoespacial de la Secretaría de Desarrollo Económico. Sus sistemas satélite (Artesanos, PYMEs, Trámites, etc.) delegan a GeoBase tres responsabilidades:

1. **Identidad de beneficiarios** — registro único por CURP, deduplicación cruzada
2. **Validación geográfica** — PostGIS, capas INEGI/CONEVAL, poka-yoke espacial
3. **Evidencia auditable** — snapshots criptográficos para MIR y Cuenta Pública

Lo geográfico es el núcleo. La identidad es un medio para la inteligencia territorial, no el fin en sí mismo.

### ¿Por qué separado del sistema MIR?

1. **PII sensible** — CURP, domicilio, condición socioeconómica requieren políticas de seguridad distintas al sistema de indicadores (información pública).
2. **PostGIS** — geometrías, queries espaciales y capas INEGI son carga técnica que no debe compartir recursos con la MIR.
3. **Ciclo de vida independiente** — entregable al estado como aplicación autónoma cuando centralicen el padrón.

### Modelo hub-and-spoke

```
Sistema MIR (dte-spp)    ──┐
Sistema RRHH              ──┤
Sistema de Trámites       ──┤──→  GeoBase  ──→ (futuro: SIPPRES estatal)
Sistemas satélite UR      ──┘       ↑
                                API interna
                                autenticada
```

### Alcance

Solo la Secretaría de Desarrollo Económico. Si el estado quiere algo similar, que lo construya. GeoBase se diseña para ser entregable, no para ser adoptado como plataforma estatal.

---

## 2. Stack Técnico

| Capa | Tecnología | Justificación |
|------|-----------|---------------|
| Backend | Laravel 12, PHP 8.2+ | Consistencia con ecosistema interno (MIR) |
| Base de datos | PostgreSQL 16 + PostGIS | Geometrías, `ST_Contains`, `ST_Intersects`, queries espaciales |
| Frontend operativo | Livewire 3 + Leaflet.js + Alpine.js (TALL) | Mapas interactivos sin SPA |
| Caché | Redis | GeoJSON por zoom level, endpoints públicos con TTL |
| Almacenamiento | MinIO | Snapshots, comprobantes, evidencia oficial (no nube extranjera) |
| Analítica | Metabase (Docker) | Conectado a réplica de lectura, sin código nuevo por query |
| Auth inter-servicio | Laravel Sanctum (tokens) | API interna para sistemas de la secretaría |
| Auth operadores | Jetstream + Teams | Consistencia con MIR |

---

## 3. Arquitectura de Módulos

### Cuatro dominios internos

**Registry** — Registro de Identidad
- Modelo maestro `Beneficiary` (una fila por CURP, nunca se duplica)
- Modelo `Enrollment` (apoyo recibido — muchos por beneficiario)
- Cifrado PII, historial de cambios, deduplicación cruzada

**Geo** — Motor Geoespacial
- PostGIS: capas INEGI (estados, municipios, localidades, ZAP)
- Capas CONEVAL (pobreza municipal, marginación CONAPO)
- Validación `ST_Contains` — poka-yoke geográfico en inscripciones
- Pipeline de importación via Artisan commands

**Api** — Hub de Integración
- API interna (`/api/v1/geobase/*`) — Sanctum con scopes por sistema
- API pública (`/api/v1/datos-abiertos/*`) — agregados anonimizados, rate-limited
- Endpoint de snapshot criptográfico para cierre trimestral MIR
- Webhooks de notificación a satélites

**Analytics** — Inteligencia Territorial
- Metabase conectado a réplica de lectura
- Dashboards operador y directivo
- Exportaciones: GeoJSON, CSV anonimizado, reportes PDF

### Estructura de carpetas (Híbrida Laravel)

```
app/
├── Models/
│   ├── Beneficiary.php
│   ├── Enrollment.php
│   ├── Program.php
│   ├── Component.php
│   ├── ProgramGeography.php
│   ├── DuplicateReview.php
│   ├── BeneficiaryDataHistory.php
│   ├── EnrollmentStatusHistory.php
│   ├── DocumentoSoporte.php
│   ├── InegiLayer.php
│   ├── ConevalLayer.php
│   ├── MarginacionLayer.php
│   ├── Snapshot.php
│   ├── SyncQueue.php
│   ├── AuditLog.php
│   ├── SystemToken.php
│   ├── WebhookSubscription.php
│   └── WebhookDelivery.php
│
├── Services/
│   ├── BeneficiaryService.php
│   ├── EnrollmentService.php
│   ├── DuplicateDetectionService.php
│   ├── GeographicValidationService.php
│   ├── GeojsonBuilderService.php
│   ├── SnapshotService.php
│   ├── OpenDataExportService.php
│   ├── OfflineSyncService.php
│   └── PiiAuditService.php
│
├── Repositories/
│   ├── BeneficiaryRepository.php
│   └── EnrollmentRepository.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/V1/
│   │   │   ├── Padron/
│   │   │   │   ├── BeneficiaryController.php
│   │   │   │   ├── EnrollmentController.php
│   │   │   │   ├── ValidationController.php
│   │   │   │   ├── SnapshotController.php
│   │   │   │   └── ProgramController.php
│   │   │   └── DatosAbiertos/
│   │   │       ├── GeojsonController.php
│   │   │       └── CsvExportController.php
│   │   └── Web/
│   │       ├── DashboardController.php
│   │       ├── MapController.php
│   │       └── SyncController.php
│   ├── Requests/
│   │   ├── StoreBeneficiaryRequest.php
│   │   ├── StoreEnrollmentRequest.php
│   │   └── ValidateLocationRequest.php
│   ├── Resources/
│   │   ├── BeneficiaryResource.php
│   │   ├── EnrollmentResource.php
│   │   └── GeojsonResource.php
│   └── Middleware/
│       ├── AuditPiiAccess.php
│       ├── CheckScope.php
│       └── EnforceRateLimit.php
│
├── Events/
│   ├── BeneficiaryCreated.php
│   ├── BeneficiaryLocationUpdated.php
│   ├── EnrollmentApproved.php
│   └── SyncCompleted.php
│
├── Listeners/
│   ├── LogPiiAccess.php
│   └── ClearGeojsonCache.php
│
├── Jobs/
│   ├── RevaluateBeneficiaryEligibility.php
│   ├── ProcessOfflineSync.php
│   ├── ProcessWebhookDelivery.php
│   ├── ProcessWebhookRetry.php
│   ├── ExportOpenDataCsv.php
│   └── GenerateGeojsonCache.php
│
├── Policies/
│   ├── BeneficiaryPolicy.php
│   └── EnrollmentPolicy.php
│
└── Console/Commands/
    ├── ImportInegiLayer.php
    ├── ImportConevalLayer.php
    ├── ProcessSyncQueue.php
    ├── GenerateOpenDataExports.php
    ├── PurgeExpired.php
    └── BackupAppKey.php

database/seeders/
├── ProgramSeeder.php
├── ComponentSeeder.php
├── SystemTokenSeeder.php
└── WebhookEventSeeder.php

routes/
├── web/
│   ├── registry.php
│   ├── geo.php
│   └── analytics.php
├── api/
│   ├── geobase.php
│   └── datos-abiertos.php
└── console.php
```

### Decisión: ¿Por qué Híbrida Laravel y no DDD puro?

| Factor | Impacto | Decisión |
|--------|---------|----------|
| Consistencia con MIR | Alto | Mismo equipo, misma estructura — sin fricción cognitiva |
| Entregabilidad al estado | Alto | Equipo externo mantiene código Laravel estándar sin formación DDD |
| Complejidad del dominio | Medio | Un padrón con georeferencia, no bounded contexts múltiples |
| Rotación de personal | Medio | Cualquier dev Laravel entra al proyecto día uno |
| Timeline | Alto | Menos carpetas = menos decisiones de "¿dónde va esto?" |

---

## 4. Modelo de Datos

### Convención de nomenclatura

- Tablas de dominio: inglés (`beneficiaries`, `enrollments`, `programs`, `components`)
- Tablas de capas oficiales: español (`inegi_municipios`, `coneval_pobreza`) — coincide con shapefiles INEGI/CONEVAL
- Tablas de sistema: inglés (`audit_logs`, `sync_queue`, `webhook_subscriptions`)

### Tablas de Catálogos (Programs)

```sql
-- Programas registrados en GeoBase
CREATE TABLE programs (
    id SERIAL PRIMARY KEY,
    clave_programa VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    anio_fiscal SMALLINT NOT NULL,
    monto_asignado DECIMAL(15,2),
    status VARCHAR(20) NOT NULL DEFAULT 'activo',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Componentes por programa
CREATE TABLE components (
    id SERIAL PRIMARY KEY,
    program_id INT NOT NULL REFERENCES programs(id),
    clave_componente VARCHAR(50) NOT NULL,
    nombre VARCHAR(200) NOT NULL,
    tipo_apoyo VARCHAR(30) NOT NULL,              -- monetario | en_especie | servicio
    monto_unitario DECIMAL(15,2),
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP,
    UNIQUE(program_id, clave_componente)
);
```

### Tablas Core (Registry)

```sql
-- Identidad del beneficiario (una fila por CURP — nunca se duplica)
CREATE TABLE beneficiaries (
    id BIGSERIAL PRIMARY KEY,
    curp_rfc TEXT NOT NULL,                    -- encrypted, UNIQUE
    type VARCHAR(20) NOT NULL,                 -- persona_fisica | persona_moral
    nombre TEXT,                               -- encrypted
    apellidos TEXT,                            -- encrypted
    razon_social TEXT,                         -- encrypted, nullable (personas morales)
    beneficiary_catalog_key SMALLINT,          -- catálogo SIPPRES (claves 1-99)
    sex VARCHAR(1),
    gender VARCHAR(20),
    age_group VARCHAR(30),
    ethnicity VARCHAR(50),
    disability VARCHAR(100),
    socioeconomic_level VARCHAR(30),
    household_type VARCHAR(30),
    location GEOMETRY(POINT, 4326),            -- PostGIS — domicilio georreferenciado
    address_state VARCHAR(100),
    address_municipality VARCHAR(100),
    address_locality VARCHAR(100),
    address_street TEXT,                        -- encrypted
    activo BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP                       -- soft delete
);

-- Apoyo recibido (muchos por beneficiario)
CREATE TABLE enrollments (
    id BIGSERIAL PRIMARY KEY,
    beneficiary_id BIGINT NOT NULL REFERENCES beneficiaries(id),
    program_id INT NOT NULL REFERENCES programs(id),
    component_id INT REFERENCES components(id),
    system_origin VARCHAR(50) NOT NULL,        -- 'mir', 'artesanos', 'pymes'
    enrollment_date DATE NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'aprobado',
        -- aprobado | rechazado | observado | observado_por_cambio_domicilio
    monto_entregado DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP
);

-- Historial de cambios a datos maestros
CREATE TABLE beneficiary_data_history (
    id BIGSERIAL PRIMARY KEY,
    beneficiary_id BIGINT NOT NULL REFERENCES beneficiaries(id),
    field_changed VARCHAR(100) NOT NULL,
    old_value TEXT,                             -- encrypted
    new_value TEXT,                             -- encrypted
    supporting_doc VARCHAR(500),               -- URL en MinIO
    changed_by BIGINT,
    system_origin VARCHAR(50),
    changed_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Historial de estados de enrollment (para queries point-in-time)
CREATE TABLE enrollment_status_history (
    id BIGSERIAL PRIMARY KEY,
    enrollment_id BIGINT NOT NULL REFERENCES enrollments(id),
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    reason TEXT,
    changed_by BIGINT,
    changed_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Documentos de soporte
CREATE TABLE documentos_soporte (
    id BIGSERIAL PRIMARY KEY,
    beneficiary_id BIGINT NOT NULL REFERENCES beneficiaries(id),
    tipo_documento VARCHAR(30) NOT NULL,       -- INE | comprobante_domicilio | CURP | otro
    archivo_path VARCHAR(500) NOT NULL,        -- MinIO
    verificado BOOLEAN DEFAULT FALSE,
    verificado_por BIGINT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Revisión de duplicados
CREATE TABLE duplicate_reviews (
    id BIGSERIAL PRIMARY KEY,
    beneficiary_primary_id BIGINT NOT NULL REFERENCES beneficiaries(id),
    beneficiary_secondary_id BIGINT NOT NULL REFERENCES beneficiaries(id),
    similarity_score DECIMAL(3,2),             -- 0.00 a 1.00
    detection_method VARCHAR(30) NOT NULL,     -- curp_match | fuzzy_name | same_location
    status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        -- pendiente | fusionado | falso_positivo
    reviewed_by BIGINT,
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP
);
```

### Tablas Geoespaciales (Geo)

```sql
-- Extensión PostGIS
CREATE EXTENSION IF NOT EXISTS postgis;

-- Capas INEGI
CREATE TABLE inegi_estados (
    id BIGSERIAL PRIMARY KEY,
    clave VARCHAR(2) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    geometry GEOMETRY(MULTIPOLYGON, 4326),
    version VARCHAR(10),
    imported_at TIMESTAMP
);

CREATE TABLE inegi_municipios (
    id BIGSERIAL PRIMARY KEY,
    estado_id BIGINT NOT NULL REFERENCES inegi_estados(id),
    clave VARCHAR(5) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    geometry GEOMETRY(MULTIPOLYGON, 4326),
    version VARCHAR(10),
    imported_at TIMESTAMP
);

CREATE TABLE inegi_localidades (
    id BIGSERIAL PRIMARY KEY,
    municipio_id BIGINT NOT NULL REFERENCES inegi_municipios(id),
    clave VARCHAR(10) NOT NULL UNIQUE,
    nombre VARCHAR(150) NOT NULL,
    geometry GEOMETRY(POINT, 4326),            -- POINT para localidades pequeñas
    version VARCHAR(10),
    imported_at TIMESTAMP
);

CREATE TABLE zonas_atencion_prioritaria (
    id BIGSERIAL PRIMARY KEY,
    municipio_id BIGINT REFERENCES inegi_municipios(id),
    tipo VARCHAR(30),
    geometry GEOMETRY(MULTIPOLYGON, 4326),
    anio SMALLINT,
    fuente VARCHAR(100),
    imported_at TIMESTAMP
);

-- Capas CONEVAL
CREATE TABLE coneval_pobreza (
    id BIGSERIAL PRIMARY KEY,
    municipio_id BIGINT NOT NULL REFERENCES inegi_municipios(id),
    porcentaje_pobreza DECIMAL(5,2),
    anio SMALLINT NOT NULL,
    fuente VARCHAR(100)
);

CREATE TABLE marginacion_indices (
    id BIGSERIAL PRIMARY KEY,
    municipio_id BIGINT NOT NULL REFERENCES inegi_municipios(id),
    indice_marginacion DECIMAL(8,5),
    grado VARCHAR(20),                         -- muy_alto | alto | medio | bajo | muy_bajo
    anio SMALLINT NOT NULL,
    fuente VARCHAR(100)
);

-- Polígono elegible por programa/componente
CREATE TABLE program_geographies (
    id BIGSERIAL PRIMARY KEY,
    program_id INT NOT NULL REFERENCES programs(id),
    component_id INT REFERENCES components(id),
    poligono_elegibilidad GEOMETRY(MULTIPOLYGON, 4326),
    descripcion VARCHAR(255),
    vigencia_inicio DATE,
    vigencia_fin DATE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tablas de Integración (Api)

```sql
-- Snapshots criptográficos para MIR
CREATE TABLE snapshots (
    id BIGSERIAL PRIMARY KEY,
    component_id INT NOT NULL,
    program_id INT NOT NULL,
    period VARCHAR(10) NOT NULL,               -- '2026-Q1'
    cutoff_date TIMESTAMP NOT NULL,
    valor_oficial INT NOT NULL,
    snapshot_hash VARCHAR(64) NOT NULL,         -- SHA-256
    evidencia_url VARCHAR(500) NOT NULL,        -- MinIO
    generated_by VARCHAR(50) NOT NULL,          -- sistema que solicitó
    metadata JSONB,                             -- conteos desagregados, versión esquema
    created_at TIMESTAMP,
    UNIQUE(component_id, program_id, period)    -- idempotente
);

-- Subscripciones a webhooks
CREATE TABLE webhook_subscriptions (
    id BIGSERIAL PRIMARY KEY,
    system_name VARCHAR(50) NOT NULL,
    event_type VARCHAR(50) NOT NULL,           -- beneficiary_relocated | enrollment_observed
    callback_url VARCHAR(500) NOT NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Auditoría de acceso PII
CREATE TABLE audit_logs (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT,
    token_id BIGINT,
    system_origin VARCHAR(50),
    action VARCHAR(30) NOT NULL,               -- read | export | update | delete
    model_type VARCHAR(50) NOT NULL,           -- Beneficiary | Enrollment
    model_id BIGINT,
    ip_address INET,
    user_agent TEXT,
    query_snippet TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT NOW()
);

-- Archivo de auditoría (log rotation > 1 año)
CREATE TABLE audit_logs_archive (LIKE audit_logs INCLUDING ALL);

-- Registro de sistemas autorizados
CREATE TABLE system_tokens (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,          -- 'mir', 'artesanos', 'contraloria'
    scopes JSONB NOT NULL,                     -- ['padron:read', 'padron:snapshot']
    contact_email VARCHAR(100),
    last_used_at TIMESTAMP,
    expires_at TIMESTAMP,
    created_at TIMESTAMP
    -- El hash del token lo gestiona Sanctum en personal_access_tokens
);

-- Logs de webhooks enviados
CREATE TABLE webhook_deliveries (
    id BIGSERIAL PRIMARY KEY,
    subscription_id BIGINT NOT NULL REFERENCES webhook_subscriptions(id),
    payload JSONB NOT NULL,
    response_status INT,
    response_body TEXT,
    delivered_at TIMESTAMP,
    next_retry_at TIMESTAMP,
    attempts INT DEFAULT 1,
    status VARCHAR(20) NOT NULL DEFAULT 'pending'
        -- delivered | failed | pending_retry
);

-- Cola de sincronización offline
CREATE TABLE sync_queue (
    id BIGSERIAL PRIMARY KEY,
    device_id VARCHAR(100),
    operation VARCHAR(20) NOT NULL,            -- create_beneficiary | create_enrollment
    data JSONB NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pendiente',
        -- pendiente | sincronizado | fusionado | error
    existing_beneficiary_id BIGINT,            -- si se fusionó
    error_message TEXT,
    created_at TIMESTAMP,                      -- timestamp del dispositivo
    processed_at TIMESTAMP
);
```

### Índices de Performance

```sql
-- GIST obligatorios para PostGIS
CREATE INDEX idx_beneficiaries_location ON beneficiaries USING GIST (location);
CREATE INDEX idx_program_geographies_poligono ON program_geographies USING GIST (poligono_elegibilidad);
CREATE INDEX idx_inegi_municipios_geom ON inegi_municipios USING GIST (geometry);
CREATE INDEX idx_inegi_estados_geom ON inegi_estados USING GIST (geometry);
CREATE INDEX idx_zap_geom ON zonas_atencion_prioritaria USING GIST (geometry);

-- B-tree para queries frecuentes
CREATE INDEX idx_enrollments_beneficiary_program ON enrollments (beneficiary_id, program_id);
CREATE INDEX idx_enrollments_status ON enrollments (status) WHERE deleted_at IS NULL;
CREATE INDEX idx_enrollment_history_lookup ON enrollment_status_history (enrollment_id, changed_at DESC);
CREATE INDEX idx_duplicate_reviews_status ON duplicate_reviews (status) WHERE status = 'pendiente';
CREATE INDEX idx_sync_queue_pending ON sync_queue (status, created_at) WHERE status = 'pendiente';

-- Índices compuestos adicionales
CREATE INDEX idx_enrollments_program_date ON enrollments (program_id, enrollment_date DESC);
CREATE INDEX idx_enrollments_status_date ON enrollments (status, enrollment_date DESC);
CREATE INDEX idx_beneficiaries_municipality ON beneficiaries (address_municipality) WHERE activo = TRUE;
CREATE INDEX idx_audit_logs_model ON audit_logs (model_type, model_id, created_at DESC);
CREATE INDEX idx_audit_logs_user ON audit_logs (user_id, created_at DESC);
CREATE INDEX idx_webhook_deliveries_retry ON webhook_deliveries (status, next_retry_at) WHERE status = 'pending_retry';
```

---

## 5. Seguridad y Cumplimiento

### LGPDPPSO (Protección de Datos Personales)

**Campos cifrados en reposo** (Laravel `encrypted` cast):
- `curp_rfc`, `nombre`, `apellidos`, `razon_social`, `address_street`
- `beneficiary_data_history.old_value`, `beneficiary_data_history.new_value`

**Middleware `AuditPiiAccess`** — registra cada consulta a datos personales:
sistema solicitante, token, endpoint, timestamp, IP.

**Purga automática:** Command `geobase:purge-expired` ejecutado via scheduler.

**Respaldo de APP_KEY:** Command `geobase:backup-key`. El cifrado depende de `APP_KEY` — si se pierde, los datos cifrados son irrecuperables. Respaldo obligatorio en ubicación segura (vault, offline).

**Configuración completa:**

```php
// config/geobase.php
'retention' => [
    'beneficiary_inactive' => 2555,  // 7 años — beneficiario sin actividad
    'audit_logs' => 3650,            // 10 años — requerimiento fiscal
    'sync_queue' => 30,              // 30 días — cola temporal
],

'webhooks' => [
    'max_attempts' => 5,
    'retry_intervals' => [60, 300, 900, 3600, 86400], // 1min, 5min, 15min, 1h, 24h
    'timeout_seconds' => 30,
],

'cache' => [
    'geojson_prefix' => 'geojson_',
    'geojson_ttl' => 86400,          // 24 horas
    'stats_prefix' => 'stats_',
    'stats_ttl' => 3600,             // 1 hora
    'zoom_simplification' => [       // Simplificación de polígonos por zoom
        5 => 0.01,                   // Zoom bajo: simplificación agresiva
        10 => 0.005,
        15 => 0.001,                 // Zoom alto: máxima precisión
    ],
    'invalidation_events' => [
        'enrollment_created' => ['geojson', 'stats'],
        'beneficiary_relocated' => ['geojson'],
    ],
],
```

### Autenticación y Scopes (Sanctum)

```
mir:                padron:read, padron:snapshot
operadores-ur:      padron:register, padron:enroll, padron:read, padron:update-identity
sistema-tramites:   padron:validate
rrhh:               padron:validate
contraloria:        padron:read, padron:export
```

**Rate limiting:**
- API pública (`datos-abiertos`): 60 req/min por IP
- API interna (`geobase`): 200 req/min por token

### Integridad de Evidencia

- Snapshots con SHA-256 + almacenamiento en MinIO
- Endpoint idempotente: mismo `component_id + program_id + period` = mismo snapshot
- Soft deletes + `enrollment_status_history` para queries point-in-time
- Verificación de integridad: recalcular SHA-256 del archivo y comparar con hash almacenado

### Auditoría

- `spatie/laravel-activitylog` en modelo `Beneficiary` (trait `LogsActivity`)
- Log rotation: tabla `audit_logs_archive` para registros > 1 año
- Retención de archivos: 10 años (requerimiento fiscal)

---

## 6. APIs

### API Interna — `/api/v1/geobase/*` (Sanctum token requerido)

```
POST   /beneficiaries                  → Alta/upsert por CURP
GET    /beneficiaries/{id}             → Datos del beneficiario
PATCH  /beneficiaries/{id}             → Actualización con comprobante
POST   /enrollments                    → Registrar beneficio otorgado
GET    /enrollments?program_id=&status= → Listar enrollments
POST   /validate-location              → ST_Contains (poka-yoke geográfico)
POST   /validate-curp                  → Verificar duplicidad por CURP
POST   /snapshot                       → Corte de caja trimestral
GET    /programs/{id}/coverage         → Conteos para indicadores MIR
GET    /programs/{id}/geometry         → GeoJSON del polígono elegible
```

### API Pública — `/api/v1/datos-abiertos/*` (anónima, rate-limited, Redis cache)

```
GET    /geojson?programa=&anio=        → GeoJSON de zonas de cobertura (polígonos, no puntos)
GET    /csv?programa=&anio=            → CSV anonimizado (municipio, tipo, monto)
GET    /stats?programa=&anio=          → Estadísticas agregadas
```

Todos los endpoints públicos: Redis cache (TTL 24h), rate limiting (60/min), sin PII. GeoJSON simplificado por zoom level para reducir tamaño.

---

## 7. Flujos Operativos Clave

### Inscripción con validación geográfica

```
Operador abre formulario → Leaflet muestra polígono elegible
    → Operador coloca pin de domicilio
    → Alpine.js envía coordenadas al backend
    → PostGIS: ST_Contains(poligono_programa, punto_domicilio)
    → Falla: formulario no guarda, mensaje descriptivo
    → Éxito: inscripción procede, enrollment con status "aprobado"
```

### Captura offline (conectividad degradada)

```
Formulario funciona sin mapa (GPS del dispositivo o coordenadas manuales)
    → Datos entran a sync_queue con status "pendiente"
    → Al sincronizar: verificar CURP existente → merge o crear
    → Validación ST_Contains diferida → si falla: "pendiente_validacion_geografica"
    → Operador notificado de registros que no pasaron validación
```

### Detección de duplicados

```
Nivel 1: CURP normalizada coincide → 100% duplicado → merge automático
Nivel 2: Fuzzy match (similarity > 0.85) + proximidad (< 500m) → duplicate_review pendiente
    → Revisor manual decide: fusionar | falso positivo
```

### Cambio de domicilio (Efecto Dominó)

```
Operador ejecuta PATCH con comprobante PDF obligatorio
    → Padrón actualiza registro maestro
    → Guarda domicilio anterior en beneficiary_data_history
    → Encola job RevaluateBeneficiaryEligibility
    → Por cada enrollment con restricción geográfica:
        ST_Contains falla → status = "observado_por_cambio_domicilio"
    → Webhook a satélites afectados
```

### Corte de caja (Snapshot MIR)

```
Operador cierra trimestre en MIR
    → MIR: POST /api/v1/geobase/snapshot { component_id, period, cutoff_date }
    → GeoBase: query point-in-time → CSV → SHA-256 → MinIO
    → Respuesta: { valor_oficial, snapshot_hash, evidencia_url }
    → MIR guarda y congela — inmutable por ley
    → Idempotente: mismo request = mismo snapshot
```

---

## 8. Integración MIR ↔ GeoBase: Tres Momentos

### Momento 1 — Programación (Enlace Lógico)

El Planeador vincula un Componente MIR con su ID en GeoBase. Configura variables estandarizadas desde el Diccionario de Variables que apuntan a endpoints de GeoBase. No se intercambian datos de ciudadanos.

### Momento 2 — Seguimiento (Enlace Operativo)

La MIR consulta GeoBase en tiempo real. El campo de valor está bloqueado (read-only) — el Operador no puede escribir en él. Control anticorrupción arquitectónico. Al cerrar trimestre, se ejecuta el Corte de Caja (snapshot).

### Momento 3 — Evaluación (Medio de Verificación)

El auditor descarga el CSV del snapshot, recalcula SHA-256 y compara contra el hash en la MIR. Coincide = evidencia íntegra. No coincide = alerta de alteración.

---

## 9. Ecosistema de Sistemas Satélite (MDM)

### Contrato de integración

**Fase A — Alta y validación geográfica (tiempo real)**
```
Satélite → POST /api/v1/geobase/beneficiaries { curp, datos, coordenadas }
GeoBase  → ST_Contains + verificación CURP
         → CURP nueva:     { beneficiary_id, created: true }
         → CURP existente: { beneficiary_id, created: false, active_enrollments: [...] }
```

**Fase B — Proceso de negocio (interno al satélite)**
El satélite maneja su workflow. Único vínculo: `geobase_beneficiary_id` almacenado localmente. No replica PII.

**Fase C — Otorgamiento del beneficio**
```
Satélite → POST /api/v1/geobase/enrollments { beneficiary_id, component_id, monto }
GeoBase  → Crea enrollment con status "aprobado"
```

**Fase D — Corte de caja (trimestral)**
El satélite no hace nada. La MIR solicita el snapshot a GeoBase.

---

## 10. Decisiones Clave Consolidadas

| Decisión | Alternativa descartada | Razón |
|----------|----------------------|-------|
| App Laravel independiente | Módulo interno en MIR | PII requiere aislamiento; entregable al estado |
| Nombre "GeoBase" | "Padrón de Beneficiarios" | El hub geoespacial es el núcleo, no el registro |
| Estructura Híbrida Laravel | DDD puro | Consistencia con MIR, entregabilidad, menor curva |
| PostGIS completo | Solo claves INEGI de catálogo | Validación espacial y análisis de brechas lo requieren |
| DB réplica para Metabase | Conexión directa a DB principal | Queries analíticos pesados no compiten con operaciones |
| Dos APIs separadas | Una API con flags | Políticas de seguridad radicalmente distintas |
| Separación beneficiaries/enrollments | program_id en beneficiaries | Deduplicación imposible de otra forma |
| Captura offline con merge | Bloquear sin conexión | Operadores en zonas marginadas tienen conectividad limitada |
| Snapshot criptográfico | Consulta en tiempo real post-cierre | Inmutabilidad legal de la Cuenta Pública |
| MinIO para evidencia | AWS S3 | Documentos oficiales bajo control de la dependencia |
| Índices GIST obligatorios | Sin índices espaciales | Performance de ST_Contains en tiempo real |
| Rate limiting dual | Un solo rate limit | API pública vs interna tienen perfiles de uso distintos |
