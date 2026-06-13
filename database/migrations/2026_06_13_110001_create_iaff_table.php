<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iaff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programa_id')->constrained('programa_presupuestarios')->cascadeOnDelete();
            $table->integer('ejercicio_fiscal');
            $table->smallInteger('trimestre');
            $table->jsonb('snapshot_payload');
            $table->string('hash_sha256', 64);
            $table->timestamp('generado_en');
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('firmado_en')->nullable();
            $table->foreignId('firmado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['programa_id', 'ejercicio_fiscal', 'trimestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iaff');
    }
};
