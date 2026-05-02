<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('geobase_webhook_deliveries', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('delivery_id', 64);
            $table->string('event_type', 64);
            $table->boolean('signature_valid');
            $table->jsonb('payload');
            $table->smallInteger('status_code')->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique('delivery_id');
            $table->index(['event_type', 'created_at']);
        });

        DB::statement('CREATE INDEX geobase_webhook_deliveries_in_flight_idx ON geobase_webhook_deliveries (created_at) WHERE processed_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('geobase_webhook_deliveries');
    }
};
