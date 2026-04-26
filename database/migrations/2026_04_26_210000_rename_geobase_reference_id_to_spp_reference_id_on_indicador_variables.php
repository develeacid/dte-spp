<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The reference_id stored on indicador_variables points either to a programa
 * (program_coverage) or to a MIR componente (component_coverage). Both
 * resolutions go through dte-spp ids now, so rename the column to make the
 * semantics explicit. geobase_endpoint_type stays — it discriminates which
 * geobase endpoint to hit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicador_variables', function (Blueprint $table) {
            $table->renameColumn('geobase_reference_id', 'spp_reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('indicador_variables', function (Blueprint $table) {
            $table->renameColumn('spp_reference_id', 'geobase_reference_id');
        });
    }
};
