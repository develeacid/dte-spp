<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Crear ENUM nativo de PostgreSQL primero (IF NOT EXISTS via DO block)
        DB::statement(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'tipo_programa_derivado') THEN
                    CREATE TYPE tipo_programa_derivado AS ENUM ('sectorial', 'especial', 'institucional', 'regional');
                END IF;
            END $$;
        SQL);

        // 2. Crear tabla
        Schema::create('programas_derivados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_plan_id')->constrained('ped_planes')->cascadeOnDelete();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            // Columna tipo usando ENUM nativo via raw SQL
            // Se agrega después para controlar el tipo exacto
        });

        // 3. Agregar columna tipo como ENUM nativo
        DB::statement(
            "ALTER TABLE programas_derivados ADD COLUMN tipo tipo_programa_derivado NOT NULL DEFAULT 'sectorial'"
        );

        // 4. Índice para búsquedas por tipo
        DB::statement(
            'CREATE INDEX programas_derivados_tipo_index ON programas_derivados (tipo)'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('programas_derivados');

        // IMPORTANTE: Eliminar ENUM después de eliminar la tabla
        DB::statement('DROP TYPE IF EXISTS tipo_programa_derivado');
    }
};
