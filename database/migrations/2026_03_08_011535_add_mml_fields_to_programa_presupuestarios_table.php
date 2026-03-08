<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('id')->constrained('teams')->nullOnDelete();
            $table->unsignedSmallInteger('ejercicio_fiscal')->default(2026)->after('clave');
            $table->string('origen', 20)->default('nuevo')->after('ejercicio_fiscal');
            $table->string('estado', 20)->default('borrador')->after('origen');
            $table->foreignId('created_by')->nullable()->after('estado')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index(['team_id', 'ejercicio_fiscal']);
        });
    }

    public function down(): void
    {
        Schema::table('programa_presupuestarios', function (Blueprint $table) {
            $table->dropIndex(['team_id', 'ejercicio_fiscal']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['team_id']);
            $table->dropColumn(['team_id', 'ejercicio_fiscal', 'origen', 'estado', 'created_by', 'deleted_at']);
        });
    }
};
