<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 of the architectural shift: dte-spp is the source of truth for
 * program identity. We stop storing geobase's local autoincrement and
 * just track whether this program has been provisioned in geobase.
 *
 * geobase resolves operations by spp_program_id (== programa.id) on its
 * own side, so dte-spp only needs to know "did I activate the padron for
 * this program already?".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->boolean('padron_geobase_activo')->default(false)->after('estado');
            $table->dropIndex(['geobase_program_id']);
            $table->dropColumn('geobase_program_id');
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->unsignedBigInteger('geobase_program_id')->nullable()->after('estado');
            $table->index('geobase_program_id');
            $table->dropColumn('padron_geobase_activo');
        });
    }
};
