<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('llm_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 10); // global, team, user
            $table->unsignedBigInteger('scope_id')->nullable(); // team_id or user_id
            $table->date('month'); // first of month
            $table->decimal('budget_usd', 10, 2);
            $table->decimal('spent_usd', 10, 6)->default(0);
            $table->decimal('alert_threshold', 3, 2)->default(0.80);
            $table->timestamp('alerted_at')->nullable();
            $table->timestamps();

            $table->unique(['scope', 'scope_id', 'month']);
            $table->index('month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('llm_budgets');
    }
};
