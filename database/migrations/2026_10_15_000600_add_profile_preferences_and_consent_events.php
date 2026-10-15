<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('avatar_path')->nullable();
            $table->json('profile_preferences')->nullable();
        });

        Schema::create('user_consent_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('consent_type', 40);
            $table->string('policy_version', 32)->nullable();
            $table->string('action', 16);
            $table->string('source', 32);
            $table->timestamp('recorded_at');
            $table->index(['user_id', 'consent_type', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consent_events');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['avatar_path', 'profile_preferences']);
        });
    }
};
