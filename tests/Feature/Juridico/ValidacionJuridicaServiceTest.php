<?php

namespace Tests\Feature\Juridico;

use App\Enums\EstadoValidacionJuridica;
use App\Enums\TipoDocumentoNormativo;
use App\Enums\TipoSustentoLegal;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\Juridico\ValidacionJuridicaPrograma;
use App\Models\User;
use App\Services\Juridico\ValidacionJuridicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\JuridicoTestHelpers;

class ValidacionJuridicaServiceTest extends TestCase
{
    use RefreshDatabase;
    use JuridicoTestHelpers;

    private ValidacionJuridicaService $service;
    private User $user;
    private int $teamId;
    private int $programaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedJuridicoPermissions();

        $this->service = app(ValidacionJuridicaService::class);
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->teamId = $this->user->currentTeam->id;

        $programa = $this->crearPrograma($this->teamId);
        $this->programaId = $programa->id;
    }

    public function test_recalcular_checklist_sin_sustentos(): void
    {
        $validacion = $this->service->recalcularChecklist($this->programaId, 2026);

        $this->assertFalse($validacion->tiene_facultad_ur);
        $this->assertFalse($validacion->tiene_mandato_gasto);
        $this->assertNull($validacion->tiene_rop); // No requiere ROP
    }

    public function test_recalcular_checklist_con_facultad_ur(): void
    {
        $this->crearSustento($this->programaId, $this->teamId, $this->user->id, TipoSustentoLegal::FACULTAD_UR);

        $validacion = $this->service->recalcularChecklist($this->programaId, 2026);

        $this->assertTrue($validacion->tiene_facultad_ur);
        $this->assertFalse($validacion->tiene_mandato_gasto);
    }

    public function test_recalcular_checklist_completo(): void
    {
        $this->crearSustento($this->programaId, $this->teamId, $this->user->id, TipoSustentoLegal::FACULTAD_UR);
        $this->crearSustento($this->programaId, $this->teamId, $this->user->id, TipoSustentoLegal::MANDATO_GASTO);

        $validacion = $this->service->recalcularChecklist($this->programaId, 2026);

        $this->assertTrue($validacion->tiene_facultad_ur);
        $this->assertTrue($validacion->tiene_mandato_gasto);
        $this->assertTrue($validacion->checklist_completo);
    }

    public function test_validar_con_checklist_completo(): void
    {
        $this->crearSustento($this->programaId, $this->teamId, $this->user->id, TipoSustentoLegal::FACULTAD_UR);
        $this->crearSustento($this->programaId, $this->teamId, $this->user->id, TipoSustentoLegal::MANDATO_GASTO);

        $validacion = $this->service->validar($this->programaId, 2026, $this->user->id, 'Todo en orden.');

        $this->assertEquals(EstadoValidacionJuridica::VALIDADO, $validacion->estado);
        $this->assertEquals($this->user->id, $validacion->validado_por);
        $this->assertNotNull($validacion->validado_at);
    }

    public function test_validar_con_checklist_incompleto_lanza_excepcion(): void
    {
        $this->expectException(\DomainException::class);

        $this->service->validar($this->programaId, 2026, $this->user->id);
    }

    public function test_rechazar_con_observaciones(): void
    {
        // Crear validación existente
        ValidacionJuridicaPrograma::create([
            'programa_presupuestario_id' => $this->programaId,
            'ejercicio_fiscal' => 2026,
            'estado' => EstadoValidacionJuridica::PENDIENTE,
        ]);

        $validacion = $this->service->rechazar($this->programaId, 2026, $this->user->id, 'Falta mandato de gasto.');

        $this->assertEquals(EstadoValidacionJuridica::RECHAZADO, $validacion->estado);
        $this->assertEquals('Falta mandato de gasto.', $validacion->observaciones);
    }

    public function test_puede_abrir_seguimiento_sin_requiere_rop(): void
    {
        $this->assertTrue($this->service->puedeAbrirSeguimiento($this->programaId));
    }

    public function test_programas_pendientes(): void
    {
        $pendientes = $this->service->programasPendientes($this->teamId, 2026);

        $this->assertCount(1, $pendientes); // Our test program has no validation
    }
}
