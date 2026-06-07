<?php

namespace Tests\Feature\Seeders;

use App\Models\Mml\Indicador;
use App\Models\Mml\MirNivel;
use Database\Seeders\Mml\UnidadesMedidaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnidadesMedidaSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reproduce el estado de una BD FRESCA tras `migrate:fresh`: la migración
     * `harden_mir_fields` corre sobre el catálogo vacío y crea 'ND' con id
     * auto-secuencial (≈2). El seeder DEBE tolerarlo: si lo upserta por id
     * explícito (1..9) colisiona el PK contra ese ND@2.
     */
    public function test_seeder_tolerates_stray_nd_created_by_migration_on_fresh_db(): void
    {
        // Reconstruye exactamente lo que deja la migración en BD fresca:
        // catálogo con un único ND auto-secuencial en id 2, sin referencias.
        DB::table('catalogo_unidades_medida')->delete();
        DB::statement("SELECT setval(pg_get_serial_sequence('catalogo_unidades_medida', 'id'), 1, false)");
        DB::table('catalogo_unidades_medida')->insert([
            'id' => 2,
            'clave' => 'ND',
            'nombre' => 'No definida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(UnidadesMedidaSeeder::class);

        $this->assertSame(9, DB::table('catalogo_unidades_medida')->count());
        $this->assertSame(9, (int) DB::table('catalogo_unidades_medida')->where('clave', 'ND')->value('id'));
        $this->assertSame(2, (int) DB::table('catalogo_unidades_medida')->where('clave', 'TASA')->value('id'));
        $this->assertSame(1, DB::table('catalogo_unidades_medida')->where('clave', 'ND')->count());
    }

    /**
     * BD vieja (prod): el ND ya existe con un id alto (>9) y un indicador lo
     * referencia. El seeder NO debe borrarlo (rompería la FK); el upsert por
     * clave solo actualiza su nombre, sin colisión de PK.
     */
    public function test_seeder_preserves_referenced_nd_in_existing_db(): void
    {
        // La migración harden_mir_fields ya dejó un ND auto-secuencial en la BD
        // de testing; lo limpiamos para reconstruir el estado "BD vieja" donde
        // ND vive con un id alto referenciado por indicadores.
        DB::table('catalogo_unidades_medida')->where('clave', 'ND')->delete();

        DB::table('catalogo_unidades_medida')->insert([
            'id' => 20,
            'clave' => 'ND',
            'nombre' => 'No definida',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nivel = MirNivel::factory()->create();
        $indicador = Indicador::factory()->create([
            'mir_nivel_id' => $nivel->id,
            'unidad_medida_id' => 20,
        ]);

        $this->seed(UnidadesMedidaSeeder::class);

        // ND@20 conserva su id y el indicador su referencia.
        $this->assertSame(20, (int) DB::table('catalogo_unidades_medida')->where('clave', 'ND')->value('id'));
        $this->assertSame(20, (int) Indicador::find($indicador->id)->unidad_medida_id);

        // No hay ND duplicado: el upsert por clave actualizó el existente.
        $this->assertSame(1, DB::table('catalogo_unidades_medida')->where('clave', 'ND')->count());

        // Las 9 claves canónicas están presentes (sus ids 1..8 pueden variar
        // porque el IndicadorFactory hace firstOrCreate('PCT') antes del seeder;
        // la garantía de ids exactos en BD fresca la cubre el primer test).
        foreach (['PCT', 'TASA', 'IDX', 'PROM', 'NUM', 'RAZ', 'PROP', 'MNT', 'ND'] as $clave) {
            $this->assertSame(1, DB::table('catalogo_unidades_medida')->where('clave', $clave)->count(), "clave $clave");
        }
    }
}
