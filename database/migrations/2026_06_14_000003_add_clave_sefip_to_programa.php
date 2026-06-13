<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            // Clave presupuestaria SEFIP completa (32 caracteres) tal cual está en uso.
            // Fuente de verdad de los programas con clave ya establecida; preserva los
            // bloques Objeto del Gasto y Financiamiento que no tienen campo discreto.
            // string(64) da margen sobre los 32 y tolera separadores antes de limpiar.
            $table->string('clave_sefip', 64)->nullable()->after('subfuncion_id');
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropColumn('clave_sefip');
        });
    }
};
