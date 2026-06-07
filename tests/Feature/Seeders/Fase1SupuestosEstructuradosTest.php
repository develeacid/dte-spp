<?php

namespace Tests\Feature\Seeders;

use App\Enums\TipoNivelMir;
use App\Models\Mml\MirNivel;
use App\Models\Mml\MirSupuesto;
use App\Models\ProgramaPresupuestario;
use Database\Seeders\AnexosTransversalesSeeder;
use Database\Seeders\Fase0PrerequisitosSeeder;
use Database\Seeders\Fase1PlaneacionMmlSeeder;
use Database\Seeders\Mml\UnidadesMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Cubre el path Fase1 de los supuestos estructurados (estrategia
 * read-from-definitions de `Fase1PlaneacionMmlSeeder::crearSupuestoEstructurado()`).
 *
 * A diferencia de SeedersDemoTest (que corre Desarrollo + QaTesting), aquí los
 * prerequisitos mínimos son distintos: Fase0 (roles/URs/usuarios), unidades de
 * medida (`unidad_medida_id` numéricos referenciados por las definiciones) y
 * anexos transversales (sync por id). Por eso vive en su propio archivo con un
 * setUp propio.
 *
 * Bus::fake() viene del TestCase base, así que el observer GeoBase de ISM-001
 * (padron_geobase_activo => true) no golpea servicios externos.
 */
class Fase1SupuestosEstructuradosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(Fase0PrerequisitosSeeder::class);
        $this->seed(UnidadesMedidaSeeder::class);
        $this->seed(AnexosTransversalesSeeder::class);
        $this->seed(Fase1PlaneacionMmlSeeder::class);
    }

    public function test_fase1_crea_supuestos_estructurados(): void
    {
        $this->assertGreaterThan(0, MirSupuesto::count(), 'Fase1 debe crear MirSupuesto estructurados.');
    }

    public function test_niveles_de_ism001_tienen_supuestos_estructurados(): void
    {
        $programa = ProgramaPresupuestario::where('clave', 'ISM-001')->firstOrFail();

        $niveles = MirNivel::where('programa_presupuestario_id', $programa->id)->get();

        $this->assertGreaterThan(0, $niveles->count(), 'ISM-001 debe tener niveles MIR.');

        foreach ($niveles as $nivel) {
            $this->assertTrue(
                MirSupuesto::where('mir_nivel_id', $nivel->id)->exists(),
                "El nivel {$nivel->tipo_nivel->value} de ISM-001 debe tener al menos un MirSupuesto estructurado."
            );
        }
    }

    public function test_existe_al_menos_un_supuesto_parcial_en_actividades(): void
    {
        // El path ACTIVIDAD queda como parcial (solo externo, sin relevante ni
        // probabilidad razonable) para que la UI muestre ambos estados.
        $actividadIds = MirNivel::where('tipo_nivel', TipoNivelMir::ACTIVIDAD)->pluck('id');

        $this->assertTrue(
            MirSupuesto::whereIn('mir_nivel_id', $actividadIds)
                ->where('es_externo', true)
                ->where('es_relevante', false)
                ->where('probabilidad_razonable', false)
                ->exists(),
            'Debe existir al menos un supuesto parcial (path ACTIVIDAD solo-externo).'
        );
    }

    public function test_columna_legacy_supuestos_queda_null_en_todos_los_niveles(): void
    {
        $this->assertSame(
            0,
            MirNivel::whereNotNull('supuestos')->count(),
            'La columna legacy mir_niveles.supuestos debe quedar NULL en todos los niveles (Fase1 no la escribe).'
        );
    }

    public function test_segunda_corrida_no_duplica_supuestos(): void
    {
        $antes = MirSupuesto::count();

        $this->seed(Fase1PlaneacionMmlSeeder::class);

        $this->assertSame($antes, MirSupuesto::count(), 'Re-correr Fase1 no debe duplicar MirSupuesto.');
    }
}
