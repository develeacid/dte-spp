<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Livewire\Mml\MirEditor;
use App\Models\Mml\Indicador;
use App\Models\Mml\IndicadorVariable;
use App\Models\Mml\MedioVerificacion;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirVersion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\MirSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MirSnapshotTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PT-001',
            'team_id' => $this->user->currentTeam->id,
        ]);

        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a la mejora',
            'orden' => 1,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::PROPOSITO->value,
            'resumen_narrativo' => 'Población beneficiada',
            'orden' => 1,
        ]);

        $comp = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Becas entregadas',
            'orden' => 1,
        ]);

        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $comp->id,
            'resumen_narrativo' => 'Registro de beneficiarios',
            'orden' => 1,
        ]);

        $indicador = Indicador::create([
            'mir_nivel_id' => $fin->id,
            'nombre' => 'Tasa de cobertura',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'formula_texto' => '(A/B) x 100',
            'orden' => 1,
        ]);

        IndicadorVariable::create([
            'indicador_id' => $indicador->id,
            'simbolo' => 'A',
            'nombre' => 'Alumnos inscritos',
            'orden' => 1,
        ]);

        MedioVerificacion::create([
            'indicador_id' => $indicador->id,
            'nombre' => 'Padrón de beneficiarios',
            'orden' => 1,
        ]);
    }

    public function test_crear_snapshot_guarda_estado_completo(): void
    {
        $service = app(MirSnapshotService::class);
        $version = $service->crear($this->programa, 'Borrador inicial', $this->user->id);

        $this->assertDatabaseHas('mir_versiones', [
            'programa_presupuestario_id' => $this->programa->id,
            'etiqueta' => 'Borrador inicial',
            'created_by' => $this->user->id,
        ]);

        $snapshot = $version->snapshot;
        $this->assertCount(4, $snapshot); // fin, proposito, componente, actividad
    }

    public function test_snapshot_incluye_indicadores_y_variables(): void
    {
        $service = app(MirSnapshotService::class);
        $version = $service->crear($this->programa, 'Con indicadores', $this->user->id);

        $snapshot = $version->snapshot;
        $finData = collect($snapshot)->firstWhere('tipo_nivel', 'fin');

        $this->assertNotEmpty($finData['indicadores']);
        $this->assertEquals('Tasa de cobertura', $finData['indicadores'][0]['nombre']);
        $this->assertNotEmpty($finData['indicadores'][0]['variables']);
        $this->assertEquals('A', $finData['indicadores'][0]['variables'][0]['simbolo']);
        $this->assertNotEmpty($finData['indicadores'][0]['medios']);
    }

    public function test_restaurar_version_reemplaza_mir(): void
    {
        $service = app(MirSnapshotService::class);
        $version = $service->crear($this->programa, 'Backup', $this->user->id);

        // Modify current MIR
        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $fin->update(['resumen_narrativo' => 'Modificado después del snapshot']);

        // Restore
        $service->restaurar($version);

        $finRestored = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $this->assertEquals('Contribuir a la mejora', $finRestored->resumen_narrativo);
    }

    public function test_restaurar_recrea_indicadores(): void
    {
        $service = app(MirSnapshotService::class);
        $version = $service->crear($this->programa, 'Backup', $this->user->id);

        // Delete all indicators
        Indicador::query()->delete();
        IndicadorVariable::query()->delete();

        $service->restaurar($version);

        $fin = $this->programa->mirNiveles()->where('tipo_nivel', 'fin')->first();
        $this->assertEquals(1, $fin->indicadores()->count());
        $this->assertEquals('Tasa de cobertura', $fin->indicadores()->first()->nombre);
        $this->assertEquals(1, $fin->indicadores()->first()->variables()->count());
    }

    public function test_restaurar_mantiene_jerarquia_componente_actividad(): void
    {
        $service = app(MirSnapshotService::class);
        $version = $service->crear($this->programa, 'Backup', $this->user->id);

        $service->restaurar($version);

        $comp = $this->programa->mirNiveles()->where('tipo_nivel', 'componente')->first();
        $actividad = $this->programa->mirNiveles()->where('tipo_nivel', 'actividad')->first();

        $this->assertNotNull($actividad->componente_id);
        $this->assertEquals($comp->id, $actividad->componente_id);
    }

    public function test_crear_snapshot_via_livewire(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->set('snapshotEtiqueta', 'Mi snapshot')
            ->call('crearSnapshot');

        $this->assertDatabaseHas('mir_versiones', [
            'etiqueta' => 'Mi snapshot',
        ]);
    }

    public function test_no_crea_snapshot_sin_etiqueta(): void
    {
        Livewire::actingAs($this->user)
            ->test(MirEditor::class, ['programa' => $this->programa])
            ->set('snapshotEtiqueta', '')
            ->call('crearSnapshot');

        $this->assertEquals(0, MirVersion::count());
    }
}
