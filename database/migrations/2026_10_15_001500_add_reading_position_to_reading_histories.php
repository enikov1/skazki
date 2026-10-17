<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reading_histories', function (Blueprint $table): void {
            $table->unsignedInteger('paragraph_position')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('reading_histories', function (Blueprint $table): void {
            $table->dropColumn('paragraph_position');
        });
    }
};
