<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avances', function (Blueprint $table) {
            $table->jsonb('analisis_desviacion')->nullable()->after('justificacion_final');
        });
    }

    public function down(): void
    {
        Schema::table('avances', function (Blueprint $table) {
            $table->dropColumn('analisis_desviacion');
        });
    }
};
