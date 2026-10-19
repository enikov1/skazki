<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('genres', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->timestamps();
        });

        DB::table('stories')->whereNotNull('genre')->distinct()->orderBy('genre')->pluck('genre')->each(function (string $name): void {
            DB::table('genres')->insertOrIgnore(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('genres');
    }
};
