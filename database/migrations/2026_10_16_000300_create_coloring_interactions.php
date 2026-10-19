<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coloring_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coloring_page_id')->constrained('coloring_pages')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'coloring_page_id']);
        });
        Schema::create('coloring_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coloring_page_id')->constrained('coloring_pages')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->unique(['user_id', 'coloring_page_id']);
            $table->index(['coloring_page_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coloring_ratings');
        Schema::dropIfExists('coloring_favorites');
    }
};
