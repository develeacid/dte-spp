<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mir_niveles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('tipo_nivel', 20);
            $table->foreignId('componente_id')
                ->nullable()->constrained('mir_niveles')->cascadeOnDelete();
            $table->text('resumen_narrativo')->nullable();
            $table->text('supuestos')->nullable();
            $table->foreignId('arbol_nodo_id')
                ->nullable()->constrained('arbol_nodos')->nullOnDelete();
            $table->smallInteger('orden')->default(0);

            // FKs de alineación con cascada de planes
            $table->foreignId('ped_objetivo_estrategico_id')
                ->nullable()->constrained('ped_objetivos_estrategicos')->nullOnDelete();
            $table->foreignId('programa_derivado_objetivo_id')
                ->nullable()->constrained('programas_derivados_objetivos')->nullOnDelete();
            $table->foreignId('ped_linea_accion_id')
                ->nullable()->constrained('ped_lineas_accion')->nullOnDelete();

            // UR Coadyuvante
            $table->foreignId('team_id')
                ->nullable()->constrained('teams')->nullOnDelete();

            $table->timestamps();

            $table->index(['programa_presupuestario_id', 'tipo_nivel']);
            $table->index('componente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mir_niveles');
    }
};
