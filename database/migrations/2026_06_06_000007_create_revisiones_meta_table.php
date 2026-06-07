<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meta_periodo_id')
                ->constrained('metas_periodo')
                ->cascadeOnDelete();
            $table->decimal('valor_anterior', 14, 4);
            $table->decimal('valor_nuevo', 14, 4);
            $table->text('justificacion');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones_meta');
    }
};
