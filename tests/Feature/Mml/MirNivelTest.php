<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirVersion;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirNivelTest extends TestCase
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
    }

    public function test_crear_nivel_fin(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'resumen_narrativo' => 'Contribuir a la mejora educativa',
            'orden' => 1,
        ]);

        $this->assertDatabaseHas('mir_niveles', [
            'tipo_nivel' => 'fin',
            'resumen_narrativo' => 'Contribuir a la mejora educativa',
        ]);
    }

    public function test_jerarquia_componente_actividad(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'resumen_narrativo' => 'Becas entregadas',
            'orden' => 1,
        ]);

        $actividad = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componente->id,
            'resumen_narrativo' => 'Registro de beneficiarios',
            'orden' => 1,
        ]);

        $this->assertEquals($componente->id, $actividad->componente->id);
        $this->assertTrue($componente->actividades->contains($actividad));
    }

    public function test_cast_tipo_nivel_enum(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertInstanceOf(TipoNivelMir::class, $fin->fresh()->tipo_nivel);
    }

    public function test_relacion_programa(): void
    {
        $fin = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertEquals($this->programa->id, $fin->programa->id);
    }

    public function test_cascade_delete(): void
    {
        $componente = MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::COMPONENTE->value,
            'orden' => 1,
        ]);
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::ACTIVIDAD->value,
            'componente_id' => $componente->id,
            'orden' => 1,
        ]);

        $componente->delete();
        $this->assertDatabaseMissing('mir_niveles', ['componente_id' => $componente->id]);
    }

    public function test_crear_mir_version_snapshot(): void
    {
        $version = MirVersion::create([
            'programa_presupuestario_id' => $this->programa->id,
            'etiqueta' => 'Versión 1.0',
            'snapshot' => ['niveles' => [['tipo' => 'fin', 'texto' => 'Test']]],
            'created_by' => $this->user->id,
        ]);

        $this->assertIsArray($version->fresh()->snapshot);
        $this->assertEquals('Versión 1.0', $version->etiqueta);
    }

    public function test_relacion_programa_mir_niveles(): void
    {
        MirNivel::create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => TipoNivelMir::FIN->value,
            'orden' => 1,
        ]);

        $this->assertEquals(1, $this->programa->mirNiveles()->count());
    }
}
