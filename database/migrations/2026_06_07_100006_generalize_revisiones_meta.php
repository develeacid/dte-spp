<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisiones_meta', function (Blueprint $table) {
            // meta_periodo_id deja de ser obligatorio: ahora la revisión puede
            // apuntar a un indicador (meta anual) en vez de a un meta_periodo.
            $table->foreignId('meta_periodo_id')->nullable()->change();

            $table->foreignId('indicador_id')
                ->nullable()
                ->after('meta_periodo_id')
                ->constrained('indicadores')
                ->cascadeOnDelete();
        });

        // XOR: exactamente uno de los dos FKs debe estar presente.
        DB::statement('ALTER TABLE revisiones_meta ADD CONSTRAINT revisiones_meta_target_xor CHECK ((meta_periodo_id IS NOT NULL) <> (indicador_id IS NOT NULL))');
    }

    public function down(): void
    {
        // ⚠ Solo es seguro si no hay filas con indicador_id (la tabla nació en
        // el sprint inmediatamente anterior, sin deploy a prod). Volver
        // meta_periodo_id a NOT NULL fallaría si existen revisiones de meta anual.
        DB::statement('ALTER TABLE revisiones_meta DROP CONSTRAINT IF EXISTS revisiones_meta_target_xor');

        Schema::table('revisiones_meta', function (Blueprint $table) {
            $table->dropConstrainedForeignId('indicador_id');
            $table->foreignId('meta_periodo_id')->nullable(false)->change();
        });
    }
};
