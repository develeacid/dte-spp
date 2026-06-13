<?php

namespace Tests\Feature\Presupuesto;

use App\Enums\EstadoCierreFiscal;
use App\Models\Presupuesto\CierreFiscal;
use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Presupuesto\CierreFiscalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CierreFiscalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'Test', 'clave' => 'PC-001',
            'team_id' => $this->user->currentTeam->id,
        ]);
    }

    private function service(): CierreFiscalService
    {
        return app(CierreFiscalService::class);
    }

    private function iaffQ4Firmado(): void
    {
        Iaff::create([
            'programa_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026, 'trimestre' => 4,
            'snapshot_payload' => [], 'hash_sha256' => str_repeat('a', 64),
            'generado_en' => now(), 'generado_por' => $this->user->id,
            'firmado_en' => now(), 'firmado_por' => $this->user->id,
        ]);
    }

    public function test_iniciar_crea_cierre_en_prevalidacion(): void
    {
        $cierre = $this->service()->iniciar($this->programa, 2026);

        $this->assertSame(EstadoCierreFiscal::PREVALIDACION, $cierre->estado);
    }

    public function test_avanza_prevalidacion_a_consolidacion(): void
    {
        $cierre = $this->service()->iniciar($this->programa, 2026);

        $cierre = $this->service()->avanzar($cierre, $this->user);

        $this->assertSame(EstadoCierreFiscal::CONSOLIDACION, $cierre->estado);
    }

    public function test_avanzar_a_firma_sin_iaff_q4_firmado_lanza(): void
    {
        $cierre = $this->service()->iniciar($this->programa, 2026);
        $cierre = $this->service()->avanzar($cierre, $this->user); // CONSOLIDACION

        $this->expectException(DomainException::class);
        $this->service()->avanzar($cierre, $this->user); // intenta FIRMA sin IAFF Q4 firmado
    }

    public function test_avanza_a_firma_con_iaff_q4_firmado(): void
    {
        $this->iaffQ4Firmado();
        $cierre = $this->service()->iniciar($this->programa, 2026);
        $cierre = $this->service()->avanzar($cierre, $this->user); // CONSOLIDACION

        $cierre = $this->service()->avanzar($cierre, $this->user); // FIRMA

        $this->assertSame(EstadoCierreFiscal::FIRMA, $cierre->estado);
    }

    public function test_avanza_hasta_cerrado_y_no_avanza_mas(): void
    {
        $this->iaffQ4Firmado();
        $cierre = $this->service()->iniciar($this->programa, 2026);
        $cierre = $this->service()->avanzar($cierre, $this->user); // CONSOLIDACION
        $cierre = $this->service()->avanzar($cierre, $this->user); // FIRMA
        $cierre = $this->service()->avanzar($cierre, $this->user); // CERRADO

        $this->assertSame(EstadoCierreFiscal::CERRADO, $cierre->estado);

        $this->expectException(DomainException::class);
        $this->service()->avanzar($cierre, $this->user);
    }

    public function test_historial_registra_cada_avance(): void
    {
        $this->iaffQ4Firmado();
        $cierre = $this->service()->iniciar($this->programa, 2026);
        $cierre = $this->service()->avanzar($cierre, $this->user);

        $this->assertNotEmpty($cierre->historial);
        $this->assertSame('consolidacion', $cierre->historial[0]['estado']);
    }
}
