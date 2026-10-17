<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('password_login_enabled')->default(true);
        });

        DB::table('users')->whereNotNull('oauth_provider')->update(['password_login_enabled' => false]);

        Schema::create('social_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id', 191);
            $table->string('email')->nullable();
            $table->string('display_name', 120)->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_user_id']);
            $table->unique(['user_id', 'provider']);
        });

        DB::table('users')->whereNotNull('oauth_provider')->whereNotNull('oauth_id')->orderBy('id')->get()->each(function ($user): void {
            DB::table('social_accounts')->insertOrIgnore([
                'user_id' => $user->id,
                'provider' => $user->oauth_provider,
                'provider_user_id' => $user->oauth_id,
                'email' => $user->email,
                'display_name' => $user->name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('password_login_enabled');
        });
    }
};
