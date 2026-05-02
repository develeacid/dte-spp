<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datasets_abiertos', function (Blueprint $table) {
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete()->after('publicado_en');
            $table->text('motivo_cambio_estado')->nullable()->after('creado_por');
            $table->index('creado_por', 'datasets_abiertos_creado_por_idx');
        });
    }

    public function down(): void
    {
        Schema::table('datasets_abiertos', function (Blueprint $table) {
            $table->dropIndex('datasets_abiertos_creado_por_idx');
            $table->dropConstrainedForeignId('creado_por');
            $table->dropColumn('motivo_cambio_estado');
        });
    }
};
