<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('catalog_landing_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 24)->index();
            $table->string('title', 180);
            $table->string('slug', 180);
            $table->string('filter_value', 180)->nullable();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->text('description')->nullable();
            $table->string('cover_path', 255)->nullable();
            $table->string('seo_title', 180)->nullable();
            $table->string('seo_description', 320)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamps();
            $table->unique(['type', 'slug']);
            $table->unique(['type', 'filter_value']);
            $table->unique(['type', 'age_min', 'age_max']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_landing_pages');
    }
};
