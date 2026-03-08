<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llm_logs', function (Blueprint $table) {
            $table->decimal('cost_usd', 10, 6)->nullable()->after('duration_ms');
            $table->string('prompt_version')->nullable()->after('prompt_template');
        });
    }

    public function down(): void
    {
        Schema::table('llm_logs', function (Blueprint $table) {
            $table->dropColumn(['cost_usd', 'prompt_version']);
        });
    }
};
