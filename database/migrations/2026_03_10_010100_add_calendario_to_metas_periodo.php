<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('metas_periodo', function (Blueprint $table) {
            $table->date('fecha_apertura')->nullable()->after('activo');
            $table->date('fecha_cierre')->nullable()->after('fecha_apertura');
        });
    }

    public function down(): void
    {
        Schema::table('metas_periodo', function (Blueprint $table) {
            $table->dropColumn(['fecha_apertura', 'fecha_cierre']);
        });
    }
};
