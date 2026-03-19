<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avance_variables', function (Blueprint $table) {
            $table->boolean('synced_from_geobase')->default(false)->after('valor_acumulado');
            $table->timestamp('synced_at')->nullable()->after('synced_from_geobase');
        });
    }

    public function down(): void
    {
        Schema::table('avance_variables', function (Blueprint $table) {
            $table->dropColumn(['synced_from_geobase', 'synced_at']);
        });
    }
};
