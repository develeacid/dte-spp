<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->boolean('requiere_rop')->default(false)
                ->comment('Programa entrega subsidios/apoyos → requiere ROP publicadas');
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropColumn('requiere_rop');
        });
    }
};
