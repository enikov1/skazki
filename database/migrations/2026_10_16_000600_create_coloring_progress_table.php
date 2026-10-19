<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coloring_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coloring_page_id')->constrained('coloring_pages')->cascadeOnDelete();
            $table->json('strokes');
            $table->timestamps();
            $table->unique(['user_id', 'coloring_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coloring_progress');
    }
};
