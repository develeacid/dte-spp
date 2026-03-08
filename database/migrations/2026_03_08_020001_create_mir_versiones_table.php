<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mir_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_presupuestario_id')
                ->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->string('etiqueta', 100);
            $table->jsonb('snapshot');
            $table->foreignId('created_by')
                ->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('programa_presupuestario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mir_versiones');
    }
};
