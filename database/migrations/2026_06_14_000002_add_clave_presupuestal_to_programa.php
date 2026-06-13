<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            // Clave presupuestaria canónica SEFIP/CONAC, nivel programa. Todas nullable:
            // captura hacia adelante (las claves legacy tipo "ISM-001" no codifican estructura).
            // Bloque Administrativa (6 díg): Grupo (1) · UR (2) · UE (3).
            $table->smallInteger('grupo')->nullable()->after('clave');
            $table->smallInteger('unidad_responsable')->nullable()->after('grupo');
            $table->smallInteger('unidad_ejecutora')->nullable()->after('unidad_responsable');
            // Bloque Programática (11 díg): Programa (3) · Subprograma (2) · Proyecto (3) · Actividad (3).
            $table->smallInteger('programa_clave')->nullable()->after('unidad_ejecutora');
            $table->smallInteger('subprograma')->nullable()->after('programa_clave');
            $table->smallInteger('proyecto')->nullable()->after('subprograma');
            $table->smallInteger('actividad')->nullable()->after('proyecto');
            // Clasificación Funcional CONAC (¿para qué se gasta?).
            $table->foreignId('finalidad_id')->nullable()->after('actividad')->constrained('clasificacion_funcional')->nullOnDelete();
            $table->foreignId('funcion_id')->nullable()->after('finalidad_id')->constrained('clasificacion_funcional')->nullOnDelete();
            $table->foreignId('subfuncion_id')->nullable()->after('funcion_id')->constrained('clasificacion_funcional')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('finalidad_id');
            $table->dropConstrainedForeignId('funcion_id');
            $table->dropConstrainedForeignId('subfuncion_id');
            $table->dropColumn([
                'grupo', 'unidad_responsable', 'unidad_ejecutora',
                'programa_clave', 'subprograma', 'proyecto', 'actividad',
            ]);
        });
    }
};
