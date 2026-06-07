<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->decimal('rango_rojo_alto_min', 8, 2)->nullable()->after('rango_rojo_max');
            $table->decimal('rango_rojo_alto_max', 8, 2)->nullable()->after('rango_rojo_alto_min');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->dropColumn(['rango_rojo_alto_min', 'rango_rojo_alto_max']);
        });
    }
};
