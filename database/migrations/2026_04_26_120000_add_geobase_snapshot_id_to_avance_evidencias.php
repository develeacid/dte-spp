<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avance_evidencias', function (Blueprint $table) {
            $table->unsignedBigInteger('avance_id')->nullable()->change();
            $table->unsignedBigInteger('geobase_snapshot_id')->nullable()->after('hash_archivo');
            $table->index('geobase_snapshot_id');
        });
    }

    public function down(): void
    {
        Schema::table('avance_evidencias', function (Blueprint $table) {
            $table->dropIndex(['geobase_snapshot_id']);
            $table->dropColumn('geobase_snapshot_id');
            $table->unsignedBigInteger('avance_id')->nullable(false)->change();
        });
    }
};
