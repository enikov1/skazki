<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coloring_work_comments', function (Blueprint $table): void {
            $table->foreignId('coloring_submission_id')->nullable()->change();
            $table->foreignId('coloring_page_id')->nullable()->after('coloring_submission_id')->constrained('coloring_pages')->cascadeOnDelete();
            $table->index(['coloring_page_id', 'status']);
        });
    }

    public function down(): void
    {
        DB::table('coloring_work_comments')->whereNull('coloring_submission_id')->delete();
        Schema::table('coloring_work_comments', function (Blueprint $table): void {
            $table->dropIndex(['coloring_page_id', 'status']);
            $table->dropConstrainedForeignId('coloring_page_id');
            $table->foreignId('coloring_submission_id')->nullable(false)->change();
        });
    }
};
