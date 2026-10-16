<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('slug_redirects', function (Blueprint $table): void {
            $table->id();
            $table->morphs('redirectable');
            $table->string('old_slug', 200);
            $table->timestamps();
            $table->unique(['redirectable_type', 'old_slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
    }
};
