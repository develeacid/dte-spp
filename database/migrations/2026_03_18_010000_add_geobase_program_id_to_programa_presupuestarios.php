<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->unsignedBigInteger('geobase_program_id')->nullable()->after('estado');
            $table->index('geobase_program_id');
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropIndex(['geobase_program_id']);
            $table->dropColumn('geobase_program_id');
        });
    }
};
