<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('interactive_games', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 24)->index();
            $table->string('title', 180);
            $table->string('slug', 190)->unique();
            $table->text('description')->nullable();
            $table->string('image_a')->nullable();
            $table->string('image_b')->nullable();
            $table->json('data');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('interactive_games'); }
};
