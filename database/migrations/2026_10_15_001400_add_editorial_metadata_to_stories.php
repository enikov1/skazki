<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->string('series_name', 160)->nullable();
            $table->string('source_name', 180)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->string('rights_holder', 180)->nullable();
            $table->text('rights_note')->nullable();
            $table->json('tags')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table): void {
            $table->dropColumn(['series_name', 'source_name', 'source_url', 'rights_holder', 'rights_note', 'tags']);
        });
    }
};
