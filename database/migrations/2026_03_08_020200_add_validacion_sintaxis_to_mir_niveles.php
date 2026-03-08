<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mir_niveles', function (Blueprint $table) {
            $table->boolean('sintaxis_valida')->nullable();
            $table->text('sintaxis_observacion')->nullable();
            $table->text('sintaxis_sugerencia')->nullable();
            $table->timestamp('sintaxis_validada_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mir_niveles', function (Blueprint $table) {
            $table->dropColumn([
                'sintaxis_valida', 'sintaxis_observacion',
                'sintaxis_sugerencia', 'sintaxis_validada_at',
            ]);
        });
    }
};
