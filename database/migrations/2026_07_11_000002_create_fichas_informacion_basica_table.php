<?php

use App\Models\ProgramaPresupuestario;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fichas_informacion_basica', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProgramaPresupuestario::class)
                ->constrained()
                ->cascadeOnDelete()
                ->unique();
            $table->text('magnitud')->nullable();
            $table->text('focalizacion')->nullable();
            $table->text('causas_efectos')->nullable();
            $table->text('bienes_servicios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fichas_informacion_basica');
    }
};
