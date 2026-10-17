<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audio_playback_states', function (Blueprint $table): void {
            $table->unsignedSmallInteger('queue_position')->nullable()->after('position_seconds');
            $table->index(['user_id', 'queue_position']);
        });
    }

    public function down(): void
    {
        Schema::table('audio_playback_states', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'queue_position']);
            $table->dropColumn('queue_position');
        });
    }
};
