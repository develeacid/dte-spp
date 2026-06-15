# Especificación de Integración: GeoBase ↔ dte-spp
**Versión:** 1.0 · **Fecha:** 2026-04-23
**Estado actual:** CERO líneas de código de integración existen en dte-spp. Todo lo aquí descrito es primera implementación.

---

## Principio de integración

dte-spp es el sistema de gestión del desempeño (MIR). GeoBase es el registro maestro de beneficiarios. La integración sigue el principio **"GeoBase como fuente única de verdad para datos de persona y territorio"**: dte-spp nunca almacena CURP, dirección ni datos biométricos propios — los delega a GeoBase y conserva solo el `geobase_beneficiary_id` como referencia.

```
dte-spp                          GeoBase
────────────────────            ───────────────────
Programas (MIR)       ←──────→  Beneficiaries
Indicadores           ←──────→  Enrollments
Avances               ←──────→  Snapshots
Beneficiarios (ref)   ←──────→  Webhooks
```

---

## Configuración base requerida

### En dte-spp

**`config/geobase.php`** (archivo nuevo):
```php
return [
    'base_url'   => env('GEOBASE_API_URL', 'http://localhost:8081'),
    'api_key'    => env('GEOBASE_API_KEY'),       // Sanctum token de servicio
    'timeout'    => env('GEOBASE_TIMEOUT', 10),   // segundos
    'retry'      => env('GEOBASE_RETRY', 2),
    'webhook_secret' => env('GEOBASE_WEBHOOK_SECRET'),
];
```

**`.env` de dte-spp:**
```
GEOBASE_API_URL=http://geobase.sde.internal:8081
GEOBASE_API_KEY=<sanctum_token_generado_en_geobase>
GEOBASE_WEBHOOK_SECRET=<hmac_secret_compartido>
```

**`app/Services/GeoBaseClient.php`** (clase nueva — esqueleto):
```php
namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GeoBaseClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(config('geobase.base_url'))
            ->withToken(config('geobase.api_key'))
            ->timeout(config('geobase.timeout'))
            ->retry(config('geobase.retry'), 500);
    }

    // Métodos específicos en cada momento — ver secciones siguientes
}
```

**Migración en dte-spp** — columnas de referencia a GeoBase:
```php
// En tabla beneficiarios (si existe) o en la tabla que gestione personas
$table->unsignedBigInteger('geobase_beneficiary_id')->nullable()->index();
$table->unsignedBigInteger('geobase_enrollment_id')->nullable()->index();
$table->string('geobase_last_sync_at')->nullable();
```

**Verificación de configuración base:**
```bash
# dte-spp
grep -n "geobase" config/geobase.php
grep -n "GeoBaseClient" app/Services/GeoBaseClient.php
php artisan config:show geobase

# GeoBase — verificar que el token de servicio exista
php artisan tinker --execute="echo \App\Models\User::where('email','service@spp.internal')->first()?->id;"
```

### En GeoBase

**Token de servicio** — crear usuario de servicio para dte-spp:
```bash
php artisan tinker
>>> $user = \App\Models\User::create(['name'=>'spp-service','email'=>'service@spp.internal','password'=>bcrypt(Str::random(32))]);
>>> echo $user->createToken('spp-integration')->plainTextToken;
# Copiar este token al GEOBASE_API_KEY de dte-spp
```

**`config/geobase.php` de GeoBase** — verificar que existan:
```php
'webhook_secret' => env('GEOBASE_WEBHOOK_SECRET'),
'spp_webhook_url' => env('SPP_WEBHOOK_URL', 'http://spp.sde.internal/api/webhooks/geobase'),
```

---

## Momento 1 — Validación Geográfica al Crear Beneficiario

### Descripción
Antes de guardar un nuevo beneficiario en dte-spp, el sistema consulta a GeoBase si el domicilio capturado cae dentro de la zona de cobertura del programa. GeoBase responde con el municipio normalizado y el resultado de la validación PostGIS.

**Trigger:** `POST /beneficiarios` (o equivalente en dte-spp) — antes del `$beneficiario->save()`

### Flujo de datos

```
Usuario llena formulario dte-spp
        ↓
dte-spp: POST /api/v1/validar-direccion (GeoBase)
        ↓
GeoBase: ST_Contains(programa.zona_cobertura, ST_Point(lon, lat))
        ↓
Respuesta: { valido: true/false, municipio_id, municipio_nombre, advertencias[] }
        ↓
dte-spp: si valido=false → mostrar error; si valido=true → continuar guardado
```

### Contrato de API — GeoBase debe exponer

**Endpoint:** `POST /api/v1/geo/validate-address`
**Auth:** Bearer token (Sanctum)

**Request:**
```json
{
  "program_id": 12,
  "domicilio": {
    "calle": "Av. Reforma",
    "numero_exterior": "100",
    "colonia": "Centro",
    "municipio_clave": "01001",
    "estado_clave": "01",
    "cp": "20000",
    "latitud": 21.882,
    "longitud": -102.293
  }
}
```

**Response 200 (válido):**
```json
{
  "valido": true,
  "municipio_id": 47,
  "municipio_nombre": "Aguascalientes",
  "municipio_clave": "01001",
  "dentro_cobertura": true,
  "advertencias": []
}
```

**Response 200 (fuera de cobertura):**
```json
{
  "valido": false,
  "municipio_id": 47,
  "municipio_nombre": "Aguascalientes",
  "municipio_clave": "01001",
  "dentro_cobertura": false,
  "advertencias": ["El domicilio está fuera de la zona de cobertura del programa ID 12"]
}
```

**Response 422 (sin coordenadas):**
```json
{
  "valido": null,
  "advertencias": ["No se proporcionaron coordenadas — validación geográfica omitida"],
  "municipio_id": null
}
```

### Código esperado en dte-spp

**`app/Services/GeoBaseClient.php`** — agregar método:
```php
public function validateAddress(int $programId, array $domicilio): array
{
    return $this->http
        ->post('/api/v1/geo/validate-address', [
            'program_id' => $programId,
            'domicilio'  => $domicilio,
        ])
        ->throw()
        ->json();
}
```

**Controller / Action de dte-spp:**
```php
// En BeneficiarioController@store (o similar)
$geoResult = app(GeoBaseClient::class)->validateAddress(
    $request->programa_id,
    $request->only(['calle','numero_exterior','colonia','municipio_clave','estado_clave','cp','latitud','longitud'])
);

if ($geoResult['valido'] === false) {
    return back()->withErrors(['domicilio' => $geoResult['advertencias'][0] ?? 'Domicilio fuera de cobertura']);
}

// Guardar municipio_id normalizado de GeoBase
$beneficiario->municipio_id_geo = $geoResult['municipio_id'];
```

### Código esperado en GeoBase

**`routes/api/geobase.php`** — añadir:
```php
Route::middleware('auth:sanctum')->post('/geo/validate-address', GeoValidationController::class);
```

**`app/Http/Controllers/Api/GeoValidationController.php`** (nuevo):
```php
public function __invoke(ValidateAddressRequest $request): JsonResponse
{
    $program = Program::findOrFail($request->program_id);
    $result = $this->geoService->validateDomicilio($program, $request->domicilio);
    return response()->json($result);
}
```

**`app/Services/GeoValidationService.php`** — lógica PostGIS:
```php
// Consulta PostGIS
$query = DB::select("
    SELECT ST_Contains(
        (SELECT zona_cobertura FROM programs WHERE id = ?),
        ST_SetSRID(ST_Point(?, ?), 4326)
    ) AS dentro_cobertura,
    im.id AS municipio_id, im.nombre AS municipio_nombre, im.clave AS municipio_clave
    FROM inegi_municipios im
    WHERE ST_Contains(im.geom, ST_SetSRID(ST_Point(?, ?), 4326))
    LIMIT 1
", [$programId, $lon, $lat, $lon, $lat]);
```

### Lista de verificación cruzada — Momento 1

| # | Verificar en | Comando / archivo | Resultado esperado |
|---|---|---|---|
| M1-V1 | GeoBase | `php artisan route:list \| grep validate-address` | Ruta `POST api/v1/geo/validate-address` existe |
| M1-V2 | GeoBase | `grep -rn "GeoValidationController" app/Http/Controllers/Api/` | Archivo existe |
| M1-V3 | GeoBase | `grep -n "ST_Contains\|zona_cobertura" app/Services/GeoValidationService.php` | Consulta PostGIS presente |
| M1-V4 | dte-spp | `grep -n "validateAddress" app/Services/GeoBaseClient.php` | Método existe |
| M1-V5 | dte-spp | `grep -rn "GeoBaseClient\|validateAddress" app/Http/Controllers/` | Llamada en controller |
| M1-V6 | dte-spp | `grep -n "municipio_id_geo" database/migrations/` | Columna existe en migración |

---

## Momento 2 — Creación / Deduplicación de Beneficiario en GeoBase

### Descripción
Después de validar el domicilio (Momento 1), dte-spp registra al beneficiario en GeoBase. GeoBase deduplica por `curp_hash`: si el beneficiario ya existe, devuelve el ID existente; si no, lo crea. dte-spp conserva el `geobase_beneficiary_id` como única referencia — **nunca almacena CURP propio**.

**Trigger:** Inmediatamente después de que M1 retorna `valido=true`, antes de hacer `commit` en dte-spp.

### Flujo de datos

```
M1 completado (valido=true)
        ↓
dte-spp: POST /api/v1/beneficiaries (GeoBase)
        ↓
GeoBase: busca por curp_hash → si existe devuelve id; si no crea registro
        ↓
Respuesta: { geobase_id, created: true/false, status }
        ↓
dte-spp: guarda geobase_beneficiary_id en su propio registro
```

### Contrato de API — GeoBase debe exponer

**Endpoint:** `POST /api/v1/beneficiaries`
**Auth:** Bearer token (Sanctum)

**Request:**
```json
{
  "curp":              "GOML900101HDFNRS09",
  "nombre":            "Luis",
  "primer_apellido":   "González",
  "segundo_apellido":  "Morales",
  "fecha_nacimiento":  "1990-01-01",
  "genero":            "masculino",
  "es_indigena":       false,
  "pueblo_originario": null,
  "discapacidad":      false,
  "tipo_discapacidad": null,
  "domicilio": {
    "municipio_id": 47,
    "calle": "Av. Reforma",
    "numero_exterior": "100",
    "colonia": "Centro",
    "cp": "20000",
    "latitud": 21.882,
    "longitud": -102.293
  },
  "origen_sistema":    "spp",
  "origen_programa_id": 12
}
```

**Response 201 (creado):**
```json
{
  "geobase_id": 8341,
  "created": true,
  "curp_hash": "a3f7...c9e2",
  "status": "activo"
}
```

**Response 200 (ya existía — deduplicado):**
```json
{
  "geobase_id": 6102,
  "created": false,
  "curp_hash": "a3f7...c9e2",
  "status": "activo",
  "advertencias": ["Beneficiario ya registrado — se reutiliza ID existente"]
}
```

**Response 409 (duplicado con datos inconsistentes):**
```json
{
  "error": "duplicate_conflict",
  "geobase_id": 6102,
  "mensaje": "El CURP ya existe pero con nombre diferente. Revisión manual requerida.",
  "inconsistencias": ["nombre: 'Luis' vs 'Luis Alberto'"]
}
```

### Código esperado en dte-spp

**`app/Services/GeoBaseClient.php`** — agregar:
```php
public function registerBeneficiary(array $data): array
{
    return $this->http
        ->post('/api/v1/beneficiaries', $data)
        ->throw()
        ->json();
}
```

**En la transacción de creación:**
```php
DB::transaction(function () use ($request, $geoResult) {
    $geoReg = app(GeoBaseClient::class)->registerBeneficiary([
        'curp'             => $request->curp,
        'nombre'           => $request->nombre,
        'primer_apellido'  => $request->primer_apellido,
        'segundo_apellido' => $request->segundo_apellido,
        'fecha_nacimiento' => $request->fecha_nacimiento,
        'genero'           => $request->genero,
        'es_indigena'      => $request->es_indigena,
        'discapacidad'     => $request->discapacidad,
        'tipo_discapacidad'=> $request->tipo_discapacidad,
        'domicilio'        => array_merge($request->domicilio, [
            'municipio_id' => $geoResult['municipio_id'],
        ]),
        'origen_sistema'    => 'spp',
        'origen_programa_id'=> $request->programa_id,
    ]);

    $beneficiario = Beneficiario::create([
        // campos propios de dte-spp (NO incluir CURP aquí)
        'programa_id'             => $request->programa_id,
        'geobase_beneficiary_id'  => $geoReg['geobase_id'],
        'status'                  => 'activo',
    ]);
});
```

> **IMPORTANTE:** dte-spp NO debe tener columna `curp` en su tabla de beneficiarios. El CURP solo existe en GeoBase (cifrado con AES-256-CBC + hash para búsqueda). Si dte-spp requiere buscar por CURP, debe delegar la búsqueda a GeoBase via `GET /api/v1/beneficiaries?curp_hash={sha256(curp)}`.

### Lista de verificación cruzada — Momento 2

| # | Verificar en | Comando / archivo | Resultado esperado |
|---|---|---|---|
| M2-V1 | GeoBase | `php artisan route:list \| grep "api/v1/beneficiaries"` | Ruta `POST` existe |
| M2-V2 | GeoBase | `grep -n "curp_hash\|deduplic" app/Http/Controllers/Api/BeneficiaryController.php` | Lógica de deduplicación |
| M2-V3 | GeoBase | `grep -n "'encrypted'" app/Models/Beneficiary.php` | CURP cifrado (no plain text) |
| M2-V4 | dte-spp | `grep -n "registerBeneficiary" app/Services/GeoBaseClient.php` | Método existe |
| M2-V5 | dte-spp | `grep -n "geobase_beneficiary_id" database/migrations/` | Columna de referencia |
| M2-V6 | dte-spp | `grep -rn "->curp\b" app/Models/Beneficiario.php` | NO debe existir columna curp propia |
| M2-V7 | dte-spp | `grep -rn "DB::transaction" app/Http/Controllers/*Beneficiario*` | Creación en transacción |

---

## Momento 3 — Registro de Inscripción al Aprobar Beneficiario en un Programa

### Descripción
Cuando un beneficiario es aprobado en dte-spp para participar en un programa específico, dte-spp notifica a GeoBase para crear el `Enrollment`. GeoBase es quien lleva el registro histórico de todas las inscripciones con estatus normativo completo (SOLICITADO → APROBADO). dte-spp conserva `geobase_enrollment_id` para futuras referencias.

**Trigger:** Evento/acción de aprobación en dte-spp (`BeneficiarioAprobado` event o equivalente)

### Flujo de datos

```
Usuario aprueba beneficiario en dte-spp
        ↓
dte-spp: POST /api/v1/enrollments (GeoBase)
        ↓
GeoBase: crea Enrollment con status=APROBADO, verifica geobase_beneficiary_id
        ↓
Respuesta: { geobase_enrollment_id, status, monto_asignado }
        ↓
dte-spp: guarda geobase_enrollment_id en su registro de participación
```

### Contrato de API — GeoBase debe exponer

**Endpoint:** `POST /api/v1/enrollments`
**Auth:** Bearer token (Sanctum)

**Request:**
```json
{
  "beneficiary_id":   8341,
  "program_id":       12,
  "component_id":     3,
  "status":           "aprobado",
  "monto_entregado":  2500.00,
  "enrollment_date":  "2026-04-23",
  "origen_sistema":   "spp",
  "origen_ref_id":    991
}
```

**Response 201:**
```json
{
  "geobase_enrollment_id": 19204,
  "beneficiary_id": 8341,
  "program_id": 12,
  "status": "aprobado",
  "enrollment_date": "2026-04-23"
}
```

**Response 409 (ya inscrito en mismo programa):**
```json
{
  "error": "already_enrolled",
  "geobase_enrollment_id": 18500,
  "mensaje": "Beneficiario ya tiene inscripción activa en este programa"
}
```

### Código esperado en dte-spp

**`app/Services/GeoBaseClient.php`** — agregar:
```php
public function createEnrollment(int $geobaseBeneficiaryId, array $data): array
{
    return $this->http
        ->post('/api/v1/enrollments', array_merge(
            ['beneficiary_id' => $geobaseBeneficiaryId],
            $data
        ))
        ->throw()
        ->json();
}
```

**Event Listener** — `app/Listeners/SyncEnrollmentToGeoBase.php` (nuevo):
```php
public function handle(BeneficiarioAprobado $event): void
{
    $beneficiario = $event->beneficiario;

    if (! $beneficiario->geobase_beneficiary_id) {
        Log::warning('SyncEnrollmentToGeoBase: beneficiario sin geobase_id', [
            'beneficiario_id' => $beneficiario->id,
        ]);
        return;
    }

    $result = app(GeoBaseClient::class)->createEnrollment(
        $beneficiario->geobase_beneficiary_id,
        [
            'program_id'      => $beneficiario->programa_id,
            'component_id'    => $beneficiario->componente_id,
            'status'          => 'aprobado',
            'monto_entregado' => $beneficiario->monto_asignado,
            'enrollment_date' => now()->toDateString(),
            'origen_sistema'  => 'spp',
            'origen_ref_id'   => $beneficiario->id,
        ]
    );

    $beneficiario->update([
        'geobase_enrollment_id' => $result['geobase_enrollment_id'],
    ]);
}
```

**Registrar en `EventServiceProvider`:**
```php
BeneficiarioAprobado::class => [SyncEnrollmentToGeoBase::class],
```

### Lista de verificación cruzada — Momento 3

| # | Verificar en | Comando / archivo | Resultado esperado |
|---|---|---|---|
| M3-V1 | GeoBase | `php artisan route:list \| grep "api/v1/enrollments"` | Ruta `POST` existe |
| M3-V2 | GeoBase | `grep -rn "origen_sistema\|origen_ref_id" app/Models/Enrollment.php` | Campos rastreables al origen |
| M3-V3 | dte-spp | `grep -n "createEnrollment" app/Services/GeoBaseClient.php` | Método existe |
| M3-V4 | dte-spp | `grep -rn "SyncEnrollmentToGeoBase" app/Listeners/` | Listener existe |
| M3-V5 | dte-spp | `grep -n "BeneficiarioAprobado" app/Providers/EventServiceProvider.php` | Evento registrado |
| M3-V6 | dte-spp | `grep -n "geobase_enrollment_id" database/migrations/` | Columna de referencia existe |
| M3-V7 | Ambos | Verificar enrollment GeoBase ID=8341 en programa 12 con `status=aprobado` | Registro existe tras prueba E2E |

---

## Momento 4 — Snapshot Criptográfico al Registrar Avance MIR

### Descripción
Cuando dte-spp registra un avance físico en un indicador MIR (Componente o Actividad con beneficiarios), solicita a GeoBase un snapshot criptográfico del padrón activo del programa en ese momento. El snapshot SHA-256 queda como evidencia verificable del estado del padrón en la fecha de corte trimestral.

**Trigger:** Evento `AvanceRegistrado` en dte-spp cuando `nivel IN ('componente', 'actividad')` y el indicador tiene beneficiarios vinculados.

**Prerrequisito:** N1-08 completado (campo `snapshot_type` en tabla `snapshots` de GeoBase).

### Flujo de datos

```
Usuario registra avance trimestral en dte-spp
        ↓
dte-spp: POST /api/v1/snapshots (GeoBase)
        ↓
GeoBase: calcula SHA-256 de todos los beneficiary_id activos del programa
         en la fecha de corte; guarda snapshot con tipo=avance_mir
        ↓
Respuesta: { snapshot_id, hash_sha256, beneficiarios_contados, fecha_corte }
        ↓
dte-spp: guarda snapshot_id + hash en AvanceEvidencia
```

### Contrato de API — GeoBase debe exponer

**Endpoint:** `POST /api/v1/snapshots`
**Auth:** Bearer token (Sanctum)

**Request:**
```json
{
  "program_id":       12,
  "snapshot_type":    "avance_mir",
  "fecha_corte":      "2026-03-31",
  "origen_sistema":   "spp",
  "origen_avance_id": 4421,
  "descripcion":      "Snapshot MIR Q1-2026 Indicador: Tasa de atención"
}
```

**Response 201:**
```json
{
  "snapshot_id":            892,
  "hash_sha256":            "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "snapshot_type":          "avance_mir",
  "beneficiarios_contados": 1847,
  "fecha_corte":            "2026-03-31",
  "generado_en":            "2026-04-23T14:32:00Z"
}
```

### Código esperado en dte-spp

**`app/Services/GeoBaseClient.php`** — agregar:
```php
public function createSnapshot(int $programId, string $fechaCorte, int $avanceId): array
{
    return $this->http
        ->post('/api/v1/snapshots', [
            'program_id'       => $programId,
            'snapshot_type'    => 'avance_mir',
            'fecha_corte'      => $fechaCorte,
            'origen_sistema'   => 'spp',
            'origen_avance_id' => $avanceId,
        ])
        ->throw()
        ->json();
}
```

**Event Listener** — `app/Listeners/GenerateSnapshotForAvance.php` (nuevo):
```php
public function handle(AvanceRegistrado $event): void
{
    $avance = $event->avance;

    // Solo para niveles con beneficiarios
    if (! in_array($avance->indicador->nivel, ['componente', 'actividad'])) {
        return;
    }

    $snapshot = app(GeoBaseClient::class)->createSnapshot(
        $avance->programa_id,
        $avance->periodo_fin,
        $avance->id
    );

    AvanceEvidencia::create([
        'avance_id'          => $avance->id,
        'tipo'               => 'snapshot_geobase',
        'geobase_snapshot_id'=> $snapshot['snapshot_id'],
        'hash_sha256'        => $snapshot['hash_sha256'],
        'descripcion'        => "Padrón activo: {$snapshot['beneficiarios_contados']} beneficiarios al {$snapshot['fecha_corte']}",
        'fecha_generacion'   => $snapshot['generado_en'],
    ]);
}
```

**Migración en dte-spp** — columnas en `avance_evidencias`:
```php
$table->unsignedBigInteger('geobase_snapshot_id')->nullable();
$table->char('hash_sha256', 64)->nullable();
$table->string('fecha_generacion')->nullable();
```

### Código esperado en GeoBase

**`app/Services/SnapshotService.php`** — añadir método (o extender el existente):
```php
public function createFromApi(int $programId, string $snapshotType, string $fechaCorte, array $meta = []): Snapshot
{
    // Obtener todos los beneficiary IDs activos en el programa a la fecha de corte
    $beneficiaryIds = Enrollment::query()
        ->where('program_id', $programId)
        ->where('status', 'aprobado')
        ->whereDate('enrollment_date', '<=', $fechaCorte)
        ->pluck('beneficiary_id')
        ->sort()
        ->values();

    $payload = json_encode([
        'program_id'  => $programId,
        'fecha_corte' => $fechaCorte,
        'ids'         => $beneficiaryIds,
        'count'       => $beneficiaryIds->count(),
    ]);

    return Snapshot::create([
        'program_id'      => $programId,
        'snapshot_type'   => $snapshotType,   // requiere N1-08
        'hash_sha256'     => hash('sha256', $payload),
        'payload_summary' => $payload,
        'fecha_corte'     => $fechaCorte,
        'origen_sistema'  => $meta['origen_sistema'] ?? null,
        'origen_ref_id'   => $meta['origen_avance_id'] ?? null,
        'beneficiarios_contados' => $beneficiaryIds->count(),
    ]);
}
```

### Lista de verificación cruzada — Momento 4

| # | Verificar en | Comando / archivo | Resultado esperado |
|---|---|---|---|
| M4-V1 | GeoBase | `php artisan route:list \| grep "api/v1/snapshots"` | Ruta `POST` existe |
| M4-V2 | GeoBase | `grep -n "snapshot_type" app/Services/SnapshotService.php` | Campo tipo utilizado |
| M4-V3 | GeoBase | `php artisan migrate:status \| grep snapshot_type` | Migración N1-08 aplicada |
| M4-V4 | GeoBase | `grep -n "hash('sha256'" app/Services/SnapshotService.php` | Hash calculado sobre IDs ordenados |
| M4-V5 | dte-spp | `grep -n "createSnapshot" app/Services/GeoBaseClient.php` | Método existe |
| M4-V6 | dte-spp | `grep -rn "GenerateSnapshotForAvance" app/Listeners/` | Listener existe |
| M4-V7 | dte-spp | `grep -n "geobase_snapshot_id\|hash_sha256" database/migrations/` | Columnas en avance_evidencias |
| M4-V8 | dte-spp | `grep -n "AvanceRegistrado" app/Providers/EventServiceProvider.php` | Evento registrado |

---

## Momento 5 — Webhook de GeoBase a dte-spp ante Cambio de Estatus

### Descripción
Cuando GeoBase cambia el estatus de un `Enrollment` (ej. APROBADO → FINALIZADO o CANCELADO), envía un webhook firmado con HMAC-SHA256 a dte-spp. dte-spp actualiza su registro local de participación para mantener coherencia sin necesidad de polling.

**Trigger:** Observer en GeoBase `EnrollmentObserver@updated` cuando `status` cambia.

### Flujo de datos

```
GeoBase: enrollment.status cambia (APROBADO → FINALIZADO)
        ↓
GeoBase: POST <SPP_WEBHOOK_URL>/api/webhooks/geobase
         Header: X-GeoBase-Signature: hmac-sha256=<firma>
        ↓
dte-spp: verifica firma HMAC → busca por geobase_enrollment_id → actualiza status
        ↓
dte-spp: dispara evento interno si el cambio requiere acción (ej. BeneficiarioCancelado)
```

### Payload del webhook — GeoBase emite

```json
{
  "event":       "enrollment.status_changed",
  "occurred_at": "2026-04-23T15:00:00Z",
  "data": {
    "enrollment_id":      19204,
    "beneficiary_id":     8341,
    "program_id":         12,
    "status_anterior":    "aprobado",
    "status_nuevo":       "finalizado",
    "motivo":             "Cierre de ejercicio fiscal 2026",
    "origen_ref_id":      991
  }
}
```

**Firma:** `X-GeoBase-Signature: hmac-sha256=<hex(HMAC-SHA256(secret, raw_body))>`

### Código esperado en GeoBase

**`app/Observers/EnrollmentObserver.php`** — añadir:
```php
public function updated(Enrollment $enrollment): void
{
    if ($enrollment->wasChanged('status') && $enrollment->origen_sistema === 'spp') {
        dispatch(new SendWebhookToSpp([
            'event'      => 'enrollment.status_changed',
            'occurred_at'=> now()->toISOString(),
            'data'       => [
                'enrollment_id'   => $enrollment->id,
                'beneficiary_id'  => $enrollment->beneficiary_id,
                'program_id'      => $enrollment->program_id,
                'status_anterior' => $enrollment->getOriginal('status'),
                'status_nuevo'    => $enrollment->status,
                'motivo'          => $enrollment->motivo_cambio,
                'origen_ref_id'   => $enrollment->origen_ref_id,
            ],
        ]));
    }
}
```

**`app/Jobs/SendWebhookToSpp.php`** (nuevo):
```php
public function handle(): void
{
    $payload = json_encode($this->data);
    $signature = 'hmac-sha256=' . hash_hmac('sha256', $payload, config('geobase.webhook_secret'));

    Http::withHeaders(['X-GeoBase-Signature' => $signature])
        ->post(config('geobase.spp_webhook_url'), $this->data);
}
```

**Registrar observer en `AppServiceProvider`:**
```php
Enrollment::observe(EnrollmentObserver::class);
```

### Código esperado en dte-spp

**`routes/api/webhooks.php`** (nuevo archivo):
```php
Route::post('/webhooks/geobase', WebhookGeoBaseController::class);
// Sin middleware auth — la firma HMAC es la autenticación
```

**`app/Http/Controllers/Api/WebhookGeoBaseController.php`** (nuevo):
```php
public function __invoke(Request $request): JsonResponse
{
    // 1. Verificar firma
    $signature = $request->header('X-GeoBase-Signature');
    $expected  = 'hmac-sha256=' . hash_hmac('sha256', $request->getContent(), config('geobase.webhook_secret'));

    if (! hash_equals($expected, $signature ?? '')) {
        return response()->json(['error' => 'Firma inválida'], 401);
    }

    // 2. Procesar evento
    $event = $request->input('event');
    $data  = $request->input('data');

    match ($event) {
        'enrollment.status_changed' => $this->handleStatusChange($data),
        default => Log::info('GeoBase webhook evento desconocido', ['event' => $event]),
    };

    return response()->json(['ok' => true]);
}

private function handleStatusChange(array $data): void
{
    $participacion = Participacion::where('geobase_enrollment_id', $data['enrollment_id'])->first();

    if (! $participacion) {
        Log::warning('Webhook GeoBase: enrollment_id no encontrado', $data);
        return;
    }

    $participacion->update(['status_geobase' => $data['status_nuevo']]);

    if (in_array($data['status_nuevo'], ['cancelado', 'finalizado'])) {
        event(new BeneficiarioEstatusActualizado($participacion, $data));
    }
}
```

### Lista de verificación cruzada — Momento 5

| # | Verificar en | Comando / archivo | Resultado esperado |
|---|---|---|---|
| M5-V1 | GeoBase | `grep -rn "EnrollmentObserver" app/Observers/` | Observer existe |
| M5-V2 | GeoBase | `grep -n "Enrollment::observe" app/Providers/AppServiceProvider.php` | Observer registrado |
| M5-V3 | GeoBase | `grep -rn "SendWebhookToSpp" app/Jobs/` | Job existe |
| M5-V4 | GeoBase | `grep -n "hash_hmac.*sha256" app/Jobs/SendWebhookToSpp.php` | Firma HMAC implementada |
| M5-V5 | GeoBase | `grep -n "spp_webhook_url" config/geobase.php` | Config URL de destino existe |
| M5-V6 | dte-spp | `php artisan route:list \| grep webhooks/geobase` | Ruta `POST` existe |
| M5-V7 | dte-spp | `grep -n "hash_equals.*hmac" app/Http/Controllers/Api/WebhookGeoBaseController.php` | Verificación de firma |
| M5-V8 | dte-spp | `grep -n "geobase.webhook_secret" config/geobase.php` | Secret configurado |
| M5-V9 | dte-spp | `grep -rn "BeneficiarioEstatusActualizado" app/` | Evento downstream existe |

---

## Resumen de verificación completa — Integración en 30 comandos

Script que puede ejecutarse en cualquier momento para auditar el estado de la integración:

### En dte-spp
```bash
#!/bin/bash
echo "=== INTEGRACIÓN GEOBASE → dte-spp ==="

echo "--- CONFIG ---"
grep -c "geobase" config/geobase.php                                    && echo "config/geobase.php OK" || echo "FALTA config/geobase.php"

echo "--- CLIENTE ---"
grep -c "validateAddress"    app/Services/GeoBaseClient.php             && echo "M1: validateAddress OK"     || echo "M1: FALTA validateAddress"
grep -c "registerBeneficiary" app/Services/GeoBaseClient.php            && echo "M2: registerBeneficiary OK" || echo "M2: FALTA registerBeneficiary"
grep -c "createEnrollment"   app/Services/GeoBaseClient.php             && echo "M3: createEnrollment OK"    || echo "M3: FALTA createEnrollment"
grep -c "createSnapshot"     app/Services/GeoBaseClient.php             && echo "M4: createSnapshot OK"      || echo "M4: FALTA createSnapshot"

echo "--- LISTENERS ---"
grep -rc "SyncEnrollmentToGeoBase"   app/Listeners/                     && echo "M3: Listener OK"  || echo "M3: FALTA Listener"
grep -rc "GenerateSnapshotForAvance" app/Listeners/                     && echo "M4: Listener OK"  || echo "M4: FALTA Listener"

echo "--- WEBHOOK ---"
php artisan route:list 2>/dev/null | grep -c "webhooks/geobase"         && echo "M5: Ruta webhook OK"   || echo "M5: FALTA ruta webhook"
grep -c "hash_equals" app/Http/Controllers/Api/WebhookGeoBaseController.php && echo "M5: HMAC verify OK" || echo "M5: FALTA HMAC verify"

echo "--- COLUMNAS DB ---"
php artisan migrate:status 2>/dev/null | grep -c "geobase_beneficiary_id" && echo "M2: columna geobase_id OK" || echo "M2: FALTA migración geobase_id"
php artisan migrate:status 2>/dev/null | grep -c "geobase_enrollment_id"  && echo "M3: columna enrollment_id OK" || echo "M3: FALTA migración enrollment_id"
php artisan migrate:status 2>/dev/null | grep -c "geobase_snapshot_id"    && echo "M4: columna snapshot_id OK" || echo "M4: FALTA migración snapshot_id"
```

### En GeoBase
```bash
#!/bin/bash
echo "=== INTEGRACIÓN dte-spp → GEOBASE ==="

echo "--- ENDPOINTS API ---"
php artisan route:list 2>/dev/null | grep -c "validate-address"  && echo "M1: /validate-address OK" || echo "M1: FALTA /validate-address"
php artisan route:list 2>/dev/null | grep -c "api/v1/beneficiaries" && echo "M2: /beneficiaries OK" || echo "M2: FALTA /beneficiaries"
php artisan route:list 2>/dev/null | grep -c "api/v1/enrollments"   && echo "M3: /enrollments OK"   || echo "M3: FALTA /enrollments"
php artisan route:list 2>/dev/null | grep -c "api/v1/snapshots"     && echo "M4: /snapshots OK"     || echo "M4: FALTA /snapshots"

echo "--- OBSERVER + WEBHOOK ---"
grep -rc "EnrollmentObserver"  app/Observers/                      && echo "M5: Observer OK"       || echo "M5: FALTA Observer"
grep -c  "Enrollment::observe" app/Providers/AppServiceProvider.php && echo "M5: Registrado OK"   || echo "M5: FALTA registro observer"
grep -rc "SendWebhookToSpp"    app/Jobs/                           && echo "M5: WebhookJob OK"    || echo "M5: FALTA WebhookJob"

echo "--- MODELO BENEFICIARY ---"
grep -c "'encrypted'" app/Models/Beneficiary.php                   && echo "CURP cifrado OK"      || echo "ALERTA: CURP sin cifrar"

echo "--- SNAPSHOT TYPE (N1-08 prereq) ---"
php artisan migrate:status 2>/dev/null | grep -c "snapshot_type"   && echo "snapshot_type OK"     || echo "FALTA N1-08 antes de M4"
```

---

## Tabla de dependencias entre momentos

```
M1 (validar dirección)
    └─→ M2 (crear beneficiario en GeoBase)
            └─→ M3 (crear enrollment al aprobar)
                    ├─→ M4 (snapshot al registrar avance)  ← requiere también N1-08
                    └─→ M5 (webhook ante cambio de estatus) ← puede implementarse en paralelo
```

| Momento | Puede probarse sin... | Requiere obligatoriamente... |
|---|---|---|
| M1 | M2, M3, M4, M5 | GeoBase corriendo en :8081 + `has_geo_restriction=true` en programa |
| M2 | M3, M4, M5 | M1 completado (o bypass manual con domicilio pre-validado) |
| M3 | M4, M5 | M2 completado (necesita `geobase_beneficiary_id` válido) |
| M4 | M5 | M3 completado + N1-08 (snapshot_type en GeoBase) |
| M5 | nada posterior | M3 completado (enrollment debe existir en GeoBase) |

---

*Roadmap completo de todos los ítems: ver `roadmap_implementacion.md`*
