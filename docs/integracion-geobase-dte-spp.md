# Integracion GeoBase <-> dte-spp (Sistema MIR)

> **Documento espejo** -- este archivo es identico en ambos repositorios.
> Ultima actualizacion: 2026-03-19 (post PR #25 en dte-spp)

---

## 1. Vision General

**GeoBase** es el hub geoespacial de la Secretaria de Desarrollo Economico. Actua como
Master Data Management (MDM) de beneficiarios: identidad unica por CURP, georeferencia
con PostGIS, deduplicacion cruzada y evidencia auditable.

**dte-spp** es el sistema de Matriz de Indicadores para Resultados (MIR) que gestiona
el ciclo presupuestal basado en resultados (PbR): programas presupuestarios, indicadores,
metas, avances y medios de verificacion.

La relacion es **complementaria y unidireccional en datos sensibles**: GeoBase posee la
PII de beneficiarios; dte-spp consume agregados y snapshots, nunca datos personales
directamente.

```mermaid
flowchart LR
    subgraph Secretaria["Secretaria de Desarrollo Economico"]
        direction TB
        subgraph GeoBase["GeoBase (Hub Geoespacial)"]
            BEN[Padron de Beneficiarios]
            GEO[Motor PostGIS]
            SNAP[Snapshots Criptograficos]
            WEBHOOK[Webhooks]
        end

        subgraph DTE["dte-spp (Sistema MIR)"]
            MIR[Matriz de Indicadores]
            AVANCE[Captura de Avances]
            EVIDENCIA[Medios de Verificacion]
            REPORTE[Cuenta Publica]
        end

        subgraph Satelites["Sistemas Satelite"]
            ART[Artesanos]
            PYMES[PYMEs]
            TRAM[Tramites]
        end
    end

    Satelites -->|POST beneficiarios| GeoBase
    DTE -->|GET agregados| GeoBase
    DTE -->|POST snapshots| GeoBase
    GeoBase -->|Webhooks| DTE
    GeoBase -.->|Datos Abiertos| EXT[API Publica]
```

---

## 2. Fundamento Normativo: El Padron como Eje del PbR

> Esta seccion traduce la normatividad del Presupuesto Basado en Resultados (PbR)
> a la arquitectura de software de GeoBase y dte-spp.

### 2.1 Que es un Padron de Beneficiarios

Un **padron de beneficiarios** es la relacion oficial de personas que reciben los beneficios
de un programa gubernamental. Es obligatorio por normatividad, se conforma cada ejercicio
fiscal y es la base para medir si el gasto publico llego a quien debia llegar.

### 2.2 Vinculacion Padron-MIR (5 puntos estructurales)

El padron se vincula con la MIR en cada nivel de su estructura:

| Vinculacion | Nivel MIR | Traduccion en la arquitectura |
|-------------|-----------|-------------------------------|
| **Como Actividad** | Actividad | `POST /beneficiaries` + captura offline = la operacion diaria de administrar el padron |
| **Registro obligatorio en Componentes** | Componente | Separacion `beneficiaries` (identidad) / `enrollments` (transaccion). Endpoint `GET /components/{id}/coverage` |
| **Medio de Verificacion** | Columna 3 | Snapshots criptograficos: CSV + SHA-256 + MinIO. Momento 3 de integracion |
| **Variables de Indicadores Estrategicos** | Fin / Proposito | `COUNT(DISTINCT beneficiary_id)` via `GET /programs/{id}/coverage`. Deduplicacion CURP |
| **Supuestos (factor de riesgo)** | Columna 4 | Poka-yoke geografico `ST_Contains` + comprobante de domicilio. El software verifica el supuesto |

### 2.3 Desagregacion Obligatoria (Anexo 11)

La normativa exige desagregar el padron en variables obligatorias para la Cuenta Publica:

| Variable | Requisito normativo | Implementacion en GeoBase |
|----------|--------------------|-----------------------------|
| **Sexo** | Mujeres / Hombres | `Genero` enum ✅ (masculino, femenino, otro) |
| **Grupo de edad** | Infantes 0-5, Ninios 6-12, Adolescentes 13-17, Jovenes 18-29, Adultos 30-64, Adultos mayores 65+ | `AgeGroupEnum` ❌ (pendiente; se calculara desde `fecha_nacimiento`) |
| **Etnia** | Indigenas / No indigenas | `EthnicityEnum` ❌ (pendiente) |
| **Discapacidad** | Personas con discapacidad | Campo booleano ❌ (pendiente) |

Estos campos son prerequisito para:
- Generar el Anexo 11 "Informacion de la Poblacion Atendida"
- Calcular indicadores estrategicos con perspectiva de genero
- Cumplir con la Agenda 2030 (ODS) en desagregacion estadistica

### 2.4 Clasificacion por Tipo de Indicador

| Tipo | Niveles | Que mide | Endpoint GeoBase |
|------|---------|----------|------------------|
| **Estrategico** | Fin, Proposito, Componentes directos | Impacto real, cobertura, deduplicacion | `GET /programs/{id}/coverage` con `COUNT(DISTINCT)` |
| **De Gestion** | Actividades, Componentes indirectos | Eficiencia operativa del padron | Metricas internas: duplicados resueltos, rechazos geograficos, tiempos de proceso |

### 2.5 Ciclo de Seguimiento MIR traducido a Software

| Etapa normativa | Cuando | En dte-spp | En GeoBase |
|-----------------|--------|------------|------------|
| Captura de metas planeadas | Q1 | Planeador configura `indicador_variables` con `geobase_endpoint_type` | Carga padron base y capas INEGI |
| Registro de avances | Q1-Q4 | Operador usa boton "Sincronizar" en CapturaAvance | API responde con agregados via `GeoBaseClient` |
| Semaforizacion | Cada periodo | `FormulaEvaluatorService` + `SemaforoService` calculan automaticamente | N/A (solo provee datos) |
| Actualizacion de MIR | Cuando cambian ROP | `MirSnapshotService` crea nueva version JSONB | N/A |
| Cierre fiscal | Q4 | Genera Cuenta Publica PDF; almacena hash de snapshot | Emite snapshot final CSV + SHA-256 + MinIO |

### 2.6 Cierre de Ejercicio Fiscal

Al cerrar el anio:

- **dte-spp** congela la MIR (CapturaAvance deshabilita inputs), genera reportes PDF
  definitivos y entrega la Cuenta Publica al Congreso y Auditoria Superior.
- **GeoBase** ejecuta el snapshot final (`POST /snapshots/generate` con `period: 2026-Q4`),
  consolida la Poblacion Atendida (`enrollments WHERE status = 'aprobado'`) y expone
  datos anonimizados via API de Datos Abiertos para transparencia.

La MIR demuestra **que se logro**. El padron demuestra **a quienes se les entrego**.
Juntos cierran el ciclo del PbR.

---

## 3. Ciclo Presupuestal (PbR)

El ciclo anual de Presupuesto basado en Resultados conecta ambos sistemas en cuatro
fases. GeoBase alimenta los numeradores y la evidencia; dte-spp gestiona las metas
y los reportes.

```mermaid
gantt
    title Ciclo Presupuestal Anual (PbR)
    dateFormat YYYY-MM
    axisFormat %b

    section dte-spp (MIR)
    Captura metas planeadas           :mir1, 2026-01, 3M
    Config variables con GeoBase      :mir2, 2026-01, 2M
    Monitoreo trimestral Q1           :mir3, 2026-04, 1M
    Monitoreo trimestral Q2           :mir4, 2026-07, 1M
    Monitoreo trimestral Q3           :mir5, 2026-10, 1M
    Cierre fiscal y Cuenta Publica    :mir6, 2026-12, 1M

    section GeoBase (Padron)
    Registro continuo de beneficiarios :geo1, 2026-01, 12M
    Validacion geografica en campo     :geo2, 2026-03, 9M
    Snapshot Q1                        :snap1, 2026-04, 0.5M
    Snapshot Q2                        :snap2, 2026-07, 0.5M
    Snapshot Q3                        :snap3, 2026-10, 0.5M
    Snapshot Q4 (cierre)               :snap4, 2026-12, 0.5M

    section Sistemas Satelite
    Operadores en campo alimentan padron :sat1, 2026-03, 9M
```

### Detalle por trimestre

```mermaid
flowchart TD
    subgraph Q1["Q1: Planeacion"]
        M1[dte-spp: Captura metas planeadas]
        M2[dte-spp: Configura indicador_variables]
        M3[GeoBase: Carga padron base]
        M1 --> M2
        M2 -->|geobase_endpoint_type| M3
    end

    subgraph Q2Q3["Q2-Q3: Ejecucion y Monitoreo"]
        E1[Satelites: Operadores registran beneficiarios]
        E2[GeoBase: Valida ST_Contains]
        E3[GeoBase: Deduplicacion CURP]
        E4[dte-spp: CapturaAvance on-demand]
        E5[GeoBase: Responde con agregados]
        E1 --> E2 --> E3
        E4 --> E5
    end

    subgraph Q4["Q4: Cierre Fiscal"]
        C1[dte-spp: Solicita snapshot]
        C2[GeoBase: Genera CSV + SHA-256]
        C3[GeoBase: Almacena en MinIO]
        C4[dte-spp: Registra hash como evidencia]
        C5[dte-spp: Genera Cuenta Publica]
        C1 --> C2 --> C3 --> C4 --> C5
    end

    Q1 --> Q2Q3 --> Q4
```

---

## 4. Arquitectura de Integracion

### Endpoints y flujos de datos

```mermaid
flowchart LR
    subgraph Satelites["Sistemas Satelite"]
        SAT[Artesanos / PYMEs / Tramites]
    end

    subgraph GB["GeoBase"]
        API_INT["API Interna<br/>/api/v1/geobase/*"]
        API_PUB["API Publica<br/>/api/v1/datos-abiertos/*"]
        WH[Webhook Engine]
    end

    subgraph DTE["dte-spp"]
        CLIENT[GeoBaseClient]
        WH_HANDLER[Webhook Handler]
    end

    subgraph EXT["Publico"]
        DATOS[Datos Abiertos]
    end

    SAT -->|"POST /beneficiaries<br/>(upsert CURP)"| API_INT
    SAT -->|"POST /enrollments<br/>(registrar apoyo)"| API_INT

    CLIENT -->|"GET /components/{id}/coverage<br/>(numeradores)"| API_INT
    CLIENT -->|"GET /programs/{id}/coverage<br/>(cobertura)"| API_INT
    CLIENT -->|"POST /snapshots/generate<br/>(evidencia SHA-256)"| API_INT
    CLIENT -->|"GET /snapshots/{id}/verify<br/>(verificar integridad)"| API_INT

    WH -->|"enrollment.observed<br/>beneficiary.relocated"| WH_HANDLER

    API_PUB -->|"GET /geojson/*<br/>GET /csv/*"| DATOS
```

### Secuencia: Sincronizacion de Avance MIR

```mermaid
sequenceDiagram
    participant Op as Operador MIR
    participant DTE as dte-spp
    participant Client as GeoBaseClient
    participant GB as GeoBase API
    participant DB as PostgreSQL + PostGIS

    Op->>DTE: Click "Sincronizar con GeoBase"
    DTE->>Client: syncVariable(indicador_variable)
    Client->>GB: GET /api/v1/geobase/components/{id}/coverage<br/>?period=2026-Q2&filters={...}

    GB->>DB: SELECT COUNT(DISTINCT beneficiary_id)<br/>FROM enrollments<br/>WHERE component_id = ? AND period = ?
    DB-->>GB: { count: 847 }
    GB-->>Client: 200 { value: 847, cutoff: "2026-06-30" }
    Client-->>DTE: Actualiza avance.valor_actual = 847
    DTE-->>Op: Avance sincronizado
```

### Secuencia: Generacion de Snapshot

```mermaid
sequenceDiagram
    participant DTE as dte-spp
    participant GB as GeoBase API
    participant Job as GenerateSnapshotJob
    participant MinIO as MinIO Storage
    participant WH as Webhook

    DTE->>GB: POST /api/v1/geobase/snapshots/generate<br/>{ component_id, program_id, period }
    GB->>Job: Dispatch (async)
    GB-->>DTE: 202 { snapshot_id, status: "processing" }

    Job->>Job: Query enrollments + beneficiaries
    Job->>Job: Generate CSV
    Job->>Job: Compute SHA-256 hash
    Job->>MinIO: Store CSV file
    Job->>Job: Save snapshot record

    Job->>WH: Dispatch snapshot.generated event
    WH->>DTE: POST /webhooks/geobase<br/>{ event: "snapshot.generated", snapshot_id, hash }

    DTE->>GB: GET /api/v1/geobase/snapshots/{id}/verify
    GB-->>DTE: 200 { valid: true, hash: "abc123...", url: "..." }
```

---

## 5. Los 5 Momentos de Integracion

### Momento 1: Variables de Calculo

La tabla `indicador_variables` en dte-spp conecta cada variable de calculo de un
indicador MIR con un endpoint de GeoBase.

| Campo en `indicador_variables` | Tipo | Descripcion |
|-------------------------------|------|-------------|
| `geobase_endpoint_type` | `enum` | `component_coverage`, `program_coverage`, `territorial_report` |
| `geobase_reference_id` | `int` | ID del componente o programa en GeoBase |
| `geobase_filter_params` | `json` | Filtros adicionales: `{ "status": "aprobado", "gender": "femenino" }` |
| `geobase_value_key` | `string` | Campo del response a extraer: `count`, `amount`, `coverage_pct` |

**Flujo:**

```mermaid
flowchart LR
    IV["indicador_variables<br/>(dte-spp)"] -->|geobase_endpoint_type<br/>geobase_reference_id| EP["Endpoint GeoBase"]
    EP -->|response[geobase_value_key]| AV["avances.valor_actual<br/>(dte-spp)"]

    style IV fill:#4a90d9,color:#fff
    style EP fill:#50c878,color:#fff
    style AV fill:#4a90d9,color:#fff
```

| Estado | Sistema |
|--------|---------|
| Columnas `geobase_*` en `indicador_variables` | dte-spp ❌ (migracion pendiente) |
| `geobase_program_id` en `programa_presupuestarios` | dte-spp ✅ (PR #25) |
| `GeoBaseClient` service | dte-spp ✅ (PR #25) |
| Sincronizacion CapturaAvance (boton UI) | dte-spp ❌ |
| Endpoints coverage | GeoBase ✅ |

---

### Momento 2: Cobertura y Deduplicacion

Los indicadores de nivel estrategico (Fin/Proposito) requieren conteo de beneficiarios
unicos. GeoBase garantiza deduplicacion por CURP y validacion espacial con PostGIS.

```mermaid
flowchart TD
    subgraph GeoBase
        BEN["beneficiaries<br/>(CURP unico)"]
        ENR["enrollments<br/>(N por beneficiario)"]
        PG["program_geographies<br/>(poligonos elegibles)"]

        ENR -->|beneficiary_id| BEN
        ENR -->|ST_Contains| PG
    end

    subgraph DTE["dte-spp"]
        IND["indicadores<br/>nivel: Fin / Proposito"]
        IND -->|"GET /programs/{id}/coverage"| GeoBase
    end

    GeoBase -->|"COUNT(DISTINCT beneficiary_id)"| DTE
```

**Query subyacente en GeoBase:**

```sql
SELECT COUNT(DISTINCT e.beneficiary_id) AS cobertura
FROM enrollments e
JOIN beneficiaries b ON b.id = e.beneficiary_id
WHERE e.program_id = :program_id
  AND e.status = 'aprobado'
  AND e.enrollment_date BETWEEN :fecha_inicio AND :fecha_fin
  AND b.activo = true;
```

| Estado | Sistema |
|--------|---------|
| Endpoints coverage | GeoBase ✅ |
| Deduplicacion CURP | GeoBase ✅ |
| Validacion PostGIS | GeoBase ✅ |
| `GeoBaseClient.getProgramCoverage()` | dte-spp ✅ (PR #25) |
| Listener `UpdateAvanceFromEnrollment` | dte-spp ✅ (PR #25) |

---

### Momento 3: Snapshots Criptograficos (Medios de Verificacion)

Cada trimestre, dte-spp solicita un snapshot a GeoBase. El snapshot congela el estado
del padron en un momento dado y produce evidencia inmutable.

```mermaid
flowchart LR
    REQ["POST /snapshots/generate<br/>{ component_id, period }"]
    CSV["CSV con registros<br/>(anonimizado)"]
    HASH["SHA-256 hash"]
    MINIO["MinIO storage"]
    SNAP["snapshots table<br/>valor_oficial + hash"]
    MIR["dte-spp<br/>medios_verificacion"]

    REQ --> CSV --> HASH --> MINIO
    HASH --> SNAP
    SNAP -->|webhook| MIR
```

**Tabla `snapshots` en GeoBase:**

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `component_id` | `int` | Componente del programa |
| `program_id` | `int` | Programa presupuestario |
| `period` | `varchar(10)` | Ejemplo: `2026-Q1` |
| `cutoff_date` | `timestamp` | Fecha de corte |
| `valor_oficial` | `int` | Conteo oficial congelado |
| `snapshot_hash` | `varchar(64)` | SHA-256 del CSV |
| `evidencia_url` | `varchar(500)` | Ruta en MinIO |
| `generated_by` | `varchar(50)` | Sistema que solicito |
| `metadata` | `jsonb` | Desagregados, version |

**Constraint de idempotencia:** `UNIQUE(component_id, program_id, period)` -- un solo
snapshot por componente/programa/periodo.

| Estado | Sistema |
|--------|---------|
| POST /snapshots/generate | GeoBase ✅ |
| GET /snapshots/{id}/verify | GeoBase ✅ |
| GenerateSnapshotJob | GeoBase ✅ |
| SnapshotService | GeoBase ✅ |
| Almacenamiento MinIO | GeoBase ✅ |
| Consumo y almacenamiento de hash | dte-spp ❌ |

---

### Momento 4: Poka-yoke Geografico (Supuestos MIR)

Los supuestos de la MIR asumen que los beneficiarios estan dentro del area de cobertura
del programa. GeoBase valida esto automaticamente al momento de inscripcion.

```mermaid
flowchart TD
    SAT["Sistema Satelite<br/>POST /enrollments"]
    GVS["GeographicValidationService"]
    PG["program_geographies<br/>(poligono elegible)"]
    BEN["beneficiary.location<br/>(POINT)"]

    SAT --> GVS
    GVS -->|"ST_Contains(poligono, punto)"| PG
    GVS -->|lee ubicacion| BEN

    GVS -->|true| OK["Enrollment aprobado ✅"]
    GVS -->|false| REJECT["Enrollment observado ⚠️<br/>status: observado_por_cambio_domicilio"]
```

**Validacion PostGIS:**

```sql
SELECT EXISTS (
    SELECT 1 FROM program_geographies pg
    WHERE pg.program_id = :program_id
      AND (pg.component_id = :component_id OR pg.component_id IS NULL)
      AND ST_Contains(pg.poligono_elegibilidad, :beneficiary_location)
      AND (pg.vigencia_fin IS NULL OR pg.vigencia_fin >= CURRENT_DATE)
) AS dentro_de_cobertura;
```

| Estado | Sistema |
|--------|---------|
| GeographicValidationService | GeoBase ✅ |
| program_geographies con PostGIS | GeoBase ✅ |
| Webhook `enrollment.observed` | GeoBase ✅ |
| `WebhookController` + verificacion HMAC | dte-spp ✅ (PR #25) |
| Middleware `VerifyGeoBaseWebhook` | dte-spp ✅ (PR #25) |

---

### Momento 5: Desagregacion Demografica (Anexo 11)

La Cuenta Publica exige desagregacion por genero, grupo de edad, etnia y tipo de
beneficiario. GeoBase almacena estos campos en `beneficiaries` y los incluye en
los snapshots.

```mermaid
flowchart LR
    subgraph GeoBase["Enums en GeoBase"]
        G["Genero<br/>MASCULINO | FEMENINO | OTRO"]
        BT["BeneficiaryType<br/>PERSONA_FISICA | PERSONA_MORAL"]
        AG["AgeGroupEnum<br/>❌ NO IMPLEMENTADO"]
        ET["EthnicityEnum<br/>❌ NO IMPLEMENTADO"]
    end

    subgraph Snap["Snapshot metadata (JSONB)"]
        DES["desagregados: {<br/>  por_genero: {...},<br/>  por_tipo: {...},<br/>  por_grupo_edad: {...},<br/>  por_etnia: {...}<br/>}"]
    end

    GeoBase --> Snap
    Snap -->|webhook / GET| DTE["dte-spp<br/>Anexo 11"]
```

| Estado | Sistema |
|--------|---------|
| `Genero` enum | GeoBase ✅ |
| `BeneficiaryType` enum | GeoBase ✅ |
| `AgeGroupEnum` | GeoBase ❌ |
| `EthnicityEnum` | GeoBase ❌ |
| Desagregados en snapshot metadata | GeoBase ⚠️ (parcial) |
| Consumo Anexo 11 | dte-spp ❌ |

---

## 6. Estado Actual de Implementacion

### GeoBase

| Componente | Estado | Notas |
|-----------|--------|-------|
| API Interna (Sanctum) | ✅ | Scopes configurados |
| POST /beneficiaries (upsert CURP) | ✅ | Con deduplicacion |
| POST /enrollments | ✅ | Con validacion geografica |
| GET /components/{id}/coverage | ✅ | Numeradores por componente |
| GET /programs/{id}/coverage | ✅ | Cobertura por programa |
| POST /snapshots/generate | ✅ | Async via GenerateSnapshotJob |
| GET /snapshots/{id}/verify | ✅ | Verificacion SHA-256 |
| GeographicValidationService | ✅ | ST_Contains poka-yoke |
| SnapshotService | ✅ | CSV + hash + MinIO |
| Webhook engine | ✅ | Con reintentos |
| Enum `Genero` | ✅ | MASCULINO, FEMENINO, OTRO |
| Enum `BeneficiaryType` | ✅ | PERSONA_FISICA, PERSONA_MORAL |
| `vw_reporte_territorial` | ❌ | Vista materializada pendiente |
| `AgeGroupEnum` | ❌ | Requerido para Anexo 11 |
| `EthnicityEnum` | ❌ | Requerido para Anexo 11 |
| API Datos Abiertos | ⚠️ | GeoJSON listo, CSV parcial |

### dte-spp

| Componente | Estado | Notas |
|-----------|--------|-------|
| `GeoBaseClient` service class | ✅ | PR #25 — HTTP + Sanctum + retry + 8 metodos |
| `WebhookController` + ruta POST | ✅ | PR #25 — HMAC-SHA256 verification |
| Middleware `VerifyGeoBaseWebhook` | ✅ | PR #25 — Valida `X-GeoBase-Signature` |
| Eventos: `EnrollmentStatusChanged`, `SnapshotGenerated`, `SyncProcessed` | ✅ | PR #25 |
| Listener `UpdateAvanceFromEnrollment` | ✅ | PR #25 — Refresca cobertura automaticamente |
| `geobase_program_id` en `programa_presupuestarios` | ✅ | PR #25 — Migracion + indice |
| `ProgramaPresupuestario::getGeoBaseCoverage()` | ✅ | PR #25 |
| Config `services.geobase` (url, token, timeout, retry) | ✅ | PR #25 |
| Tests unitarios + integracion + webhook | ✅ | PR #25 — 3 suites de tests |
| Variables `.env` (`GEOBASE_API_URL`, `TOKEN`, `WEBHOOK_SECRET`) | ✅ | Configuradas |
| Columnas `geobase_*` en `indicador_variables` | ❌ | Migracion pendiente |
| Boton "Sincronizar" en CapturaAvance | ❌ | UI Livewire pendiente |
| Almacenamiento de snapshot hash | ❌ | Logica para poblar `hash_archivo` |
| Consumo de desagregados Anexo 11 | ❌ | Depende de GeoBase enums |

---

## 7. Modelo de Datos Compartido

```mermaid
erDiagram
    %% === GeoBase ===

    programs {
        int id PK
        varchar clave_programa UK
        varchar nombre
        smallint anio_fiscal
        decimal monto_asignado
    }

    components {
        int id PK
        int program_id FK
        varchar clave_componente
        varchar tipo_apoyo
        decimal monto_unitario
    }

    beneficiaries {
        bigint id PK
        text curp_rfc "encrypted, unique"
        varchar type "persona_fisica | persona_moral"
        varchar gender
        geometry location "POINT 4326"
        boolean activo
    }

    enrollments {
        bigint id PK
        bigint beneficiary_id FK
        int program_id FK
        int component_id FK
        varchar system_origin
        date enrollment_date
        varchar status
    }

    program_geographies {
        bigint id PK
        int program_id FK
        int component_id FK
        geometry poligono_elegibilidad "MULTIPOLYGON 4326"
        date vigencia_inicio
        date vigencia_fin
    }

    snapshots {
        bigint id PK
        int component_id FK
        int program_id FK
        varchar period
        int valor_oficial
        varchar snapshot_hash "SHA-256"
        varchar evidencia_url "MinIO"
        jsonb metadata
    }

    programs ||--o{ components : "tiene"
    programs ||--o{ enrollments : "registra"
    programs ||--o{ program_geographies : "define area"
    programs ||--o{ snapshots : "genera"
    components ||--o{ enrollments : "detalla"
    components ||--o{ snapshots : "congela"
    beneficiaries ||--o{ enrollments : "recibe"

    %% === dte-spp ===

    programa_presupuestarios {
        int id PK
        int geobase_program_id FK "FK logica a GeoBase"
        varchar clave
        varchar nombre
        int ejercicio_fiscal
    }

    indicadores {
        int id PK
        int programa_presupuestario_id FK
        varchar nivel "Fin | Proposito | Componente | Actividad"
        varchar nombre_indicador
        varchar metodo_calculo
    }

    indicador_variables {
        int id PK
        int indicador_id FK
        varchar nombre
        varchar geobase_endpoint_type "component_coverage | program_coverage"
        int geobase_reference_id "ID en GeoBase"
        json geobase_filter_params "filtros opcionales"
        varchar geobase_value_key "count | amount | coverage_pct"
    }

    avances {
        int id PK
        int indicador_id FK
        varchar periodo "2026-Q1"
        decimal valor_planeado
        decimal valor_actual
        varchar fuente "manual | geobase_sync"
    }

    programa_presupuestarios ||--o{ indicadores : "mide"
    indicadores ||--o{ indicador_variables : "calcula con"
    indicadores ||--o{ avances : "reporta"
```

**Relacion clave:** `programa_presupuestarios.geobase_program_id` apunta al `programs.id`
de GeoBase. Esta es una FK logica (no existe constraint fisico entre bases de datos).

---

## 8. Flujo de Datos por Nivel MIR

Cada nivel de la MIR consume datos de GeoBase de forma distinta:

```mermaid
flowchart TD
    subgraph MIR["Niveles MIR"]
        FIN["Fin<br/>(Impacto)"]
        PROP["Proposito<br/>(Resultado)"]
        COMP["Componente<br/>(Producto)"]
        ACT["Actividad<br/>(Proceso)"]
    end

    subgraph GeoBase["Datos GeoBase"]
        COV_P["GET /programs/{id}/coverage<br/>COUNT(DISTINCT beneficiary_id)"]
        COV_C["GET /components/{id}/coverage<br/>COUNT enrollments por componente"]
        OPS["Metricas operativas<br/>(conteos internos)"]
    end

    FIN -->|"Cobertura unica<br/>deduplicada"| COV_P
    PROP -->|"Cobertura unica<br/>deduplicada"| COV_P
    COMP -->|"Entregas por<br/>componente"| COV_C
    ACT -->|"Registros, validaciones,<br/>deduplicaciones"| OPS
```

### Detalle por nivel

| Nivel MIR | Tipo de indicador | Endpoint GeoBase | Ejemplo |
|-----------|------------------|------------------|---------|
| **Fin** | Cobertura poblacional | `GET /programs/{id}/coverage` | "% de artesanos atendidos vs padron estatal" |
| **Proposito** | Beneficiarios unicos | `GET /programs/{id}/coverage` | "Numero de beneficiarios unicos del programa" |
| **Componente** | Entregas/apoyos | `GET /components/{id}/coverage` | "Kits entregados en Q2" |
| **Actividad** | Operativos | Metricas internas | "Solicitudes validadas geograficamente" |

---

## 9. Seguridad y Autenticacion

### Tokens Sanctum con Scopes

```mermaid
flowchart TD
    subgraph Scopes["Scopes de API"]
        PR["padron:read<br/>Consultar beneficiarios y agregados"]
        REG["padron:register<br/>Crear/actualizar beneficiarios y enrollments"]
        SS["padron:snapshot<br/>Generar y verificar snapshots"]
    end

    subgraph Sistemas["Tokens por Sistema"]
        MIR_T["Token: mir<br/>padron:read + padron:snapshot"]
        ART_T["Token: artesanos<br/>padron:read + padron:register"]
        CONT_T["Token: contraloria<br/>padron:read"]
    end

    MIR_T --> PR
    MIR_T --> SS
    ART_T --> PR
    ART_T --> REG
    CONT_T --> PR
```

### Capas de seguridad

| Capa | Mecanismo | Ubicacion |
|------|-----------|-----------|
| **Autenticacion** | Laravel Sanctum (Bearer token) | Middleware `auth:sanctum` |
| **Autorizacion** | Scopes por token | Middleware `CheckScope` |
| **Cifrado PII** | Encrypt/decrypt en modelo | `Beneficiary` (curp_rfc, nombre, apellidos, address_street) |
| **Auditoria** | Log de acceso a PII | Middleware `AuditPiiAccess` -> tabla `audit_logs` |
| **Rate limiting** | Throttle por token | Middleware `EnforceRateLimit` |
| **Webhooks** | HMAC-SHA256 signature | Header `X-GeoBase-Signature` |
| **Datos Abiertos** | Anonimizacion obligatoria | Agregados sin PII, rate-limited |

### Cumplimiento LGPDPPSO

| Requisito | Implementacion |
|-----------|---------------|
| Minimizacion de datos | API retorna solo campos necesarios segun scope |
| Cifrado en reposo | Campos PII cifrados con APP_KEY |
| Registro de acceso | `audit_logs` con IP, user_agent, query |
| Derecho de cancelacion | Soft delete + purga programada |
| Transferencia controlada | Solo via API autenticada, nunca exportacion directa |
| Evidencia inmutable | Snapshots con hash; datos originales no se modifican post-generacion |

### Flujo de autenticacion inter-servicio

```mermaid
sequenceDiagram
    participant DTE as dte-spp
    participant GB as GeoBase
    participant DB as audit_logs

    DTE->>GB: GET /api/v1/geobase/programs/5/coverage<br/>Authorization: Bearer <token_mir>

    GB->>GB: Sanctum: validar token
    GB->>GB: CheckScope: verificar padron:read
    GB->>GB: EnforceRateLimit: verificar throttle
    GB->>DB: AuditPiiAccess: registrar acceso

    GB-->>DTE: 200 { coverage: 1247, period: "2026-Q2" }
```

---

## 10. Pendientes de Implementacion

> Actualizado: 2026-03-19. El PR #25 (`feat/geobase-integration`) resolvio la capa de
> comunicacion HTTP, webhooks y vinculacion de programas. Los pendientes restantes son
> de capa de negocio (UI, datos demograficos, reportes).

### En dte-spp

| Tarea | Prioridad | Dependencia | Notas |
|-------|-----------|-------------|-------|
| Columnas `geobase_*` en `indicador_variables` | Alta | Migracion | `geobase_endpoint_type`, `geobase_reference_id`, `geobase_filter_params`, `geobase_value_key` |
| Boton "Sincronizar con GeoBase" en CapturaAvance | Alta | Migracion anterior + GeoBaseClient ✅ | Campo readonly cuando valor viene de GeoBase |
| Almacenar `snapshot_hash` en `avance_evidencias.hash_archivo` | Media | GeoBaseClient ✅ | Columna existe, falta logica para poblarla desde webhook `snapshot.generated` |
| Consumo desagregados para Anexo 11 | Media | AgeGroupEnum + EthnicityEnum en GeoBase | Reporte transversal de genero/edad/etnia |
| Dashboard de estado de integracion | Baja | Todo lo anterior | Widget con ultimo sync, errores, variables vinculadas |
| Job de sync automatico al cierre de periodo | Baja | Boton sincronizar | Sincroniza todas las variables con `geobase_endpoint_type` al cerrar trimestre |

### En GeoBase

| Tarea | Prioridad | Dependencia | Notas |
|-------|-----------|-------------|-------|
| `AgeGroupEnum` | Alta | Definicion rangos Anexo 11 | infantes, ninios, adolescentes, jovenes, adultos, adultos_mayores |
| `EthnicityEnum` | Alta | Catalogo INPI | indigena, no_indigena |
| Campo `discapacidad` en `beneficiaries` | Alta | Migracion | Booleano — requerido por indicadores de inclusion |
| Desagregados completos en snapshot metadata | Media | Enums anteriores | Incluir grupo_edad, etnia, discapacidad en JSONB |
| `vw_reporte_territorial` (vista materializada) | Media | Capas INEGI cargadas | Cruce beneficiarios x municipios x CONEVAL |
| Evento `beneficiary.created` | Baja | WebhookService ✅ | Falta event class + mapping en DispatchWebhooks listener |
| Endpoint `GET /territorial-report/{municipio}` | Baja | vw_reporte_territorial | Reporte pre-calculado por municipio |

### Compartido

| Tarea | Prioridad | Notas |
|-------|-----------|-------|
| ~~Pruebas end-to-end de integracion~~ | ~~Alta~~ | ✅ Resuelto en PR #25 (3 suites) |
| Documentar codigos de error compartidos | Media | 4xx/5xx estandarizados |
| Monitoreo de salud de integracion | Baja | Health check bidireccional |

---

## Apendice A: Configuracion de Red (Desarrollo Local)

| Servicio | Puerto | URL |
|----------|--------|-----|
| dte-spp (app) | 80 | `http://localhost` |
| GeoBase (app) | 8081 | `http://localhost:8081` |
| GeoBase PostgreSQL | 5433 | `localhost:5433` |
| GeoBase Redis | 6380 | `localhost:6380` |
| GeoBase Vite | 5174 | `localhost:5174` |
| dte-spp PostgreSQL | 5432 | `localhost:5432` |
| dte-spp Redis | 6379 | `localhost:6379` |

En desarrollo local, dte-spp se conecta a GeoBase via `http://localhost:8081/api/v1/geobase/`.

## Apendice B: Eventos Webhook

| Evento | Payload | Cuando se dispara |
|--------|---------|-------------------|
| `beneficiary.created` | `{ beneficiary_id, curp_hash }` | Nuevo beneficiario registrado |
| `beneficiary.relocated` | `{ beneficiary_id, old_municipality, new_municipality }` | Cambio de domicilio |
| `enrollment.approved` | `{ enrollment_id, program_id, component_id }` | Inscripcion aprobada |
| `enrollment.observed` | `{ enrollment_id, reason }` | Inscripcion observada (fuera de poligono) |
| `snapshot.generated` | `{ snapshot_id, hash, period, valor_oficial }` | Snapshot completado |
| `duplicate.detected` | `{ primary_id, secondary_id, similarity }` | Posible duplicado |
