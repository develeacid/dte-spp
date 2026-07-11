<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->text('definicion')->nullable()->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('indicadores', function (Blueprint $table) {
            $table->dropColumn('definicion');
        });
    }
};
