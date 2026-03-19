<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('indicador_variables', function (Blueprint $table) {
            $table->string('geobase_endpoint_type', 30)->nullable()->after('orden');
            $table->unsignedBigInteger('geobase_reference_id')->nullable()->after('geobase_endpoint_type');
            $table->json('geobase_filter_params')->nullable()->after('geobase_reference_id');
            $table->string('geobase_value_key', 30)->nullable()->after('geobase_filter_params');
        });
    }

    public function down(): void
    {
        Schema::table('indicador_variables', function (Blueprint $table) {
            $table->dropColumn([
                'geobase_endpoint_type',
                'geobase_reference_id',
                'geobase_filter_params',
                'geobase_value_key',
            ]);
        });
    }
};
