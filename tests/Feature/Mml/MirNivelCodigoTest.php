<?php

namespace Tests\Feature\Mml;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MirNivelCodigoTest extends TestCase
{
    use RefreshDatabase;

    private ProgramaPresupuestario $programa;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->withPersonalTeam()->create();
        $this->programa = ProgramaPresupuestario::create([
            'nombre' => 'P', 'clave' => 'PT-COD-'.uniqid(),
            'team_id' => $user->currentTeam->id, 'ejercicio_fiscal' => 2026,
        ]);
    }

    private function nivel(TipoNivelMir $tipo, int $orden, ?int $componenteId = null): MirNivel
    {
        return MirNivel::factory()->create([
            'programa_presupuestario_id' => $this->programa->id,
            'tipo_nivel' => $tipo->value,
            'orden' => $orden,
            'componente_id' => $componenteId,
        ]);
    }

    public function test_codigo_fin_y_proposito(): void
    {
        $this->assertSame('F', $this->nivel(TipoNivelMir::FIN, 1)->codigoMir());
        $this->assertSame('P', $this->nivel(TipoNivelMir::PROPOSITO, 1)->codigoMir());
    }

    public function test_codigo_componente(): void
    {
        $this->assertSame('C2', $this->nivel(TipoNivelMir::COMPONENTE, 2)->codigoMir());
    }

    public function test_codigo_actividad_usa_orden_del_componente_padre(): void
    {
        $comp = $this->nivel(TipoNivelMir::COMPONENTE, 3);
        $act = $this->nivel(TipoNivelMir::ACTIVIDAD, 1, $comp->id);

        $this->assertSame('A3.1', $act->codigoMir());
    }

    public function test_mir_sheet_incluye_columna_codigo(): void
    {
        $sheet = new \App\Exports\Excel\MirSheet($this->programa, 2026);

        $this->assertContains('Código', $sheet->headings());
    }
}
