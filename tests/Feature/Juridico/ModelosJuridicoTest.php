<?php

namespace Tests\Feature\Juridico;

use App\Enums\EstadoValidacionJuridica;
use App\Enums\NivelJerarquiaLegal;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\CatalogoOrdenamiento;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\User;
use Database\Seeders\CatalogoOrdenamientosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\JuridicoTestHelpers;

class ModelosJuridicoTest extends TestCase
{
    use RefreshDatabase;
    use JuridicoTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedJuridicoPermissions();
    }

    public function test_catalogo_ordenamientos_seeder(): void
    {
        $this->seed(CatalogoOrdenamientosSeeder::class);

        $this->assertDatabaseCount('catalogo_ordenamientos', 15);
        $this->assertDatabaseHas('catalogo_ordenamientos', ['abreviatura' => 'CPEUM']);
        $this->assertDatabaseHas('catalogo_ordenamientos', ['abreviatura' => 'LOEPO']);
    }

    public function test_catalogo_scope_activos(): void
    {
        $this->seed(CatalogoOrdenamientosSeeder::class);

        $activos = CatalogoOrdenamiento::activos()->count();
        $this->assertEquals(15, $activos);

        CatalogoOrdenamiento::where('abreviatura', 'CPEUM')->update(['activo' => false]);
        $this->assertEquals(14, CatalogoOrdenamiento::activos()->count());
    }

    public function test_catalogo_scope_por_nivel(): void
    {
        $this->seed(CatalogoOrdenamientosSeeder::class);

        $federales = CatalogoOrdenamiento::porNivel(NivelJerarquiaLegal::FEDERAL)->count();
        $this->assertEquals(3, $federales);
    }

    public function test_sustento_legal_cita_completa(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = $this->crearPrograma($user->currentTeam->id);

        $sustento = SustentoLegalPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo' => TipoSustentoLegal::FACULTAD_UR,
            'ordenamiento' => 'Ley Orgánica del Poder Ejecutivo',
            'articulo' => 'Art. 45, Frac. III',
            'nivel_jerarquia' => NivelJerarquiaLegal::ESTATAL,
            'vigente' => true,
            'registrado_por' => $user->id,
            'team_id' => $user->currentTeam->id,
        ]);

        $this->assertEquals('Ley Orgánica del Poder Ejecutivo, Art. 45, Frac. III', $sustento->cita_completa);
    }

    public function test_validacion_checklist_completo(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = $this->crearPrograma($user->currentTeam->id);

        $validacion = ValidacionJuridicaPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'tiene_facultad_ur' => true,
            'tiene_mandato_gasto' => true,
            'tiene_rop' => null,
        ]);

        $this->assertTrue($validacion->checklist_completo);
        $this->assertEmpty($validacion->items_pendientes);
    }

    public function test_validacion_checklist_incompleto(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = $this->crearPrograma($user->currentTeam->id);

        $validacion = ValidacionJuridicaPrograma::create([
            'programa_presupuestario_id' => $programa->id,
            'ejercicio_fiscal' => 2026,
            'tiene_facultad_ur' => true,
            'tiene_mandato_gasto' => false,
            'tiene_rop' => false,
        ]);

        $this->assertFalse($validacion->checklist_completo);
        $this->assertContains('Mandato de gasto', $validacion->items_pendientes);
        $this->assertContains('Reglas de Operación', $validacion->items_pendientes);
    }

    public function test_estado_validacion_permite_edicion(): void
    {
        $this->assertTrue(EstadoValidacionJuridica::PENDIENTE->permiteEdicion());
        $this->assertTrue(EstadoValidacionJuridica::EN_REVISION->permiteEdicion());
        $this->assertTrue(EstadoValidacionJuridica::RECHAZADO->permiteEdicion());
        $this->assertFalse(EstadoValidacionJuridica::VALIDADO->permiteEdicion());
        $this->assertFalse(EstadoValidacionJuridica::VENCIDO->permiteEdicion());
    }

    public function test_programa_tiene_relaciones_juridico(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $programa = $this->crearPrograma($user->currentTeam->id);

        $this->assertCount(0, $programa->sustentosLegales);
        $this->assertCount(0, $programa->documentosNormativos);
        $this->assertNull($programa->validacionJuridica);
    }
}
