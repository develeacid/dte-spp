<?php

namespace Tests\Feature\Transparencia\Public\Publishers;

use App\Models\Mml\Indicador;
use App\Models\Mml\MetaPeriodo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\Transparencia\DatasetAbierto;
use App\Models\User;
use App\Services\Transparencia\Publishing\DesagregacionDemograficaPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\RefreshDatabasePublic;

class DesagregacionDemograficaPublisherTest extends TestCase
{
    use RefreshDatabase, RefreshDatabasePublic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRefreshDatabasePublic();
        Queue::fake();  // observers de programa_presupuestarios disparan jobs a geobase real
    }

    public function test_desnormaliza_4_dimensiones_a_filas(): void
    {
        $programa = $this->programaActivoConMir();

        Http::fake(['*desagregacion-bulk*' => Http::response([
            'data' => [
                [
                    'spp_program_id' => $programa->id,
                    'ejercicio_fiscal' => 2026,
                    'municipio_clave' => '20001',
                    'por_genero' => ['masculino' => 30, 'femenino' => 70, 'otro' => 0],
                    'por_grupo_edad' => ['nna' => 10, 'juventud' => 50, 'adulto' => 30, 'adulto_mayor' => 10],
                    'por_tipo_discapacidad' => ['motriz' => 5, 'visual' => 0, 'auditiva' => 0, 'intelectual' => 0, 'psicosocial' => 0, 'multiple' => 0, 'ninguna' => 95],
                    'por_etnia' => ['zapoteco' => 40, 'no_indigena' => 60],
                ],
            ],
        ], 200)]);

        app(DesagregacionDemograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $rows = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->where('municipio_clave', '20001')->get();

        // Sexo: M y F (otro=0 omitido excepto si fuera M/F).
        $sexo = $rows->where('dimension', 'sexo');
        $this->assertCount(2, $sexo); // M, F. Otro=0 omitido.
        $this->assertSame(30, (int) $sexo->firstWhere('categoria', 'masculino')->total_beneficiarios);
        $this->assertSame(70, (int) $sexo->firstWhere('categoria', 'femenino')->total_beneficiarios);

        // Grupo edad: 4 filas (todos > 0).
        $this->assertCount(4, $rows->where('dimension', 'grupo_edad'));

        // Discapacidad: 2 filas (motriz=5, ninguna=95). Las demás 0 omitidas.
        $disc = $rows->where('dimension', 'discapacidad');
        $this->assertCount(2, $disc);

        // Etnia: 2 filas.
        $this->assertCount(2, $rows->where('dimension', 'pueblo'));
    }

    public function test_calcula_porcentaje_dentro_de_dimension_municipio(): void
    {
        $programa = $this->programaActivoConMir();

        Http::fake(['*desagregacion-bulk*' => Http::response([
            'data' => [
                [
                    'spp_program_id' => $programa->id, 'ejercicio_fiscal' => 2026,
                    'municipio_clave' => '20001',
                    'por_genero' => ['masculino' => 60, 'femenino' => 40, 'otro' => 0],
                    'por_grupo_edad' => [], 'por_tipo_discapacidad' => [], 'por_etnia' => [],
                ],
            ],
        ], 200)]);

        app(DesagregacionDemograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $rows = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->where('dimension', 'sexo')
            ->where('municipio_clave', '20001')
            ->get();
        $m = $rows->firstWhere('categoria', 'masculino');
        $f = $rows->firstWhere('categoria', 'femenino');
        $this->assertEquals(60.00, (float) $m->porcentaje);
        $this->assertEquals(40.00, (float) $f->porcentaje);
    }

    public function test_genera_fila_estatal_municipio_clave_null(): void
    {
        $programa = $this->programaActivoConMir();

        Http::fake(['*desagregacion-bulk*' => Http::response([
            'data' => [
                ['spp_program_id' => $programa->id, 'ejercicio_fiscal' => 2026, 'municipio_clave' => '20001',
                    'por_genero' => ['masculino' => 10, 'femenino' => 20, 'otro' => 0],
                    'por_grupo_edad' => [], 'por_tipo_discapacidad' => [], 'por_etnia' => []],
                ['spp_program_id' => $programa->id, 'ejercicio_fiscal' => 2026, 'municipio_clave' => '20002',
                    'por_genero' => ['masculino' => 30, 'femenino' => 40, 'otro' => 0],
                    'por_grupo_edad' => [], 'por_tipo_discapacidad' => [], 'por_etnia' => []],
            ],
        ], 200)]);

        app(DesagregacionDemograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        // Filas estatales: municipio_clave NULL, dimension=sexo
        $estatales = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->whereNull('municipio_clave')
            ->where('dimension', 'sexo')
            ->get();
        $this->assertCount(2, $estatales); // M y F estatales
        $estatalM = $estatales->firstWhere('categoria', 'masculino');
        $estatalF = $estatales->firstWhere('categoria', 'femenino');
        $this->assertSame(40, (int) $estatalM->total_beneficiarios); // 10+30
        $this->assertSame(60, (int) $estatalF->total_beneficiarios); // 20+40
        // Porcentaje estatal: M=40 de 100 total = 40%
        $this->assertEquals(40.00, (float) $estatalM->porcentaje);
    }

    public function test_omite_categorias_con_total_cero_excepto_sexo(): void
    {
        $programa = $this->programaActivoConMir();

        Http::fake(['*desagregacion-bulk*' => Http::response([
            'data' => [[
                'spp_program_id' => $programa->id, 'ejercicio_fiscal' => 2026,
                'municipio_clave' => '20001',
                'por_genero' => ['masculino' => 50, 'femenino' => 50, 'otro' => 0],
                'por_grupo_edad' => ['nna' => 0, 'juventud' => 100, 'adulto' => 0, 'adulto_mayor' => 0],
                'por_tipo_discapacidad' => [], 'por_etnia' => [],
            ]],
        ], 200)]);

        app(DesagregacionDemograficaPublisher::class)->publish(DatasetAbierto::factory()->create());

        $municipales = DB::connection('pgsql_public')->table('pub_desagregacion_demografica')
            ->where('municipio_clave', '20001')
            ->get();

        // Sexo: M y F siempre. Otro=0 omitido.
        $sexo = $municipales->where('dimension', 'sexo');
        $this->assertCount(2, $sexo);

        // Grupo edad: solo juventud=100 (las 3 con 0 omitidas).
        $edad = $municipales->where('dimension', 'grupo_edad');
        $this->assertCount(1, $edad);
        $this->assertSame('juventud', $edad->first()->categoria);
    }

    public function test_code_es_ds_g02(): void
    {
        $this->assertSame('DS-G02', app(DesagregacionDemograficaPublisher::class)->code());
    }

    private function programaActivoConMir(): ProgramaPresupuestario
    {
        $programa = ProgramaPresupuestario::factory()->create([
            'nombre' => 'Programa Test', 'padron_geobase_activo' => true,
        ]);
        $user = User::factory()->create();
        $nivel = MirNivel::create([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente', 'resumen_narrativo' => 'C',
        ]);
        $indicador = Indicador::create([
            'mir_nivel_id' => $nivel->id, 'nombre' => 'IND',
            'tipo' => 'gestion', 'dimension' => 'eficacia', 'frecuencia' => 'trimestral',
        ]);
        MetaPeriodo::create([
            'indicador_id' => $indicador->id, 'periodo' => 1,
            'meta_periodo' => 100, 'ejercicio_fiscal' => 2026,
        ]);

        return $programa;
    }
}
