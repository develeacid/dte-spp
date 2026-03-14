<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avances_financieros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partida_presupuestal_id')
                ->constrained('partidas_presupuestales')
                ->cascadeOnDelete();
            $table->smallInteger('trimestre');
            $table->decimal('monto_comprometido', 15, 2)->default(0);
            $table->decimal('monto_devengado', 15, 2)->default(0);
            $table->decimal('monto_pagado', 15, 2)->default(0);
            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->unique(['partida_presupuestal_id', 'trimestre']);
        });

        DB::statement('ALTER TABLE avances_financieros ADD CONSTRAINT chk_trimestre_valido CHECK (trimestre BETWEEN 1 AND 4)');
        DB::statement('ALTER TABLE avances_financieros ADD CONSTRAINT chk_pagado_le_devengado CHECK (monto_pagado <= monto_devengado)');
        DB::statement('ALTER TABLE avances_financieros ADD CONSTRAINT chk_devengado_le_comprometido CHECK (monto_devengado <= monto_comprometido)');
    }

    public function down(): void
    {
        Schema::dropIfExists('avances_financieros');
    }
};
