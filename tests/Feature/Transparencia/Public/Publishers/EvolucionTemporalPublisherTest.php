<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Tracking\Avance;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use App\Services\Transparencia\Publishing\EvolucionTemporalPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class EvolucionTemporalPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
    }

    public function test_publish_agrega_evolucion_temporal(): void
    {
        $user = User::factory()->create();
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa A',
        ]);
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'IND',
            'tipo' => 'gestion',
            'dimension' => 'eficacia',
            'frecuencia' => 'trimestral',
        ]);

        foreach ([[1, 100], [2, 150]] as [$trimestre, $resultado]) {
            $meta = MetaPeriodo::create([
                'indicador_id' => $indicador->id,
                'periodo' => $trimestre,
                'meta_periodo' => 200.0,
                'ejercicio_fiscal' => 2026,
            ]);
            Avance::create([
                'meta_periodo_id' => $meta->id,
                'indicador_id' => $indicador->id,
                'resultado' => $resultado,
                'semaforo_calculado' => 'verde',
                'estado' => 'en_captura',
                'capturado_por' => $user->id,
            ]);
        }

        app(EvolucionTemporalPublisher::class)->publish(DatasetAbierto::factory()->create());

        $rows = DB::connection('pgsql_public')->table('pub_evolucion_temporal')
            ->where('programa_nombre', 'Programa A')->orderBy('trimestre')->get();
        $this->assertCount(2, $rows);
        $this->assertSame(100, (int) $rows[0]->total_beneficiarios);
        $this->assertSame(1, (int) $rows[0]->trimestre);
        $this->assertSame(150, (int) $rows[1]->total_beneficiarios);
        $this->assertSame(2, (int) $rows[1]->trimestre);
    }

    public function test_code_es_ds_g04(): void
    {
        $this->assertSame('DS-G04', app(EvolucionTemporalPublisher::class)->code());
    }
}
