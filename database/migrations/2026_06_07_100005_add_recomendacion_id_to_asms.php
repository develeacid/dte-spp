<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asms', function (Blueprint $table) {
            $table->foreignId('recomendacion_id')
                ->nullable()
                ->after('evaluacion_id')
                ->constrained('recomendaciones')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recomendacion_id');
        });
    }
};
