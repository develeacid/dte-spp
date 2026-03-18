# Integración: Módulo Presupuestal y Padrón de Beneficiarios (GeoBase)

> **Estado:** Módulo Presupuestal pendiente de implementación. Padrón de Beneficiarios
> delegado a **GeoBase** (hub geoespacial independiente, ya en operación).
>
> **Actualizado:** 2026-03-18 — Sprint 8 de GeoBase completado.

---

## 1. Módulo Presupuestal (Financial Tracking)

### Propósito

Habilitar la generación de la **Cuenta Pública** (reporte financiero anual con
obligación legal) cruzando el **avance físico** (resultados MIR, ya existente en
el sistema) con el **avance financiero** (ejecución presupuestal, por construir).

Actualmente el sistema registra la cadena:

```
ProgramaPresupuestario
  └─ MirNivel (fin, propósito, componente, actividad)
       └─ Indicador (fórmula, semáforo, frecuencia)
            └─ MetaPeriodo (meta por trimestre/semestre/año)
                 └─ Avance (resultado capturado, semáforo, evidencias)
```

El módulo presupuestal añade una rama paralela desde `ProgramaPresupuestario`
para capturar la ejecución financiera trimestral.

### Tablas sugeridas

#### `partidas_presupuestales`

Partidas de gasto asignadas a cada programa por ejercicio fiscal.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | Auto-incremental |
| `programa_presupuestario_id` | bigint FK → `programa_presupuestarios.id` | Programa al que pertenece |
| `clave_partida` | varchar(20) | Clave del Clasificador por Objeto del Gasto (COG) |
| `descripcion` | varchar(255) | Descripción de la partida |
| `monto_aprobado` | decimal(15,2) | Presupuesto aprobado por el Congreso/Legislatura |
| `monto_modificado` | decimal(15,2) | Presupuesto después de adecuaciones |
| `ejercicio_fiscal` | smallint | Año fiscal (e.g. 2026) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indices sugeridos: `(programa_presupuestario_id, ejercicio_fiscal)`.

#### `avance_financiero`

Avance financiero trimestral por partida.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `partida_presupuestal_id` | bigint FK → `partidas_presupuestales.id` | |
| `trimestre` | smallint CHECK 1-4 | Trimestre del ejercicio |
| `monto_pagado` | decimal(15,2) | Monto efectivamente pagado |
| `monto_devengado` | decimal(15,2) | Monto devengado (obligación reconocida) |
| `monto_comprometido` | decimal(15,2) | Monto comprometido (contrato/pedido) |
| `registrado_por` | bigint FK → `users.id` | Usuario que capturó |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indices sugeridos: `(partida_presupuestal_id, trimestre)` UNIQUE.

### Relaciones con modelos existentes

```php
// ProgramaPresupuestario (existente — agregar relación)
public function partidasPresupuestales(): HasMany
{
    return $this->hasMany(PartidaPresupuestal::class);
}

// PartidaPresupuestal (nuevo)
public function programa(): BelongsTo
{
    return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
}

public function avancesFinancieros(): HasMany
{
    return $this->hasMany(AvanceFinanciero::class);
}

// AvanceFinanciero (nuevo)
public function partida(): BelongsTo
{
    return $this->belongsTo(PartidaPresupuestal::class, 'partida_presupuestal_id');
}

public function registrador(): BelongsTo
{
    return $this->belongsTo(User::class, 'registrado_por');
}
```

**Cruce clave:** El servicio `IndiceEficaciaService` (o equivalente) puede
extenderse para calcular la razón avance financiero vs. avance físico por
programa y trimestre:

```
Índice de eficiencia = (% avance físico) / (% avance financiero)
```

Donde `% avance físico` se obtiene de `Avance.resultado` vs `MetaPeriodo.meta_periodo`,
y `% avance financiero` de `sum(monto_pagado)` vs `sum(monto_aprobado)` por programa.

### Reportes que se desbloquean

| Reporte | Nivel | Descripción |
|---|---|---|
| **Cuenta Pública** | Nivel 1 — Legal | Cruce de avance físico (modelo `Avance`) con avance financiero. Obligatorio ante la ASF/ASE. |
| **FMyE mejorada** | Nivel 2 — Táctico | Agrega sección "Presupuesto ejercido" a la ficha de monitoreo existente. |
| **Alerta de subejercicio** | Operativo | `monto_aprobado - monto_pagado` acumulado al cierre del trimestre; alerta si el subejercicio supera umbral configurable. |

### Puntos de integración con sistemas externos

- **SIIF, SAP, o ERP estatal:** Importación vía CSV o API REST.
- **Sugerencia:** Crear un job `ImportacionFinanciera` que:
  1. Reciba un archivo CSV (clave_partida, trimestre, pagado, devengado, comprometido).
  2. Valide contra partidas registradas.
  3. Cree/actualice registros de `AvanceFinanciero`.
  4. Registre el resultado en `importacion_reportes` (tabla existente).

### Permisos necesarios

| Permiso | Descripción |
|---|---|
| `gestionar_presupuesto` | CRUD de partidas presupuestales |
| `capturar_avance_financiero` | Registrar avance financiero trimestral |
| `exportar_cuenta_publica` | Generar y descargar reporte de Cuenta Pública |

---

## 2. Padrón de Beneficiarios — Integración con GeoBase

### Cambio Arquitectónico

El padrón de beneficiarios **no se construye dentro de dte-spp-2026**. Esta responsabilidad fue delegada a **GeoBase**, un hub geoespacial independiente que ya está en operación.

GeoBase es dueño de:
- **Identidad de beneficiarios** — registro único por CURP, deduplicación cruzada entre todos los sistemas satélite
- **Validación geográfica** — PostGIS con capas INEGI/CONEVAL, poka-yoke espacial
- **Inscripciones a programas** — registro de apoyos entregados con validación geográfica
- **Evidencia auditable** — snapshots criptográficos SHA-256 para Cuenta Pública y MIR
- **Cumplimiento LGPDPPSO** — cifrado de PII, auditoría de acceso, política de retención

dte-spp-2026 **no almacena datos personales de beneficiarios**. Solo almacena el `geobase_beneficiary_id` como referencia al registro maestro.

### Conexión entre sistemas

| Sistema | Puerto | Base de datos | Función |
|---------|--------|---------------|---------|
| dte-spp-2026 (MIR) | `localhost:80` | PostgreSQL :5432 | Avance físico, indicadores, metas |
| GeoBase | `localhost:8081` | PostgreSQL :5433 (PostGIS) | Beneficiarios, inscripciones, georreferenciación |

Ambos sistemas corren en la misma máquina pero con redes Docker y bases de datos independientes. La comunicación es exclusivamente vía API REST con tokens Sanctum.

### API de GeoBase disponible

Base URL: `http://localhost:8081/api/v1/geobase`

Autenticación: `Authorization: Bearer {token-sanctum}`

| Endpoint | Método | Scope requerido | Función |
|----------|--------|-----------------|---------|
| `/beneficiaries` | POST | `padron:register` | Alta/upsert por CURP (idempotente) |
| `/beneficiaries/{id}` | GET | `padron:read` | Consultar beneficiario con PII |
| `/beneficiaries/{id}` | PATCH | `padron:update-identity` | Actualizar datos de identidad |
| `/enrollments` | POST | `padron:enroll` | Registrar inscripción a programa |
| `/enrollments` | GET | `padron:read` | Listar inscripciones (filtros: program_id, status) |
| `/enrollments/{id}` | GET | `padron:read` | Detalle de inscripción |
| `/enrollments/{id}/approve` | POST | `padron:enroll` | Aprobar inscripción |
| `/enrollments/{id}/finalize` | POST | `padron:enroll` | Finalizar inscripción |
| `/validation/location` | POST | `padron:validate` | Validar coordenadas contra zona elegible |
| `/validation/curp` | POST | `padron:validate` | Verificar si CURP ya existe |
| `/snapshot` | POST | `padron:snapshot` | Generar snapshot criptográfico trimestral |
| `/programs/{id}/coverage` | GET | `padron:read` | Estadísticas de cobertura del programa |

Documentación completa: ver proyecto GeoBase `docs/api/geobase-v1.md`.

### Tokens de la MIR

| Token | Scopes | Uso |
|-------|--------|-----|
| `mir` | `padron:read`, `padron:snapshot` | Consultas y snapshots para evaluación |
| `operadores-ur` | `padron:register`, `padron:enroll`, `padron:read`, `padron:update-identity` | Operación completa del padrón |

### Modelo de datos local (dte-spp-2026)

dte-spp-2026 **no crea tablas de beneficiarios**. Solo almacena la referencia:

```php
// En cualquier modelo local que necesite vincular a un beneficiario
Schema::table('alguna_tabla_local', function (Blueprint $table) {
    $table->unsignedBigInteger('geobase_beneficiary_id')->nullable();
    $table->index('geobase_beneficiary_id');
});
```

Si se necesitan datos del beneficiario (nombre, CURP, municipio), se consultan a GeoBase en tiempo real vía `GET /beneficiaries/{id}`. **No se replican.**

### Flujo de integración: Los 3 momentos de la MIR

#### Momento 1 — Programación (Enlace Lógico)

El Planeador vincula un Componente MIR con su programa en GeoBase. Configura las variables estandarizadas del Diccionario de Variables que apuntan a endpoints de GeoBase.

```
MirNivel (componente)
  └─ Variable estandarizada: "Número de beneficiarios aprobados"
       └─ Fuente: GET /api/v1/geobase/programs/{id}/coverage
```

**No se intercambian datos de ciudadanos en esta fase.**

#### Momento 2 — Seguimiento (Enlace Operativo)

La MIR consulta GeoBase para obtener el valor actual del indicador. El campo de valor está **bloqueado (read-only)** — el Operador no puede escribir en él.

```
MIR solicita → GET /api/v1/geobase/programs/3/coverage
GeoBase responde → { "total_enrollments": 500, "aprobados": 450, ... }
MIR actualiza → Avance.resultado = 450 (automático, no editable)
```

Este es un **control anticorrupción arquitectónico**: la MIR solo acepta lo que existe en GeoBase como registros georreferenciados y aprobados.

#### Momento 3 — Evaluación (Medio de Verificación)

```
MIR solicita → POST /api/v1/geobase/snapshot
               { component_id: 45, period: "2026-Q1", cutoff_date: "2026-03-31" }

GeoBase responde → { snapshot_hash: "a1b2c3...", evidencia_url: "...", valor_oficial: 500 }
```

El auditor descarga el CSV del snapshot, recalcula SHA-256 y compara contra el hash almacenado en la MIR. Coincide = evidencia íntegra.

### Webhooks que recibe la MIR

GeoBase envía webhooks cuando ocurren eventos relevantes. La MIR debe suscribirse a:

| Evento | Cuándo se dispara | Acción sugerida en MIR |
|--------|-------------------|------------------------|
| `enrollment.status_changed` | Inscripción cambia de estado | Actualizar avance del indicador |
| `enrollment.observed` | Inscripción observada por geografía | Alertar al Planeador |
| `beneficiary.relocated` | Beneficiario cambia de domicilio | Verificar impacto en indicadores |
| `snapshot.generated` | Snapshot trimestral generado | Vincular como evidencia MIR |
| `sync.processed` | Operador GeoBase sincronizó datos offline | Informativo — actualizar conteos si aplica |

Documentación de webhooks: ver proyecto GeoBase `docs/webhooks/events.md`.

Verificación de firma HMAC: ver proyecto GeoBase `docs/webhooks/verification.md`.

### Servicio HTTP sugerido para la MIR

```php
// app/Services/GeoBaseClient.php
class GeoBaseClient
{
    public function __construct(
        private string $baseUrl,   // config('services.geobase.url')
        private string $token,     // config('services.geobase.token')
    ) {}

    public function getBeneficiary(int $id): array { /* GET /beneficiaries/{id} */ }
    public function upsertBeneficiary(array $data): array { /* POST /beneficiaries */ }
    public function createEnrollment(array $data): array { /* POST /enrollments */ }
    public function getProgramCoverage(int $programId): array { /* GET /programs/{id}/coverage */ }
    public function requestSnapshot(array $params): array { /* POST /snapshot */ }
}
```

Configuración en `.env`:

```
GEOBASE_URL=http://localhost:8081/api/v1/geobase
GEOBASE_TOKEN=mir-sanctum-token-here
```

### Lo que la MIR NO hace

| Responsabilidad | ¿MIR? | ¿GeoBase? |
|-----------------|-------|-----------|
| Almacenar CURP, nombre, domicilio | No | Si (cifrado) |
| Validar ubicación geográfica | No | Si (PostGIS) |
| Detectar duplicados por CURP | No | Si |
| Generar padrón público anonimizado | No | Si |
| Generar snapshots criptográficos | No | Si |
| Registrar avance físico (indicadores) | Si | No |
| Calcular semáforos MIR | Si | No |
| Generar Cuenta Pública | Si | No (provee evidencia) |

---

## 3. Diagrama de Integración

```
ProgramaPresupuestario (existente en MIR)
│
├── MirNiveles (existente)
│   └── Indicadores (existente)
│       └── MetaPeriodo (existente)
│           └── Avance (existente) ──── avance FÍSICO
│                                        │
│                                        │ valor read-only
│                                        │ desde GeoBase
│                                        ▼
│                              ┌──────────────────────┐
│                              │      GEOBASE          │
│                              │  (sistema externo)    │
│                              │                       │
│                              │  Beneficiarios        │
│                              │  Inscripciones        │
│                              │  Validación PostGIS   │
│                              │  Snapshots SHA-256    │
│                              │  Webhooks ──────────────── notifica a MIR
│                              └──────────────────────┘
│
├── PartidaPresupuestal (NUEVO — interno MIR)
│   └── AvanceFinanciero (NUEVO) ──── avance FINANCIERO
│
└── REPORTES CRUZADOS
    ├── Cuenta Pública = Avance Físico ⊕ Avance Financiero
    ├── FMyE mejorada = FMyE actual + sección presupuestal
    └── Evidencia = Snapshot GeoBase (hash SHA-256)
```

### Flujo de datos para Cuenta Pública

```
┌─────────────────────────────┐     ┌──────────────────────────────┐
│  AVANCE FÍSICO (existente)  │     │  AVANCE FINANCIERO (nuevo)   │
│                             │     │                              │
│  MetaPeriodo.meta_periodo   │     │  PartidaPresupuestal         │
│  Avance.resultado           │     │    .monto_aprobado           │
│  → % cumplimiento físico    │     │  AvanceFinanciero            │
│                             │     │    .monto_pagado             │
│  Fuente: GeoBase API        │     │  → % ejercicio financiero    │
│  (read-only, anticorrupción)│     │                              │
└─────────────┬───────────────┘     └──────────────┬───────────────┘
              │                                    │
              └──────────┬─────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │    CUENTA PÚBLICA    │
              │                      │
              │  Por programa:       │
              │  - % avance físico   │
              │  - % avance financ.  │
              │  - Índice eficiencia │
              │  - Subejercicio      │
              │  - Evidencia: hash   │
              │    snapshot GeoBase  │
              └──────────────────────┘
```

---

## 4. Orden de Implementación Sugerido

### Fase 1: Módulo Presupuestal (interno MIR)

**Prioridad:** Alta — la Cuenta Pública es obligación legal con fecha de entrega
fija ante la ASF/ASE.

- Crear migraciones para `partidas_presupuestales` y `avance_financiero`.
- Crear modelos `PartidaPresupuestal` y `AvanceFinanciero` en `app/Models/Presupuesto/`.
- Agregar relación `partidasPresupuestales()` a `ProgramaPresupuestario`.
- Implementar CRUD de partidas y captura de avance financiero.
- Implementar job `ImportacionFinanciera` para carga CSV.
- Agregar permisos y asignarlos a roles existentes.
- Generar reporte de Cuenta Pública (PDF vía DomPDF, ya instalado).

### Fase 2: Integración con GeoBase (Padrón de Beneficiarios)

**Prioridad:** Media — GeoBase ya está operativo. La integración requiere:

1. **Configuración de conexión:**
   - Agregar `GEOBASE_URL` y `GEOBASE_TOKEN` a `.env`
   - Crear `config/services.php` entry para GeoBase
   - Crear `GeoBaseClient` service con métodos para cada endpoint

2. **Enlace lógico (Momento 1):**
   - Agregar campo `geobase_program_id` a la tabla de componentes MIR (o configurar el mapeo en config)
   - UI para que el Planeador vincule componentes MIR con programas GeoBase

3. **Enlace operativo (Momento 2):**
   - Implementar consulta automática de cobertura vía `GET /programs/{id}/coverage`
   - Campo `Avance.resultado` bloqueado — valor proviene de GeoBase
   - Agregar indicador visual: "Fuente: GeoBase (automático)"

4. **Webhook handler:**
   - Crear endpoint `POST /webhooks/geobase` para recibir eventos
   - Verificar firma HMAC
   - Procesar eventos: `enrollment.status_changed`, `snapshot.generated`
   - Actualizar avances y vincular snapshots como evidencia

5. **Snapshots (Momento 3):**
   - Implementar solicitud de snapshot vía `POST /snapshot`
   - Almacenar `snapshot_hash` y `evidencia_url` en tabla de evidencias MIR
   - UI para que el auditor verifique integridad (recalcular SHA-256)

### Fase 3: Reportes cruzados

**Prioridad:** Baja — requiere Fase 1 y Fase 2 completas.

- Reporte de Cuenta Pública con evidencia GeoBase (hash de snapshot)
- FMyE mejorada con sección presupuestal
- Alerta de subejercicio vs. avance físico
- Dashboard con indicadores cruzados (físico + financiero + padrón)

---

## 5. Permisos necesarios

### Módulo Presupuestal (internos MIR)

| Permiso | Descripción |
|---|---|
| `gestionar_presupuesto` | CRUD de partidas presupuestales |
| `capturar_avance_financiero` | Registrar avance financiero trimestral |
| `exportar_cuenta_publica` | Generar y descargar reporte de Cuenta Pública |

### Integración GeoBase (consulta externa)

| Permiso | Descripción |
|---|---|
| `consultar_geobase` | Consultar beneficiarios e inscripciones vía API |
| `solicitar_snapshot` | Solicitar snapshots criptográficos a GeoBase |
| `vincular_programa_geobase` | Configurar enlace componente MIR ↔ programa GeoBase |

---

## Referencias

- **Modelos existentes consultados:**
  - `app/Models/ProgramaPresupuestario.php` — modelo raíz de programas
  - `app/Models/Mml/MirNivel.php` — niveles de la MIR
  - `app/Models/Mml/Indicador.php` — indicadores con fórmula y semáforo
  - `app/Models/Mml/MetaPeriodo.php` — metas por periodo
  - `app/Models/Tracking/Avance.php` — avance físico capturado
- **Paquetes ya instalados:** `barryvdh/laravel-dompdf`, `maatwebsite/excel`, `spatie/laravel-activitylog`
- **Base de datos:** PostgreSQL 16 (imagen `pgvector/pgvector:pg16`)
- **GeoBase docs:** `docs/api/geobase-v1.md`, `docs/webhooks/events.md`, `docs/architecture/integracion-sistemas-satelite.md`
- **GeoBase repo:** `develeacid/geobase` — Puerto 8081, PostgreSQL 5433 (PostGIS)
