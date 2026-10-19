<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 180);
            $table->string('slug', 200)->unique();
            $table->string('category', 100)->index();
            $table->text('summary');
            $table->longText('body');
            $table->unsignedSmallInteger('reading_minutes')->default(5);
            $table->string('cover_path')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'published_at']);
        });

        Schema::create('article_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('likes_count')->default(0);
            $table->unsignedInteger('dislikes_count')->default(0);
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['article_id', 'status']);
        });

        Schema::create('article_comment_votes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_comment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('vote', 8);
            $table->timestamps();
            $table->unique(['article_comment_id', 'user_id']);
        });

        Schema::create('article_ratings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->timestamps();
            $table->unique(['article_id', 'user_id']);
            $table->index(['article_id', 'rating']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_comment_votes');
        Schema::dropIfExists('article_comments');
        Schema::dropIfExists('article_ratings');
        Schema::dropIfExists('articles');
    }
};
