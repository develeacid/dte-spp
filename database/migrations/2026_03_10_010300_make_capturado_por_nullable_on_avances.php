<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avances', function (Blueprint $table) {
            $table->foreignId('capturado_por')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('avances', function (Blueprint $table) {
            $table->foreignId('capturado_por')->nullable(false)->change();
        });
    }
};
