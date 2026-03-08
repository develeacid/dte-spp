# Sprint 16: Auditoría / Activity Log — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Instalar spatie/laravel-activitylog y aplicar LogsActivity a modelos críticos para registrar cambios. Crear una UI admin para consultar el log de auditoría con filtros.

**Architecture:** Paquete `spatie/laravel-activitylog` con trait `LogsActivity` en modelos selectos. Configuración `tap()` para especificar atributos monitoreados y `logOnlyDirty`. Nuevo Livewire component en `/admin/auditoria` con filtros por modelo, usuario y rango de fechas.

**Tech Stack:** Laravel 12, Livewire 3, Spatie Activity Log, Spatie Permission, PostgreSQL

**Modelos auditados:**
- `User` — cambios de rol/permiso, activación, desactivación
- `PedPlan`, `PedEje`, `PedTema`, `PedObjetivoEstrategico` — estructura PED
- `MirNivel`, `Indicador` — MIR e indicadores
- `EvaluacionPrograma` — evaluaciones

**Excluidos (ya tienen tracking propio):**
- `Avance` — tiene `historial_observaciones` JSONB
- `LlmLog` — es en sí un log de auditoría
- `MetaPeriodo` — alto volumen, bajo valor de auditoría

---

### Task 1: Instalar spatie/laravel-activitylog, publicar migración, ejecutar migrate

**Files:**
- Modify: `composer.json` (via composer require)
- Create: `database/migrations/xxxx_create_activity_log_table.php` (via vendor:publish)
- Create: `config/activitylog.php` (via vendor:publish)

**Step 1: Instalar el paquete**

```bash
./vendor/bin/sail composer require spatie/laravel-activitylog
```

**Step 2: Publicar la migración y config**

```bash
./vendor/bin/sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
./vendor/bin/sail artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"
```

**Step 3: Ejecutar la migración**

```bash
./vendor/bin/sail artisan migrate
```

**Step 4: Verificar la instalación**

```bash
./vendor/bin/sail artisan tinker --execute="echo class_exists(\Spatie\Activitylog\Models\Activity::class) ? 'OK' : 'FAIL';"
```

**Commit:**
```
feat(audit): install spatie/laravel-activitylog package

Resolves DTE-XX

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 2: Agregar LogsActivity trait al modelo User con opciones personalizadas

**Files:**
- Modify: `app/Models/User.php`
- Create: `tests/Feature/Admin/AuditUserTest.php`

**Step 1: Modificar User.php**

Agregar el trait y la configuración `tap()`. Solo auditar atributos relevantes, nunca password ni tokens:

En `app/Models/User.php`, agregar el import:

```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
```

Agregar el trait en la clase:

```php
use LogsActivity;
```

Agregar el método de configuración:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['name', 'email', 'active', 'activated_at'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "User {$eventName}");
}
```

> **Nota:** `password`, `remember_token`, `two_factor_secret`, `two_factor_recovery_codes`, `invitation_token` quedan EXCLUIDOS del log por seguridad.

**Step 2: Crear test**

En `tests/Feature/Admin/AuditUserTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditUserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_creation_is_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'name' => 'Test Audit User',
            'email' => 'audit@test.com',
        ]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('User created', $activity->description);
    }

    public function test_user_update_logs_only_dirty_attributes(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user->update(['name' => 'Nombre Cambiado']);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('name', $activity->properties['attributes']);
        $this->assertEquals('Nombre Cambiado', $activity->properties['attributes']['name']);
    }

    public function test_user_activation_change_is_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['active' => true]);

        $user->update(['active' => false]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('active', $activity->properties['attributes']);
        $this->assertFalse($activity->properties['attributes']['active']);
    }

    public function test_password_is_not_logged(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $user->update(['password' => 'new-password-123']);

        $activities = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->get();

        foreach ($activities as $activity) {
            $this->assertArrayNotHasKey('password', $activity->properties['attributes'] ?? []);
        }
    }
}
```

**Run tests:**
```bash
./vendor/bin/sail artisan test --filter=AuditUserTest
```

**Commit:**
```
feat(audit): add LogsActivity trait to User model

Log name, email, active, activated_at changes. Exclude password
and sensitive tokens for security.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 3: Agregar LogsActivity trait a modelos PED (PedPlan, PedEje, PedTema, PedObjetivoEstrategico)

**Files:**
- Modify: `app/Models/PedPlan.php`
- Modify: `app/Models/PedEje.php`
- Modify: `app/Models/PedTema.php`
- Modify: `app/Models/PedObjetivoEstrategico.php`
- Create: `tests/Feature/Admin/AuditPedTest.php`

**Step 1: Modificar PedPlan.php**

Agregar imports:
```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
```

Agregar trait:
```php
use LogsActivity;
```

Agregar configuración:
```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['nombre', 'nivel_gobierno', 'periodo_inicio', 'periodo_fin', 'activo'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "PedPlan {$eventName}");
}
```

**Step 2: Modificar PedEje.php**

Mismos imports y trait. Configuración:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['ped_plan_id', 'numero', 'nombre', 'descripcion'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "PedEje {$eventName}");
}
```

**Step 3: Modificar PedTema.php**

Mismos imports y trait. Configuración:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['ped_eje_id', 'numero', 'nombre', 'descripcion'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "PedTema {$eventName}");
}
```

**Step 4: Modificar PedObjetivoEstrategico.php**

Mismos imports y trait. Configuración:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['ped_tema_id', 'clave', 'descripcion'])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "PedObjetivoEstrategico {$eventName}");
}
```

**Step 5: Crear test**

En `tests/Feature/Admin/AuditPedTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\PedEje;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditPedTest extends TestCase
{
    use RefreshDatabase;

    public function test_ped_plan_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test 2026-2033',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $activity = Activity::where('subject_type', PedPlan::class)
            ->where('subject_id', $plan->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('PedPlan created', $activity->description);
    }

    public function test_ped_plan_update_logs_only_dirty(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Original',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => false,
        ]);

        Activity::query()->delete(); // Clear creation log

        $plan->update(['activo' => true]);

        $activity = Activity::where('subject_type', PedPlan::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('activo', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('nombre', $activity->properties['attributes']);
    }

    public function test_ped_eje_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => 1,
            'nombre' => 'Eje de prueba',
            'descripcion' => 'Descripción del eje',
        ]);

        $activity = Activity::where('subject_type', PedEje::class)
            ->where('subject_id', $eje->id)
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_ped_tema_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create([
            'ped_plan_id' => $plan->id,
            'numero' => 1,
            'nombre' => 'Eje',
        ]);

        $tema = PedTema::create([
            'ped_eje_id' => $eje->id,
            'numero' => 1,
            'nombre' => 'Tema de prueba',
        ]);

        $activity = Activity::where('subject_type', PedTema::class)
            ->where('subject_id', $tema->id)
            ->first();

        $this->assertNotNull($activity);
    }

    public function test_ped_objetivo_estrategico_creation_is_logged(): void
    {
        $plan = PedPlan::create([
            'nombre' => 'PED Test',
            'nivel_gobierno' => 'estatal',
            'periodo_inicio' => 2026,
            'periodo_fin' => 2033,
            'activo' => true,
        ]);

        $eje = PedEje::create(['ped_plan_id' => $plan->id, 'numero' => 1, 'nombre' => 'Eje']);
        $tema = PedTema::create(['ped_eje_id' => $eje->id, 'numero' => 1, 'nombre' => 'Tema']);

        $objetivo = PedObjetivoEstrategico::create([
            'ped_tema_id' => $tema->id,
            'clave' => 'OE1',
            'descripcion' => 'Objetivo estratégico de prueba',
        ]);

        $activity = Activity::where('subject_type', PedObjetivoEstrategico::class)
            ->where('subject_id', $objetivo->id)
            ->first();

        $this->assertNotNull($activity);
    }
}
```

**Run tests:**
```bash
./vendor/bin/sail artisan test --filter=AuditPedTest
```

**Commit:**
```
feat(audit): add LogsActivity trait to PED models

Apply to PedPlan, PedEje, PedTema, PedObjetivoEstrategico.
Log only domain-relevant attributes with logOnlyDirty.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 4: Agregar LogsActivity trait a modelos MIR (MirNivel, Indicador)

**Files:**
- Modify: `app/Models/Mml/MirNivel.php`
- Modify: `app/Models/Mml/Indicador.php`
- Create: `tests/Feature/Admin/AuditMirTest.php`

**Step 1: Modificar MirNivel.php**

Agregar imports:
```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
```

Agregar trait:
```php
use LogsActivity;
```

Agregar configuración:
```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly([
            'programa_presupuestario_id', 'tipo_nivel', 'resumen_narrativo',
            'supuestos', 'orden', 'ped_objetivo_estrategico_id',
            'ped_linea_accion_id', 'team_id',
            'sintaxis_valida', 'sintaxis_observacion',
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "MirNivel {$eventName}");
}
```

**Step 2: Modificar Indicador.php**

Mismos imports y trait. Configuración:

```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly([
            'mir_nivel_id', 'nombre', 'formula_texto', 'tipo', 'dimension',
            'frecuencia', 'sentido', 'linea_base', 'meta',
            'rango_verde_min', 'rango_verde_max',
            'rango_amarillo_min', 'rango_amarillo_max',
            'rango_rojo_min', 'rango_rojo_max',
            'unidad_medida_id', 'activo_seguimiento',
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "Indicador {$eventName}");
}
```

**Step 3: Crear test**

En `tests/Feature/Admin/AuditMirTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditMirTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_mir_nivel_creation_is_logged(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Contribuir al desarrollo',
            'orden' => 1,
        ]);

        $activity = Activity::where('subject_type', MirNivel::class)
            ->where('subject_id', $nivel->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('MirNivel created', $activity->description);
    }

    public function test_mir_nivel_update_logs_only_changed(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Original',
            'orden' => 1,
        ]);

        Activity::query()->delete();

        $nivel->update(['resumen_narrativo' => 'Actualizado']);

        $activity = Activity::where('subject_type', MirNivel::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('resumen_narrativo', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('orden', $activity->properties['attributes']);
    }

    public function test_indicador_creation_is_logged(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Test',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Tasa de cobertura',
            'formula_texto' => 'A/B*100',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
            'sentido' => 'ascendente',
            'orden' => 1,
        ]);

        $activity = Activity::where('subject_type', Indicador::class)
            ->where('subject_id', $indicador->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Indicador created', $activity->description);
    }
}
```

**Run tests:**
```bash
./vendor/bin/sail artisan test --filter=AuditMirTest
```

**Commit:**
```
feat(audit): add LogsActivity trait to MirNivel and Indicador

Track changes to MIR structure and indicator configuration.
Log only dirty domain attributes, exclude high-churn fields.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 5: Agregar LogsActivity trait a EvaluacionPrograma

**Files:**
- Modify: `app/Models/Evaluation/EvaluacionPrograma.php`
- Create: `tests/Feature/Admin/AuditEvaluacionTest.php`

**Step 1: Modificar EvaluacionPrograma.php**

Agregar imports:
```php
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
```

Agregar trait:
```php
use LogsActivity;
```

Agregar configuración:
```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly([
            'programa_presupuestario_id', 'ejercicio_fiscal',
            'indice_eficacia', 'indicadores_evaluados',
            'indicadores_no_evaluados', 'calculado_por',
        ])
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn (string $eventName) => "EvaluacionPrograma {$eventName}");
}
```

> **Nota:** No logueamos `desglose_niveles`, `conteo_semaforos`, `configuracion_calculo`, ni `analisis_ia` porque son JSONB de alto volumen. Los campos clave (`indice_eficacia`, `indicadores_evaluados`) bastan para auditoría.

**Step 2: Crear test**

En `tests/Feature/Admin/AuditEvaluacionTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Models\Evaluation\EvaluacionPrograma;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditEvaluacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_evaluacion_creation_is_logged(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 85.5000,
            'indicadores_evaluados' => 10,
            'indicadores_no_evaluados' => 2,
            'calculado_por' => $user->id,
        ]);

        $activity = Activity::where('subject_type', EvaluacionPrograma::class)
            ->where('subject_id', $evaluacion->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('EvaluacionPrograma created', $activity->description);
    }

    public function test_evaluacion_update_excludes_json_fields(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();
        $user = User::factory()->withPersonalTeam()->create();

        $evaluacion = EvaluacionPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'indice_eficacia' => 80.0000,
            'indicadores_evaluados' => 8,
            'indicadores_no_evaluados' => 4,
            'calculado_por' => $user->id,
        ]);

        Activity::query()->delete();

        $evaluacion->update([
            'indice_eficacia' => 90.0000,
            'desglose_niveles' => ['fin' => 95],
        ]);

        $activity = Activity::where('subject_type', EvaluacionPrograma::class)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertArrayHasKey('indice_eficacia', $activity->properties['attributes']);
        $this->assertArrayNotHasKey('desglose_niveles', $activity->properties['attributes']);
    }
}
```

**Run tests:**
```bash
./vendor/bin/sail artisan test --filter=AuditEvaluacionTest
```

**Commit:**
```
feat(audit): add LogsActivity trait to EvaluacionPrograma

Track evaluation creation and key metric changes.
Exclude large JSONB fields (desglose, conteo, analisis_ia).

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 6: Crear componente Livewire Auditoria + vista + ruta

**Files:**
- Create: `app/Livewire/Admin/Auditoria.php`
- Create: `resources/views/livewire/admin/auditoria.blade.php`
- Modify: `routes/web/admin.php`
- Create: `tests/Feature/Admin/AuditoriaComponentTest.php`

**Step 1: Crear el componente Livewire**

En `app/Livewire/Admin/Auditoria.php`:

```php
<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
class Auditoria extends Component
{
    use WithPagination;

    #[Url]
    public string $subjectType = '';

    #[Url]
    public string $causerId = '';

    #[Url]
    public string $fechaDesde = '';

    #[Url]
    public string $fechaHasta = '';

    #[Url]
    public string $evento = '';

    /**
     * Mapa de tipos de modelo auditados con etiquetas legibles.
     */
    private const SUBJECT_TYPES = [
        'App\Models\User' => 'Usuario',
        'App\Models\PedPlan' => 'PED Plan',
        'App\Models\PedEje' => 'PED Eje',
        'App\Models\PedTema' => 'PED Tema',
        'App\Models\PedObjetivoEstrategico' => 'PED Objetivo Estratégico',
        'App\Models\Mml\MirNivel' => 'MIR Nivel',
        'App\Models\Mml\Indicador' => 'Indicador',
        'App\Models\Evaluation\EvaluacionPrograma' => 'Evaluación Programa',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('administrar_usuarios'), 403);

        if (empty($this->fechaDesde)) {
            $this->fechaDesde = now()->subDays(30)->toDateString();
        }
        if (empty($this->fechaHasta)) {
            $this->fechaHasta = now()->toDateString();
        }
    }

    public function updatedSubjectType(): void
    {
        $this->resetPage();
    }

    public function updatedCauserId(): void
    {
        $this->resetPage();
    }

    public function updatedFechaDesde(): void
    {
        $this->resetPage();
    }

    public function updatedFechaHasta(): void
    {
        $this->resetPage();
    }

    public function updatedEvento(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->subjectType = '';
        $this->causerId = '';
        $this->fechaDesde = now()->subDays(30)->toDateString();
        $this->fechaHasta = now()->toDateString();
        $this->evento = '';
        $this->resetPage();
    }

    public function getSubjectTypes(): array
    {
        return self::SUBJECT_TYPES;
    }

    public function getSubjectLabel(string $fqcn): string
    {
        return self::SUBJECT_TYPES[$fqcn] ?? class_basename($fqcn);
    }

    public function render()
    {
        $query = Activity::query()
            ->with('causer')
            ->whereBetween('created_at', [
                Carbon::parse($this->fechaDesde)->startOfDay(),
                Carbon::parse($this->fechaHasta)->endOfDay(),
            ])
            ->latest();

        if ($this->subjectType !== '') {
            $query->where('subject_type', $this->subjectType);
        }

        if ($this->causerId !== '') {
            $query->where('causer_id', (int) $this->causerId);
        }

        if ($this->evento !== '') {
            $query->where('event', $this->evento);
        }

        return view('livewire.admin.auditoria', [
            'activities' => $query->paginate(25),
            'subjectTypes' => $this->getSubjectTypes(),
            'usuarios' => \App\Models\User::orderBy('name')->pluck('name', 'id'),
        ]);
    }
}
```

**Step 2: Crear la vista Blade**

En `resources/views/livewire/admin/auditoria.blade.php`:

```blade
<div>
    <x-page.header>
        <x-slot name="title">Auditoría del sistema</x-slot>
    </x-page.header>

    <x-page.container>
        {{-- Filtros --}}
        <div class="mb-6 grid grid-cols-1 gap-4 rounded-lg border bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label for="subjectType" class="block text-sm font-medium text-gray-700">Tipo de modelo</label>
                <select wire:model.live="subjectType" id="subjectType"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($subjectTypes as $fqcn => $label)
                        <option value="{{ $fqcn }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="causerId" class="block text-sm font-medium text-gray-700">Usuario</label>
                <select wire:model.live="causerId" id="causerId"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($usuarios as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="evento" class="block text-sm font-medium text-gray-700">Evento</label>
                <select wire:model.live="evento" id="evento"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="created">Creación</option>
                    <option value="updated">Actualización</option>
                    <option value="deleted">Eliminación</option>
                </select>
            </div>

            <div>
                <label for="fechaDesde" class="block text-sm font-medium text-gray-700">Desde</label>
                <input type="date" wire:model.live="fechaDesde" id="fechaDesde"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label for="fechaHasta" class="block text-sm font-medium text-gray-700">Hasta</label>
                <input type="date" wire:model.live="fechaHasta" id="fechaHasta"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mb-4 flex justify-end">
            <button wire:click="limpiarFiltros" class="text-sm text-indigo-600 hover:text-indigo-800">
                Limpiar filtros
            </button>
        </div>

        {{-- Tabla de actividad --}}
        <div class="overflow-hidden rounded-lg border bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Evento</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Modelo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cambios</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($activities as $activity)
                        <tr class="hover:bg-gray-50">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $activity->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                {{ $activity->causer?->name ?? 'Sistema' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @switch($activity->event)
                                    @case('created')
                                        <span class="inline-flex rounded-full bg-green-100 px-2 text-xs font-semibold leading-5 text-green-800">Creación</span>
                                        @break
                                    @case('updated')
                                        <span class="inline-flex rounded-full bg-yellow-100 px-2 text-xs font-semibold leading-5 text-yellow-800">Actualización</span>
                                        @break
                                    @case('deleted')
                                        <span class="inline-flex rounded-full bg-red-100 px-2 text-xs font-semibold leading-5 text-red-800">Eliminación</span>
                                        @break
                                    @default
                                        <span class="inline-flex rounded-full bg-gray-100 px-2 text-xs font-semibold leading-5 text-gray-800">{{ $activity->event }}</span>
                                @endswitch
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                {{ $this->getSubjectLabel($activity->subject_type) }}
                                <span class="text-gray-400">#{{ $activity->subject_id }}</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $activity->description }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                @if($activity->properties->has('attributes'))
                                    <details class="cursor-pointer">
                                        <summary class="text-indigo-600 hover:text-indigo-800">
                                            {{ count($activity->properties['attributes']) }} campo(s)
                                        </summary>
                                        <div class="mt-2 max-w-md space-y-1">
                                            @foreach($activity->properties['attributes'] as $key => $value)
                                                <div class="text-xs">
                                                    <span class="font-medium text-gray-700">{{ $key }}:</span>
                                                    <span class="text-gray-500">{{ is_array($value) ? json_encode($value) : $value }}</span>
                                                    @if($activity->properties->has('old') && isset($activity->properties['old'][$key]))
                                                        <span class="text-gray-400">(antes: {{ is_array($activity->properties['old'][$key]) ? json_encode($activity->properties['old'][$key]) : $activity->properties['old'][$key] }})</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                                No se encontraron registros de auditoría para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </x-page.container>
</div>
```

**Step 3: Agregar ruta en admin.php**

En `routes/web/admin.php`, dentro del grupo `administrar_usuarios`, agregar:

```php
Route::get('/auditoria', \App\Livewire\Admin\Auditoria::class)->name('auditoria');
```

El archivo quedará:
```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'can:administrar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    // AI monitoring
    Route::get('/monitoreo-ia', \App\Livewire\Admin\MonitoreoIa::class)->name('monitoreo-ia');

    // Audit trail
    Route::get('/auditoria', \App\Livewire\Admin\Auditoria::class)->name('auditoria');
});

// User management — requires invitar_usuarios permission
Route::middleware(['auth:sanctum', 'verified', 'can:invitar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', \App\Livewire\Admin\GestionUsuarios::class)->name('users');
});
```

**Step 4: Crear test del componente**

En `tests/Feature/Admin/AuditoriaComponentTest.php`:

```php
<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Auditoria;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AuditoriaComponentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $operador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->withPersonalTeam()->create();
        $this->admin->assignRole('admin');

        $this->operador = User::factory()->withPersonalTeam()->create();
        $this->operador->assignRole('operador');
    }

    public function test_admin_can_access_auditoria(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('admin.auditoria'));

        $response->assertOk();
        $response->assertSeeLivewire(Auditoria::class);
    }

    public function test_non_admin_cannot_access_auditoria(): void
    {
        $this->actingAs($this->operador);

        $response = $this->get(route('admin.auditoria'));

        $response->assertForbidden();
    }

    public function test_guest_is_redirected(): void
    {
        $response = $this->get(route('admin.auditoria'));

        $response->assertRedirect(route('login'));
    }

    public function test_activities_are_displayed(): void
    {
        // The admin user creation itself generates an activity log entry
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->assertSee('Auditoría del sistema')
            ->assertStatus(200);
    }

    public function test_filter_by_subject_type(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('subjectType', 'App\Models\User')
            ->assertStatus(200);
    }

    public function test_filter_by_date_range(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('fechaDesde', now()->subDays(7)->toDateString())
            ->set('fechaHasta', now()->toDateString())
            ->assertStatus(200);
    }

    public function test_filter_by_event(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('evento', 'created')
            ->assertStatus(200);
    }

    public function test_limpiar_filtros_resets_all(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(Auditoria::class)
            ->set('subjectType', 'App\Models\User')
            ->set('evento', 'updated')
            ->call('limpiarFiltros')
            ->assertSet('subjectType', '')
            ->assertSet('evento', '');
    }
}
```

**Run tests:**
```bash
./vendor/bin/sail artisan test --filter=AuditoriaComponentTest
```

**Commit:**
```
feat(audit): create Auditoria Livewire component with filterable UI

Add /admin/auditoria route with filters by model type, user,
event, and date range. Paginated table with expandable change details.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 7: Tests finales + verificación

**Files:**
- No new files — run existing tests

**Step 1: Ejecutar todos los tests de auditoría**

```bash
./vendor/bin/sail artisan test --filter=Audit
```

**Step 2: Ejecutar la suite completa para verificar que no hay regresiones**

```bash
./vendor/bin/sail artisan test
```

Esperado: todos los tests existentes (baseline 426 passed) siguen pasando + los nuevos tests de auditoría.

**Step 3: Verificación manual**

1. Navegar a `/admin/auditoria` como admin
2. Verificar que la tabla muestra actividad reciente
3. Probar filtros: tipo de modelo, usuario, evento, rango de fechas
4. Probar "Limpiar filtros"
5. Verificar paginación con > 25 registros
6. Expandir detalles de cambios (click en "N campo(s)")
7. Verificar que un usuario no-admin recibe 403

**Step 4: Verificar que modelos excluidos NO generan logs**

```bash
./vendor/bin/sail artisan tinker --execute="
    \App\Models\Tracking\Avance::first()?->update(['estado' => 'borrador']);
    echo \Spatie\Activitylog\Models\Activity::where('subject_type', 'App\Models\Tracking\Avance')->count() . ' (debe ser 0)';
"
```

**Commit:**
```
test(audit): verify full test suite passes with activity logging

All audit tests pass. No regressions in existing 426+ tests.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

## Resumen de archivos

| Acción | Archivo |
|--------|---------|
| Instalar | `spatie/laravel-activitylog` via composer |
| Publicar | `database/migrations/xxxx_create_activity_log_table.php` |
| Publicar | `config/activitylog.php` |
| Modificar | `app/Models/User.php` |
| Modificar | `app/Models/PedPlan.php` |
| Modificar | `app/Models/PedEje.php` |
| Modificar | `app/Models/PedTema.php` |
| Modificar | `app/Models/PedObjetivoEstrategico.php` |
| Modificar | `app/Models/Mml/MirNivel.php` |
| Modificar | `app/Models/Mml/Indicador.php` |
| Modificar | `app/Models/Evaluation/EvaluacionPrograma.php` |
| Crear | `app/Livewire/Admin/Auditoria.php` |
| Crear | `resources/views/livewire/admin/auditoria.blade.php` |
| Modificar | `routes/web/admin.php` |
| Crear | `tests/Feature/Admin/AuditUserTest.php` |
| Crear | `tests/Feature/Admin/AuditPedTest.php` |
| Crear | `tests/Feature/Admin/AuditMirTest.php` |
| Crear | `tests/Feature/Admin/AuditEvaluacionTest.php` |
| Crear | `tests/Feature/Admin/AuditoriaComponentTest.php` |
