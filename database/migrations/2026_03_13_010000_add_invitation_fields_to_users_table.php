<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
            $table->timestamp('activated_at')->nullable()->after('email_verified_at');
            $table->boolean('active')->default(true)->after('activated_at');
            $table->string('invitation_token', 64)->nullable()->unique()->after('active');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
            $table->dropColumn(['activated_at', 'active', 'invitation_token', 'invitation_sent_at']);
        });
    }
};
