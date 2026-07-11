<?php

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('involucrados', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProgramaPresupuestario::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->string('categoria');
            $table->string('nombre');
            $table->text('interes_o_rol')->nullable();
            $table->text('riesgo_asociado')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('involucrados');
    }
};
