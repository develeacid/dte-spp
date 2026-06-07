<?php

namespace Tests\Feature\Mml;

use App\DTOs\ImportedMirData;
use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Models\User;
use App\Services\Mml\MirPersistenciaService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HardenMirFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mir_nivel_resumen_narrativo_defaults_to_empty_string_when_omitted(): void
    {
        $programa = ProgramaPresupuestario::factory()->create();

        $id = DB::table('mir_niveles')->insertGetId([
            'programa_presupuestario_id' => $programa->id,
            'tipo_nivel' => 'componente',
            'orden' => 1,
            // resumen_narrativo intencionalmente omitido
        ]);

        $this->assertSame('', DB::table('mir_niveles')->where('id', $id)->value('resumen_narrativo'));
    }

    public function test_indicador_formula_texto_defaults_to_empty_string_when_omitted(): void
    {
        $nivel = MirNivel::factory()->create();
        $unidadId = $this->unidadId();

        $id = DB::table('indicadores')->insertGetId([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Ind',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'unidad_medida_id' => $unidadId,
            'orden' => 1,
            // formula_texto omitido
        ]);

        $this->assertSame('', DB::table('indicadores')->where('id', $id)->value('formula_texto'));
    }

    public function test_indicador_sentido_defaults_to_ascendente_when_omitted(): void
    {
        $nivel = MirNivel::factory()->create();
        $unidadId = $this->unidadId();

        $id = DB::table('indicadores')->insertGetId([
            'mir_nivel_id' => $nivel->id,
            'nombre' => 'Ind',
            'tipo' => 'estrategico',
            'dimension' => 'eficacia',
            'frecuencia' => 'anual',
            'unidad_medida_id' => $unidadId,
            'orden' => 1,
            // sentido omitido
        ]);

        $this->assertSame('ascendente', DB::table('indicadores')->where('id', $id)->value('sentido'));
    }

    public function test_mir_persistencia_resolves_unidad_no_definida_when_missing(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $service = new MirPersistenciaService;

        $data = new ImportedMirData(
            nombre: 'Prog ND',
            clave: 'ND01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => 'Contribuir',
                    'supuestos' => null,
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Ind sin unidad',
                            'formula_texto' => 'A/B',
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'sentido' => 'ascendente',
                            'variables' => [],
                            'medios' => [],
                        ],
                    ],
                ],
            ],
        );

        $programa = $service->persistir($data, $user->currentTeam->id, $user->id);

        $indicador = Indicador::whereHas('mirNivel', fn ($q) => $q->where('programa_presupuestario_id', $programa->id))->firstOrFail();

        $this->assertNotNull($indicador->unidad_medida_id);
        $this->assertSame('ND', DB::table('catalogo_unidades_medida')->where('id', $indicador->unidad_medida_id)->value('clave'));
    }

    public function test_mir_persistencia_coalesces_null_load_bearing_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $service = new MirPersistenciaService;

        $data = new ImportedMirData(
            nombre: 'Prog Nulls',
            clave: 'NL01',
            ejercicioFiscal: 2026,
            niveles: [
                [
                    'tipo_nivel' => 'fin',
                    'resumen_narrativo' => null,
                    'supuestos' => null,
                    'orden' => 1,
                    'indicadores' => [
                        [
                            'nombre' => 'Ind nulls',
                            'formula_texto' => null,
                            'tipo' => 'estrategico',
                            'dimension' => 'eficacia',
                            'frecuencia' => 'anual',
                            'sentido' => null,
                            'variables' => [],
                            'medios' => [],
                        ],
                    ],
                ],
            ],
        );

        $programa = $service->persistir($data, $user->currentTeam->id, $user->id);

        $nivel = MirNivel::where('programa_presupuestario_id', $programa->id)->firstOrFail();
        $this->assertSame('', $nivel->resumen_narrativo);

        $indicador = $nivel->indicadores->firstOrFail();
        $this->assertSame('', $indicador->formula_texto);
        $this->assertSame('ascendente', $indicador->sentido->value);
        $this->assertNotNull($indicador->unidad_medida_id);
    }

    public function test_update_to_null_resumen_narrativo_throws(): void
    {
        $nivel = MirNivel::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('mir_niveles')->where('id', $nivel->id)->update(['resumen_narrativo' => null]);
    }

    public function test_update_to_null_formula_texto_throws(): void
    {
        $indicador = Indicador::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('indicadores')->where('id', $indicador->id)->update(['formula_texto' => null]);
    }

    public function test_update_to_null_sentido_throws(): void
    {
        $indicador = Indicador::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('indicadores')->where('id', $indicador->id)->update(['sentido' => null]);
    }

    public function test_update_to_null_unidad_medida_throws(): void
    {
        $indicador = Indicador::factory()->create();

        $this->expectException(QueryException::class);

        DB::table('indicadores')->where('id', $indicador->id)->update(['unidad_medida_id' => null]);
    }

    private function unidadId(): int
    {
        return DB::table('catalogo_unidades_medida')->insertGetId([
            'clave' => 'PCT',
            'nombre' => 'Porcentaje',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
