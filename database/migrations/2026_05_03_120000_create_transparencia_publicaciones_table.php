<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transparencia_publicaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dataset_abierto_id')->constrained('datasets_abiertos')->cascadeOnDelete();
            $table->string('dataset_clave', 50);
            $table->string('action', 20);
            $table->boolean('success');
            $table->string('payload_hash', 64)->nullable();
            $table->integer('registros_count')->nullable();
            $table->foreignId('publicado_por_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('error_message')->nullable();
            $table->timestamp('publicado_at');
            $table->timestamps();

            $table->index(['dataset_clave', 'publicado_at'], 'transparencia_publicaciones_clave_at_idx');
            $table->index('dataset_abierto_id', 'transparencia_publicaciones_dataset_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transparencia_publicaciones');
    }
};
