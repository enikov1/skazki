<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('coloring_submissions', function (Blueprint $table): void {
            $table->string('slug', 180)->nullable()->unique();
        });
        DB::table('coloring_submissions')->orderBy('id')->get()->each(function (object $submission): void {
            DB::table('coloring_submissions')->where('id', $submission->id)->update(['slug' => Str::slug($submission->title).'-'.$submission->id]);
        });

        Schema::create('coloring_work_likes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coloring_submission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'coloring_submission_id']);
        });
        Schema::create('coloring_work_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coloring_submission_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 16)->default('pending')->index();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();
            $table->index(['coloring_submission_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coloring_work_comments');
        Schema::dropIfExists('coloring_work_likes');
        Schema::table('coloring_submissions', fn (Blueprint $table) => $table->dropUnique(['slug']));
        Schema::table('coloring_submissions', fn (Blueprint $table) => $table->dropColumn('slug'));
    }
};
