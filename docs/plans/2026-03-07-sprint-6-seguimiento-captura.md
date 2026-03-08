# Sprint 6: Seguimiento y Captura Periódica — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Implement the complete tracking cycle: operator capture of indicator progress, automatic traffic-light calculation, AI-assisted justifications, evidence attachments, approval state machine, and planner dashboard.

**Architecture:** New `Tracking` domain (`Models/Tracking/`, `Services/Tracking/`, `Livewire/Tracking/`, `routes/web/tracking.php`). Services: `FormulaEvaluatorService` (eval formulas), `SemaforoService` (traffic light), `AvanceEstadoService` (state machine), `JustificacionService` (AI drafts). Builds on existing `metas_periodo` from S5-T4 and `LlmService` for AI.

**Tech Stack:** Laravel 12, Livewire 3, PostgreSQL (JSONB for audit history), Spatie permissions, Laravel Notifications (database channel), `symfony/expression-language` for formula evaluation.

**Baseline:** 288 tests passing, 7 skipped. Zero regressions required.

**Branch strategy:** Each task gets its own feature branch off `desarrollo`, merged back with `Resolves DTE-XX`.

---

## Task 1: Migraciones y modelos para seguimiento (S6-T1)

**Branch:** `feat/S6-T1-migraciones-seguimiento`

**Files:**
- Create: `app/Enums/EstadoAvance.php`
- Create: `app/Enums/ComportamientoVariable.php`
- Create: `database/migrations/2026_03_10_010000_create_notifications_table.php`
- Create: `database/migrations/2026_03_10_010100_add_calendario_to_metas_periodo.php`
- Create: `database/migrations/2026_03_10_010200_create_avances_table.php`
- Create: `database/migrations/2026_03_10_010300_create_avance_variables_table.php`
- Create: `database/migrations/2026_03_10_010400_create_avance_evidencias_table.php`
- Create: `database/migrations/2026_03_10_010500_create_desbloqueos_table.php`
- Create: `app/Models/Tracking/Avance.php`
- Create: `app/Models/Tracking/AvanceVariable.php`
- Create: `app/Models/Tracking/AvanceEvidencia.php`
- Create: `app/Models/Tracking/Desbloqueo.php`
- Create: `tests/Feature/Tracking/ModelosTrackingTest.php`
- Modify: `app/Models/Mml/MetaPeriodo.php` (add `fecha_apertura`, `fecha_cierre` to fillable/casts, add `avance()` relation)
- Modify: `app/Models/Mml/Indicador.php` (add `avances()` relation)

### Step 1: Create branch

```bash
git checkout desarrollo && git pull
git checkout -b feat/S6-T1-migraciones-seguimiento
```

### Step 2: Create enums

Create `app/Enums/EstadoAvance.php`:

```php
<?php

namespace App\Enums;

enum EstadoAvance: string
{
    case EN_CAPTURA = 'en_captura';
    case EN_REVISION = 'en_revision';
    case OBSERVADO = 'observado';
    case APROBADO = 'aprobado';
    case VENCIDO = 'vencido';

    public function label(): string
    {
        return match($this) {
            self::EN_CAPTURA => 'En captura',
            self::EN_REVISION => 'En revisión',
            self::OBSERVADO => 'Observado',
            self::APROBADO => 'Aprobado',
            self::VENCIDO => 'Vencido',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::EN_CAPTURA => 'bg-blue-100 text-blue-800',
            self::EN_REVISION => 'bg-yellow-100 text-yellow-800',
            self::OBSERVADO => 'bg-orange-100 text-orange-800',
            self::APROBADO => 'bg-green-100 text-green-800',
            self::VENCIDO => 'bg-red-100 text-red-800',
        };
    }

    public function esEditable(): bool
    {
        return $this === self::EN_CAPTURA;
    }
}
```

Create `app/Enums/ComportamientoVariable.php`:

```php
<?php

namespace App\Enums;

enum ComportamientoVariable: string
{
    case ACUMULABLE = 'acumulable';
    case CONTINUA = 'continua';

    public function label(): string
    {
        return match($this) {
            self::ACUMULABLE => 'Acumulable',
            self::CONTINUA => 'Continua',
        };
    }
}
```

### Step 3: Create notifications table migration

```bash
./vendor/bin/sail artisan make:notifications-table
```

Or create `database/migrations/2026_03_10_010000_create_notifications_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
```

### Step 4: Create migration `add_calendario_to_metas_periodo`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metas_periodo', function (Blueprint $table) {
            $table->date('fecha_apertura')->nullable()->after('activo');
            $table->date('fecha_cierre')->nullable()->after('fecha_apertura');
        });
    }

    public function down(): void
    {
        Schema::table('metas_periodo', function (Blueprint $table) {
            $table->dropColumn(['fecha_apertura', 'fecha_cierre']);
        });
    }
};
```

### Step 5: Create migration `create_avances_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_periodo_id')
                ->constrained('metas_periodo')->cascadeOnDelete();
            $table->foreignId('indicador_id')
                ->constrained('indicadores')->cascadeOnDelete();
            $table->decimal('resultado', 12, 4)->nullable();
            $table->string('semaforo_calculado', 10)->nullable();
            $table->string('semaforo_ajustado', 10)->nullable();
            $table->text('justificacion_ia')->nullable();
            $table->text('justificacion_final')->nullable();
            $table->string('estado', 20)->default('en_captura');
            $table->jsonb('historial_observaciones')->default('[]');
            $table->timestamp('congelado_at')->nullable();
            $table->foreignId('capturado_por')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['meta_periodo_id', 'indicador_id']);
            $table->index('estado');
            $table->index('indicador_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avances');
    }
};
```

### Step 6: Create migration `create_avance_variables_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->foreignId('indicador_variable_id')
                ->constrained('indicador_variables')->cascadeOnDelete();
            $table->decimal('valor', 12, 4);
            $table->decimal('valor_acumulado', 12, 4)->nullable();
            $table->timestamps();

            $table->unique(['avance_id', 'indicador_variable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_variables');
    }
};
```

### Step 7: Create migration `create_avance_evidencias_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avance_evidencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->string('nombre_archivo');
            $table->string('ruta_archivo');
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('hash_archivo', 64);
            $table->string('nombre_documento');
            $table->string('area_generadora')->nullable();
            $table->date('fecha_documento')->nullable();
            $table->foreignId('subido_por')
                ->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('avance_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avance_evidencias');
    }
};
```

### Step 8: Create migration `create_desbloqueos_table`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('desbloqueos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avance_id')
                ->constrained('avances')->cascadeOnDelete();
            $table->text('motivo');
            $table->foreignId('solicitado_por')
                ->constrained('users')->cascadeOnDelete();
            $table->foreignId('resuelto_por')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado', 20)->default('pendiente');
            $table->text('resolucion')->nullable();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();

            $table->index(['avance_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('desbloqueos');
    }
};
```

### Step 9: Create models

Create `app/Models/Tracking/Avance.php`:

```php
<?php

namespace App\Models\Tracking;

use App\Enums\EstadoAvance;
use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avance extends Model
{
    protected $fillable = [
        'meta_periodo_id', 'indicador_id', 'resultado',
        'semaforo_calculado', 'semaforo_ajustado',
        'justificacion_ia', 'justificacion_final',
        'estado', 'historial_observaciones', 'congelado_at', 'capturado_por',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => 'decimal:4',
            'estado' => EstadoAvance::class,
            'historial_observaciones' => 'array',
            'congelado_at' => 'datetime',
        ];
    }

    public function estaCongelado(): bool
    {
        return $this->congelado_at !== null;
    }

    public function metaPeriodo(): BelongsTo
    {
        return $this->belongsTo(MetaPeriodo::class);
    }

    public function indicador(): BelongsTo
    {
        return $this->belongsTo(Indicador::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(AvanceVariable::class);
    }

    public function evidencias(): HasMany
    {
        return $this->hasMany(AvanceEvidencia::class);
    }

    public function desbloqueos(): HasMany
    {
        return $this->hasMany(Desbloqueo::class);
    }

    public function capturador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'capturado_por');
    }
}
```

Create `app/Models/Tracking/AvanceVariable.php`:

```php
<?php

namespace App\Models\Tracking;

use App\Models\Mml\IndicadorVariable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceVariable extends Model
{
    protected $fillable = [
        'avance_id', 'indicador_variable_id', 'valor', 'valor_acumulado',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:4',
            'valor_acumulado' => 'decimal:4',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function indicadorVariable(): BelongsTo
    {
        return $this->belongsTo(IndicadorVariable::class);
    }
}
```

Create `app/Models/Tracking/AvanceEvidencia.php`:

```php
<?php

namespace App\Models\Tracking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvanceEvidencia extends Model
{
    protected $fillable = [
        'avance_id', 'nombre_archivo', 'ruta_archivo', 'mime_type',
        'tamano_bytes', 'hash_archivo', 'nombre_documento',
        'area_generadora', 'fecha_documento', 'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
            'fecha_documento' => 'date',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
```

Create `app/Models/Tracking/Desbloqueo.php`:

```php
<?php

namespace App\Models\Tracking;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Desbloqueo extends Model
{
    protected $fillable = [
        'avance_id', 'motivo', 'solicitado_por', 'resuelto_por',
        'estado', 'resolucion', 'resuelto_at',
    ];

    protected function casts(): array
    {
        return [
            'resuelto_at' => 'datetime',
        ];
    }

    public function avance(): BelongsTo
    {
        return $this->belongsTo(Avance::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function resolutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resuelto_por');
    }
}
```

### Step 10: Update existing models

In `app/Models/Mml/MetaPeriodo.php` — add to fillable: `'fecha_apertura', 'fecha_cierre'`. Add to casts: `'fecha_apertura' => 'date', 'fecha_cierre' => 'date'`. Add relation:

```php
public function avance(): \Illuminate\Database\Eloquent\Relations\HasOne
{
    return $this->hasOne(\App\Models\Tracking\Avance::class);
}
```

In `app/Models/Mml/Indicador.php` — add relation:

```php
public function avances(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\Tracking\Avance::class);
}
```

### Step 11: Run migrations

```bash
./vendor/bin/sail artisan migrate
```

### Step 12: Write tests

Create `tests/Feature/Tracking/ModelosTrackingTest.php`:

```php
<?php

namespace Tests\Feature\Tracking;

use App\Enums\ComportamientoVariable;
use App\Enums\EstadoAvance;
use App\Enums\TipoNivelMir;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use App\Models\Tracking\AvanceVariable;
use App\Models\Tracking\Desbloqueo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelosTrackingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Indicador $indicador;
    private MetaPeriodo $metaPeriodo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();

        $programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Test', 'orden' => 1,
        ]);

        $this->indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'Tasa',
            'tipo' => 'estrategico', 'dimension' => 'eficacia',
            'frecuencia' => 'trimestral', 'meta' => 100,
            'activo_seguimiento' => true, 'orden' => 1,
        ]);

        $this->metaPeriodo = MetaPeriodo::create([
            'indicador_id' => $this->indicador->id,
            'periodo' => 1, 'meta_periodo' => 25,
            'ejercicio_fiscal' => 2026, 'activo' => true,
        ]);
    }

    public function test_crear_avance_con_estado(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $this->assertEquals(EstadoAvance::EN_CAPTURA, $avance->estado);
        $this->assertFalse($avance->estaCongelado());
    }

    public function test_avance_congelado(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::APROBADO->value,
            'congelado_at' => now(),
            'capturado_por' => $this->user->id,
        ]);

        $this->assertTrue($avance->estaCongelado());
    }

    public function test_avance_relaciones(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $this->assertEquals($this->metaPeriodo->id, $avance->metaPeriodo->id);
        $this->assertEquals($this->indicador->id, $avance->indicador->id);
        $this->assertEquals($this->user->id, $avance->capturador->id);
    }

    public function test_avance_variable_con_acumulado(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $variable = IndicadorVariable::create([
            'indicador_id' => $this->indicador->id,
            'simbolo' => 'A', 'nombre' => 'Var A', 'orden' => 1,
        ]);

        $av = AvanceVariable::create([
            'avance_id' => $avance->id,
            'indicador_variable_id' => $variable->id,
            'valor' => 50, 'valor_acumulado' => 50,
        ]);

        $this->assertEquals(50, $av->valor);
        $this->assertEquals(50, $av->valor_acumulado);
        $this->assertEquals($avance->id, $av->avance->id);
    }

    public function test_avance_evidencia(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $evidencia = AvanceEvidencia::create([
            'avance_id' => $avance->id,
            'nombre_archivo' => 'doc.pdf', 'ruta_archivo' => 'evidencias/1/doc.pdf',
            'mime_type' => 'application/pdf', 'tamano_bytes' => 1024,
            'hash_archivo' => str_repeat('a', 64),
            'nombre_documento' => 'Padrón', 'subido_por' => $this->user->id,
        ]);

        $this->assertEquals($avance->id, $evidencia->avance->id);
        $this->assertEquals(1, $avance->evidencias()->count());
    }

    public function test_desbloqueo(): void
    {
        $avance = Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::APROBADO->value,
            'congelado_at' => now(),
            'capturado_por' => $this->user->id,
        ]);

        $desbloqueo = Desbloqueo::create([
            'avance_id' => $avance->id,
            'motivo' => 'Error en captura',
            'solicitado_por' => $this->user->id,
            'estado' => 'pendiente',
        ]);

        $this->assertEquals($avance->id, $desbloqueo->avance->id);
        $this->assertEquals($this->user->id, $desbloqueo->solicitante->id);
    }

    public function test_meta_periodo_tiene_avance(): void
    {
        Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $this->assertNotNull($this->metaPeriodo->avance);
    }

    public function test_indicador_tiene_avances(): void
    {
        Avance::create([
            'meta_periodo_id' => $this->metaPeriodo->id,
            'indicador_id' => $this->indicador->id,
            'estado' => EstadoAvance::EN_CAPTURA->value,
            'capturado_por' => $this->user->id,
        ]);

        $this->assertEquals(1, $this->indicador->avances()->count());
    }

    public function test_estado_avance_enum(): void
    {
        $this->assertEquals('En captura', EstadoAvance::EN_CAPTURA->label());
        $this->assertTrue(EstadoAvance::EN_CAPTURA->esEditable());
        $this->assertFalse(EstadoAvance::APROBADO->esEditable());
        $this->assertCount(5, EstadoAvance::cases());
    }

    public function test_comportamiento_variable_enum(): void
    {
        $this->assertEquals('Acumulable', ComportamientoVariable::ACUMULABLE->label());
        $this->assertCount(2, ComportamientoVariable::cases());
    }
}
```

### Step 13: Run tests and commit

```bash
./vendor/bin/sail artisan test
git add -A
git commit -m "feat(S6-T1): tracking domain models and migrations

- Extend metas_periodo with fecha_apertura/fecha_cierre
- Create avances, avance_variables, avance_evidencias, desbloqueos tables
- Models: Avance, AvanceVariable, AvanceEvidencia, Desbloqueo in Tracking domain
- Enums: EstadoAvance (5 states), ComportamientoVariable
- Notifications table for Laravel notifications
- 10 tests covering models, relations, enums

Resolves DTE-XX"
```

---

## Task 2: Calendario de captura y notificaciones (S6-T2)

**Branch:** `feat/S6-T2-calendario-notificaciones`

**Files:**
- Create: `app/Services/Tracking/CalendarioService.php`
- Create: `app/Console/Commands/AbrirPeriodosCaptura.php`
- Create: `app/Console/Commands/CerrarPeriodosVencidos.php`
- Create: `app/Notifications/PeriodoAbiertoNotification.php`
- Create: `app/Notifications/AvanceVencidoNotification.php`
- Create: `app/Livewire/Tracking/MisIndicadoresPendientes.php`
- Create: `app/Livewire/Tracking/IndicadoresVencidos.php`
- Create: `resources/views/livewire/tracking/mis-indicadores-pendientes.blade.php`
- Create: `resources/views/livewire/tracking/indicadores-vencidos.blade.php`
- Create: `routes/web/tracking.php`
- Create: `tests/Feature/Tracking/CalendarioTest.php`
- Modify: `routes/web.php` (require tracking.php)
- Modify: `routes/console.php` (schedule commands)

### Key implementation details:

**CalendarioService::calcularFechas():**
```php
public function calcularFechas(int $ejercicio, FrecuenciaMedicion $frecuencia): array
{
    $periodos = (new CalendarizacionService())->numeroPeriodos($frecuencia);
    $result = [];
    for ($p = 1; $p <= $periodos; $p++) {
        $mesInicio = $this->mesInicioPeriodo($frecuencia, $p);
        $apertura = Carbon::create($ejercicio, $mesInicio, 1);
        $cierre = $apertura->copy()->addDays(14); // 15 days window
        $result[] = ['periodo' => $p, 'fecha_apertura' => $apertura->toDateString(), 'fecha_cierre' => $cierre->toDateString()];
    }
    return $result;
}
```

**AbrirPeriodosCaptura command:** Queries `metas_periodo` where `fecha_apertura <= today`, `activo = true`, no existing avance. Creates `Avance` with `EN_CAPTURA` state. Notifies users with `capturar_avance` in the program's team.

**CerrarPeriodosVencidos command:** Queries avances via meta_periodo where `fecha_cierre < today` and estado NOT IN (APROBADO, VENCIDO). Sets estado = VENCIDO.

**Schedule in `routes/console.php`:**
```php
Schedule::command('mir:abrir-periodos')->dailyAt('06:00');
Schedule::command('mir:cerrar-vencidos')->dailyAt('23:00');
```

**Route file `routes/web/tracking.php`:**
```php
Route::prefix('seguimiento')
    ->middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->group(function () {
        Route::get('/pendientes', MisIndicadoresPendientes::class)->name('tracking.pendientes');
        Route::get('/vencidos', IndicadoresVencidos::class)->name('tracking.vencidos');
    });
```

**Tests (~8):** command creates avances, command marks vencidos, no duplicate avances, notifications sent, calcularFechas returns correct dates per frequency.

### Commit message:
```
feat(S6-T2): capture calendar commands and notifications

- CalendarioService with date calculation per frequency
- mir:abrir-periodos and mir:cerrar-vencidos scheduled commands
- PeriodoAbiertoNotification and AvanceVencidoNotification
- MisIndicadoresPendientes and IndicadoresVencidos views
- routes/web/tracking.php with seguimiento routes
- 8 tests

Resolves DTE-XX
```

---

## Task 3: Formulario de captura de avance (S6-T3)

**Branch:** `feat/S6-T3-formulario-captura-avance`

**Files:**
- Create: `app/Services/Tracking/FormulaEvaluatorService.php`
- Create: `app/Services/Tracking/SemaforoService.php`
- Create: `app/Livewire/Tracking/CapturaAvance.php`
- Create: `resources/views/livewire/tracking/captura-avance.blade.php`
- Create: `tests/Feature/Tracking/FormulaEvaluatorTest.php`
- Create: `tests/Feature/Tracking/SemaforoTest.php`
- Create: `tests/Feature/Tracking/CapturaAvanceTest.php`
- Modify: `routes/web/tracking.php` (add capture route)

### Key implementation details:

**Install expression-language:**
```bash
./vendor/bin/sail composer require symfony/expression-language
```

**FormulaEvaluatorService:**
```php
<?php

namespace App\Services\Tracking;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class FormulaEvaluatorService
{
    public function evaluar(string $formula, array $variables): ?float
    {
        try {
            // Normalize MIR notation: "(A/B) x 100" → "(A/B) * 100"
            $expr = preg_replace('/\bx\b/i', '*', $formula);
            $expr = preg_replace('/[^\w\s\+\-\*\/\(\)\.\,]/', '', $expr);

            $lang = new ExpressionLanguage();
            $result = $lang->evaluate($expr, $variables);

            return is_numeric($result) ? round((float) $result, 4) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
```

**SemaforoService:**
```php
<?php

namespace App\Services\Tracking;

use App\Enums\SentidoIndicador;
use App\Models\Mml\Indicador;

class SemaforoService
{
    public function calcular(float $resultado, Indicador $indicador): string
    {
        $sentido = $indicador->sentido ?? SentidoIndicador::ASCENDENTE;

        return match ($sentido) {
            SentidoIndicador::ASCENDENTE => $this->calcularAscendente($resultado, $indicador),
            SentidoIndicador::DESCENDENTE => $this->calcularDescendente($resultado, $indicador),
            SentidoIndicador::REGULAR => $this->calcularRegular($resultado, $indicador),
        };
    }

    private function calcularAscendente(float $resultado, Indicador $ind): string
    {
        if ($ind->rango_verde_min !== null && $resultado >= $ind->rango_verde_min) return 'verde';
        if ($ind->rango_amarillo_min !== null && $resultado >= $ind->rango_amarillo_min) return 'amarillo';
        return 'rojo';
    }

    private function calcularDescendente(float $resultado, Indicador $ind): string
    {
        if ($ind->rango_verde_max !== null && $resultado <= $ind->rango_verde_max) return 'verde';
        if ($ind->rango_amarillo_max !== null && $resultado <= $ind->rango_amarillo_max) return 'amarillo';
        return 'rojo';
    }

    private function calcularRegular(float $resultado, Indicador $ind): string
    {
        if ($ind->rango_verde_min !== null && $ind->rango_verde_max !== null
            && $resultado >= $ind->rango_verde_min && $resultado <= $ind->rango_verde_max) return 'verde';
        if ($ind->rango_amarillo_min !== null && $ind->rango_amarillo_max !== null
            && $resultado >= $ind->rango_amarillo_min && $resultado <= $ind->rango_amarillo_max) return 'amarillo';
        return 'rojo';
    }
}
```

**CapturaAvance Livewire:** Mounted with Avance. Shows dynamic fields per variable. `calcular()` evaluates formula and determines traffic light on `wire:change`. `guardar()` persists AvanceVariable records + Avance.resultado + semaforo_calculado. Requires justificacion if amarillo/rojo.

**Tests (~10):** FormulaEvaluator (basic, division, invalid), Semaforo (ascendente, descendente, regular), CapturaAvance (renders, calculates, saves, permission check, frozen check).

### Commit:
```
feat(S6-T3): formula evaluator, traffic light, and capture form

- FormulaEvaluatorService using symfony/expression-language
- SemaforoService respecting sentido (ascendente/descendente/regular)
- CapturaAvance Livewire with dynamic variable fields
- Accumulation logic for acumulable variables
- 10 tests

Resolves DTE-XX
```

---

## Task 4: Generación de justificaciones con IA (S6-T4)

**Branch:** `feat/S6-T4-justificaciones-ia`

**Files:**
- Create: `app/Services/Tracking/JustificacionService.php`
- Create: `resources/views/prompts/tracking/justificar-avance.blade.php`
- Create: `tests/Feature/Tracking/JustificacionTest.php`
- Modify: `app/Livewire/Tracking/CapturaAvance.php` (integrate AI justification)

### Key implementation:

**Prompt template** at `resources/views/prompts/tracking/justificar-avance.blade.php`:
```blade
Eres un asistente de seguimiento de indicadores para programas presupuestarios gubernamentales.

Genera una justificación técnica para el siguiente indicador que presenta desviación respecto a su meta.

## Indicador
- **Nombre:** {{ $indicador->nombre }}
- **Nivel MIR:** {{ ucfirst($nivel->tipo_nivel) }}
- **Resumen narrativo:** {{ $nivel->resumen_narrativo }}

## Resultado
- **Meta del período:** {{ $metaPeriodo }}
- **Resultado obtenido:** {{ $resultado }}
- **Desviación:** {{ $desviacion }}%
- **Semáforo:** {{ $semaforo }}

@if($supuestos)
## Supuestos de la MIR (Columna 4)
{{ $supuestos }}

Analiza si alguno de estos supuestos pudo no haberse cumplido y contribuir a la desviación.
@else
No se encontraron supuestos definidos para este nivel de la MIR.
@endif

@if($historial)
## Historial de períodos anteriores
@foreach($historial as $h)
- Período {{ $h['periodo'] }}: resultado {{ $h['resultado'] }}, semáforo {{ $h['semaforo'] }}
@endforeach
@endif

## Instrucciones
- Genera una justificación técnica de 2-3 párrafos
- Basa tu análisis EXCLUSIVAMENTE en los supuestos de la MIR y los datos numéricos proporcionados
- NO inventes contexto externo ni datos no proporcionados
- Si los supuestos están vacíos, indica que no se cuenta con supuestos registrados
- Sé preciso con las cifras de desviación
```

**JustificacionService:** calls `LlmService::suggest()` with rendered prompt. Returns draft string.

**Tests (~5):** mock LlmService, generate with supuestos, without supuestos, saves dual fields, returns empty on error.

### Commit:
```
feat(S6-T4): AI-generated justifications for deviations

- JustificacionService using LlmService::suggest()
- Prompt template with MIR context and numerical deviation
- Integration with CapturaAvance for auto-generation
- 5 tests

Resolves DTE-XX
```

---

## Task 5: Adjuntar evidencia (S6-T5)

**Branch:** `feat/S6-T5-adjuntar-evidencia`

**Files:**
- Create: `app/Livewire/Tracking/EvidenciaAvance.php`
- Create: `resources/views/livewire/tracking/evidencia-avance.blade.php`
- Create: `app/Http/Controllers/Tracking/EvidenciaController.php`
- Create: `tests/Feature/Tracking/EvidenciaTest.php`
- Modify: `routes/web/tracking.php`

### Key implementation:

**Upload:** Livewire `WithFileUploads`, validate mimes (pdf,xlsx,xls,jpg,png,doc,docx), max 10MB. Store to `evidencias/{avance_id}/` on `local` disk. Hash with `hash_file('sha256')`.

**Download controller:** `EvidenciaController@download` checks `can:revisar_avance` OR user is capturador. Returns `Storage::download()`.

**MIR validation:** Compare `nombre_documento` against `$avance->indicador->mediosVerificacion->pluck('nombre')`. If no match, show warning (not block).

**Tests (~6):** upload creates record with hash, download with permission, download without permission denied, MIR warning, delete only if not frozen, file stored privately.

### Commit:
```
feat(S6-T5): evidence attachments with SHA-256 integrity

- EvidenciaAvance Livewire with upload to private storage
- SHA-256 hash for file integrity
- EvidenciaController for secure downloads
- MIR medio verification name matching
- 6 tests

Resolves DTE-XX
```

---

## Task 6: Máquina de estados (S6-T6)

**Branch:** `feat/S6-T6-maquina-estados-avance`

**Files:**
- Create: `app/Services/Tracking/AvanceEstadoService.php`
- Create: `app/Exceptions/TransicionInvalidaException.php`
- Create: `app/Notifications/AvanceObservadoNotification.php`
- Create: `app/Notifications/AvanceEnRevisionNotification.php`
- Create: `app/Livewire/Tracking/FlujosAvance.php`
- Create: `resources/views/livewire/tracking/flujos-avance.blade.php`
- Create: `resources/views/livewire/tracking/partials/timeline-observaciones.blade.php`
- Create: `tests/Feature/Tracking/AvanceEstadoTest.php`

### Key implementation:

**AvanceEstadoService:**
```php
<?php

namespace App\Services\Tracking;

use App\Enums\EstadoAvance;
use App\Exceptions\TransicionInvalidaException;
use App\Models\Tracking\Avance;
use App\Models\User;

class AvanceEstadoService
{
    private const TRANSICIONES = [
        'en_captura' => ['en_revision', 'vencido'],
        'en_revision' => ['observado', 'aprobado', 'vencido'],
        'observado' => ['en_captura'],
        'aprobado' => [],
        'vencido' => [],
    ];

    public function transicionar(Avance $avance, EstadoAvance $nuevoEstado, User $usuario, ?string $observacion = null): void
    {
        $estadoActual = $avance->estado->value;
        $permitidos = self::TRANSICIONES[$estadoActual] ?? [];

        if (! in_array($nuevoEstado->value, $permitidos)) {
            throw new TransicionInvalidaException(
                "Transición inválida: {$estadoActual} → {$nuevoEstado->value}"
            );
        }

        $estadoAnterior = $avance->estado;
        $avance->estado = $nuevoEstado;

        if ($nuevoEstado === EstadoAvance::APROBADO) {
            $avance->congelado_at = now();
        }

        // Append to historial
        $historial = $avance->historial_observaciones ?? [];
        $historial[] = [
            'fecha' => now()->toISOString(),
            'usuario_id' => $usuario->id,
            'usuario_nombre' => $usuario->name,
            'accion' => $nuevoEstado->value,
            'estado_anterior' => $estadoAnterior->value,
            'estado_nuevo' => $nuevoEstado->value,
            'observacion' => $observacion,
        ];
        $avance->historial_observaciones = $historial;

        $avance->save();
    }
}
```

**Tests (~10):** valid transitions, invalid throws exception, historial appended, congelado_at on approve, permissions checked in Livewire.

### Commit:
```
feat(S6-T6): advance state machine with audit trail

- AvanceEstadoService with controlled transitions
- TransicionInvalidaException for invalid state changes
- JSONB historial_observaciones append-only
- Notifications for observed/review states
- Timeline partial for observation history
- 10 tests

Resolves DTE-XX
```

---

## Task 7: Congelamiento y desbloqueo (S6-T7)

**Branch:** `feat/S6-T7-congelamiento-desbloqueo`

**Files:**
- Create: `app/Livewire/Tracking/SolicitarDesbloqueo.php`
- Create: `app/Livewire/Tracking/GestionarDesbloqueos.php`
- Create: `resources/views/livewire/tracking/solicitar-desbloqueo.blade.php`
- Create: `resources/views/livewire/tracking/gestionar-desbloqueos.blade.php`
- Create: `tests/Feature/Tracking/DesbloqueoTest.php`
- Modify: `routes/web/tracking.php`
- Modify: `app/Livewire/Tracking/CapturaAvance.php` (check estaCongelado)
- Modify: `app/Livewire/Tracking/EvidenciaAvance.php` (check estaCongelado)

### Key implementation:

**SolicitarDesbloqueo:** Operator submits motivo. Creates Desbloqueo with estado=pendiente. Only one pending per avance.

**GestionarDesbloqueos:** Admin view. Lists pending requests. `aprobar()` sets `congelado_at=null`, estado=EN_CAPTURA, adds historial. `rechazar()` sets estado=rechazado with resolucion.

**Freeze enforcement:** CapturaAvance and EvidenciaAvance check `$avance->estaCongelado()` before any write operation.

**Tests (~7):** frozen blocks edit, request created, approve unfreezes, reject keeps frozen, only one pending, only admin can approve, historial updated.

### Commit:
```
feat(S6-T7): freeze enforcement and exceptional unlock flow

- Frozen avance blocks all edits
- SolicitarDesbloqueo for operators
- GestionarDesbloqueos for admins
- Approve unfreezes and resets to EN_CAPTURA
- 7 tests

Resolves DTE-XX
```

---

## Task 8: Vista de seguimiento planeador (S6-T8)

**Branch:** `feat/S6-T8-vista-seguimiento-planeador`

**Files:**
- Create: `app/Livewire/Tracking/PanelSeguimiento.php`
- Create: `resources/views/livewire/tracking/panel-seguimiento.blade.php`
- Create: `tests/Feature/Tracking/PanelSeguimientoTest.php`
- Modify: `routes/web/tracking.php`
- Modify: `resources/views/navigation-menu.blade.php` (add Seguimiento link)

### Key implementation:

**PanelSeguimiento:** Queries programas for current team. Joins mir_niveles → indicadores → metasPeriodo → avance. Includes UR Coadyuvante niveles (where `mir_niveles.team_id = currentTeam`). Filterable by programa, estado, semaforo.

**Expandable rows:** Click to show variables, justification, evidencias, historial.

**Navigation:** Add "Seguimiento" link after "Importaciones" in nav, with `can('revisar_avance')` gate.

**Tests (~5):** team isolation, filters work, includes coadyuvante niveles, permission required, empty state.

### Commit:
```
feat(S6-T8): planner tracking dashboard with Multi-UR isolation

- PanelSeguimiento with expandable indicator rows
- Filters: programa, estado, semaforo
- Multi-UR isolation via team_id
- Navigation link with permission gate
- 5 tests

Resolves DTE-XX
```

---

## Execution Summary

| Task | Branch | Tests | Key Deliverables |
|------|--------|:---:|-----------------|
| T1 | `feat/S6-T1-migraciones-seguimiento` | ~10 | Enums, migrations, 4 models, relations |
| T2 | `feat/S6-T2-calendario-notificaciones` | ~8 | CalendarioService, 2 commands, 2 notifications, 2 views |
| T3 | `feat/S6-T3-formulario-captura-avance` | ~10 | FormulaEvaluator, Semaforo, CapturaAvance |
| T4 | `feat/S6-T4-justificaciones-ia` | ~5 | JustificacionService, prompt, LlmService integration |
| T5 | `feat/S6-T5-adjuntar-evidencia` | ~6 | EvidenciaAvance, controller, SHA-256 |
| T6 | `feat/S6-T6-maquina-estados-avance` | ~10 | AvanceEstadoService, notifications, timeline |
| T7 | `feat/S6-T7-congelamiento-desbloqueo` | ~7 | Freeze enforcement, desbloqueo flow |
| T8 | `feat/S6-T8-vista-seguimiento-planeador` | ~5 | PanelSeguimiento, nav link |

**Execution order:** T1 → T2 ∥ T5 → T3 → T4 ∥ T6 → T7 → T8

**Estimated new tests:** ~61
**Expected baseline after sprint:** ~349 tests passing
