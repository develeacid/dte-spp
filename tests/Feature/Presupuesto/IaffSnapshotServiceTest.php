<?php

namespace Tests\Feature\Presupuesto;

use App\Models\Presupuesto\Iaff;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Presupuesto\IaffSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IaffSnapshotServiceTest extends TestCase
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

    private function service(): IaffSnapshotService
    {
        return app(IaffSnapshotService::class);
    }

    public function test_generar_crea_snapshot_con_hash(): void
    {
        $iaff = $this->service()->generar($this->programa, 2026, 1, $this->user);

        $this->assertDatabaseHas('iaff', [
            'programa_id' => $this->programa->id,
            'ejercicio_fiscal' => 2026,
            'trimestre' => 1,
            'generado_por' => $this->user->id,
        ]);
        $this->assertSame(64, strlen($iaff->hash_sha256));
        $this->assertArrayHasKey('financiero', $iaff->snapshot_payload);
        $this->assertArrayHasKey('consolidacion', $iaff->snapshot_payload);
        $this->assertNull($iaff->firmado_en);
    }

    public function test_regenerar_no_firmado_hace_upsert_no_duplica(): void
    {
        $this->service()->generar($this->programa, 2026, 1, $this->user);
        $this->service()->generar($this->programa, 2026, 1, $this->user);

        $this->assertSame(1, Iaff::where('programa_id', $this->programa->id)
            ->where('ejercicio_fiscal', 2026)->where('trimestre', 1)->count());
    }

    public function test_firmar_congela_el_snapshot(): void
    {
        $iaff = $this->service()->generar($this->programa, 2026, 1, $this->user);

        $firmado = $this->service()->firmar($iaff, $this->user);

        $this->assertNotNull($firmado->firmado_en);
        $this->assertSame($this->user->id, $firmado->firmado_por);
        $this->assertTrue($firmado->estaFirmado());
    }

    public function test_regenerar_tras_firma_no_sobrescribe(): void
    {
        $iaff = $this->service()->generar($this->programa, 2026, 1, $this->user);
        $this->service()->firmar($iaff, $this->user);
        $hashFirmado = $iaff->fresh()->hash_sha256;

        // Segundo intento de generar el mismo período: debe respetar el firmado.
        $resultado = $this->service()->generar($this->programa, 2026, 1, $this->user);

        $this->assertSame($hashFirmado, $resultado->hash_sha256);
        $this->assertNotNull($resultado->firmado_en);
    }

    public function test_firmar_dos_veces_lanza(): void
    {
        $iaff = $this->service()->generar($this->programa, 2026, 1, $this->user);
        $this->service()->firmar($iaff, $this->user);

        $this->expectException(\DomainException::class);
        $this->service()->firmar($iaff->fresh(), $this->user);
    }
}
