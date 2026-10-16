<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coloring_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('description', 500)->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });
        Schema::create('coloring_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->constrained('coloring_categories')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('slug', 180)->unique();
            $table->string('preview_path')->nullable();
            $table->string('outline_path');
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
            $table->index(['category_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coloring_pages');
        Schema::dropIfExists('coloring_categories');
    }
};
