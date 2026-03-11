<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poblaciones_programa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programas_presupuestarios')->cascadeOnDelete();
            $table->string('unidad_medida', 100);
            $table->unsignedInteger('referencia_cantidad');
            $table->text('referencia_fuente')->nullable();
            $table->unsignedInteger('potencial_cantidad');
            $table->text('potencial_fuente')->nullable();
            $table->unsignedInteger('objetivo_cantidad');
            $table->text('objetivo_justificacion')->nullable();
            $table->smallInteger('anio_ejercicio');
            $table->timestamps();

            $table->unique(['programa_id', 'anio_ejercicio']);
        });

        DB::statement("
            ALTER TABLE poblaciones_programa
            ADD CONSTRAINT chk_embudo_logico CHECK (
                objetivo_cantidad <= potencial_cantidad AND
                potencial_cantidad <= referencia_cantidad AND
                referencia_cantidad > 0 AND
                potencial_cantidad > 0 AND
                objetivo_cantidad > 0
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('poblaciones_programa');
    }
};
