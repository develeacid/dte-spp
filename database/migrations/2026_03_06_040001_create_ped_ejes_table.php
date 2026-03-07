<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ped_ejes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ped_plan_id')->constrained('ped_planes')->cascadeOnDelete();
            $table->string('numero', 10); // "1", "2", "3"
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->unique(['ped_plan_id', 'numero']);
        });

        DB::statement('ALTER TABLE ped_ejes ADD COLUMN embedding vector(1536)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ped_ejes');
    }
};
