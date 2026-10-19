<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stories', function (Blueprint $table): void {
            $table->id(); $table->string('title', 180); $table->string('slug', 200)->unique(); $table->string('author', 160)->nullable(); $table->string('genre', 80)->index(); $table->text('summary'); $table->longText('body'); $table->unsignedTinyInteger('age_min')->default(3); $table->unsignedTinyInteger('age_max')->default(8); $table->unsignedSmallInteger('reading_minutes')->default(5); $table->string('cover_path')->nullable(); $table->string('audio_path')->nullable(); $table->string('status', 20)->default('draft')->index(); $table->string('seo_title', 180)->nullable(); $table->string('seo_description', 320)->nullable(); $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('published_at')->nullable(); $table->timestamps(); $table->index(['status', 'published_at']);
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id(); $table->foreignId('story_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->text('body'); $table->string('status', 20)->default('pending')->index(); $table->unsignedInteger('likes_count')->default(0); $table->unsignedInteger('dislikes_count')->default(0); $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete(); $table->timestamp('moderated_at')->nullable(); $table->timestamps(); $table->index(['story_id', 'status']);
        });
        Schema::create('comment_votes', function (Blueprint $table): void { $table->id(); $table->foreignId('comment_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->constrained()->cascadeOnDelete(); $table->string('vote', 8); $table->timestamps(); $table->unique(['comment_id', 'user_id']); $table->index(['comment_id', 'vote']); });
    }

    public function down(): void { Schema::dropIfExists('comment_votes'); Schema::dropIfExists('comments'); Schema::dropIfExists('stories'); }
};
