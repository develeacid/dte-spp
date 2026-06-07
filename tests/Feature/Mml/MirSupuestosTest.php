<?php

namespace Tests\Feature\Mml;

use App\Models\Mml\MirNivel;
use App\Models\Mml\MirSupuesto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MirSupuestosTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRACION = 'migrations/2026_06_07_100001_create_mir_supuestos_table.php';

    // --- Backfill (migración idempotente) -------------------------------------

    public function test_backfill_crea_un_supuesto_desde_el_texto_legacy(): void
    {
        $nivel = MirNivel::factory()->create(['supuestos' => 'Condiciones climáticas estables']);

        $this->assertDatabaseMissing('mir_supuestos', ['mir_nivel_id' => $nivel->id]);

        (require database_path(self::MIGRACION))->up();

        $this->assertDatabaseHas('mir_supuestos', [
            'mir_nivel_id' => $nivel->id,
            'descripcion' => 'Condiciones climáticas estables',
            'es_externo' => false,
            'es_relevante' => false,
            'probabilidad_razonable' => false,
            'orden' => 1,
        ]);
    }

    public function test_backfill_es_idempotente(): void
    {
        $nivel = MirNivel::factory()->create(['supuestos' => 'Texto legacy']);

        (require database_path(self::MIGRACION))->up();
        (require database_path(self::MIGRACION))->up();

        $this->assertSame(1, MirSupuesto::where('mir_nivel_id', $nivel->id)->count());
    }

    public function test_backfill_ignora_niveles_sin_texto_legacy(): void
    {
        $nivelVacio = MirNivel::factory()->create(['supuestos' => '']);
        $nivelNull = MirNivel::factory()->create(['supuestos' => null]);

        (require database_path(self::MIGRACION))->up();

        $this->assertDatabaseMissing('mir_supuestos', ['mir_nivel_id' => $nivelVacio->id]);
        $this->assertDatabaseMissing('mir_supuestos', ['mir_nivel_id' => $nivelNull->id]);
    }

    public function test_backfill_no_duplica_si_el_nivel_ya_tiene_supuestos_estructurados(): void
    {
        $nivel = MirNivel::factory()->create(['supuestos' => 'Texto legacy']);

        MirSupuesto::create([
            'mir_nivel_id' => $nivel->id,
            'descripcion' => 'Capturado a mano antes del backfill',
            'orden' => 1,
        ]);

        (require database_path(self::MIGRACION))->up();

        $this->assertSame(1, MirSupuesto::where('mir_nivel_id', $nivel->id)->count());
    }

    // --- Modelo ----------------------------------------------------------------

    public function test_relacion_ordenada_y_cascade_delete(): void
    {
        $nivel = MirNivel::factory()->create(['supuestos' => null]);

        $b = MirSupuesto::create(['mir_nivel_id' => $nivel->id, 'descripcion' => 'B', 'orden' => 2]);
        $a = MirSupuesto::create(['mir_nivel_id' => $nivel->id, 'descripcion' => 'A', 'orden' => 1]);

        $this->assertSame(
            [$a->id, $b->id],
            $nivel->supuestosEstructurados()->pluck('id')->all(),
        );

        $nivel->delete();

        $this->assertSame(0, DB::table('mir_supuestos')->count());
    }

    public function test_es_valido_requiere_los_tres_booleans(): void
    {
        $nivel = MirNivel::factory()->create();

        $supuesto = MirSupuesto::create([
            'mir_nivel_id' => $nivel->id,
            'descripcion' => 'Supuesto de prueba',
            'es_externo' => true,
            'es_relevante' => true,
            'probabilidad_razonable' => false,
            'orden' => 1,
        ]);

        $this->assertFalse($supuesto->esValido());

        $supuesto->update(['probabilidad_razonable' => true]);

        $this->assertTrue($supuesto->fresh()->esValido());
    }

    public function test_supuestos_texto_concatena_descripciones(): void
    {
        $nivel = MirNivel::factory()->create(['supuestos' => null]);

        $this->assertNull($nivel->supuestos_texto);

        MirSupuesto::create(['mir_nivel_id' => $nivel->id, 'descripcion' => 'Uno', 'orden' => 1]);
        MirSupuesto::create(['mir_nivel_id' => $nivel->id, 'descripcion' => 'Dos', 'orden' => 2]);

        $this->assertSame('Uno; Dos', $nivel->fresh()->supuestos_texto);
    }
}
