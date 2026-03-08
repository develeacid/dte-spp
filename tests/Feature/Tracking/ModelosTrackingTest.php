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
