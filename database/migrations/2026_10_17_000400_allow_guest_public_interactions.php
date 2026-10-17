<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['comments', 'article_comments', 'coloring_work_comments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->change();
                $table->string('guest_name', 40)->nullable();
            });
        }

        foreach ([
            'comment_votes' => 'comment_id',
            'article_comment_votes' => 'article_comment_id',
        ] as $tableName => $parentColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $parentColumn): void {
                $table->dropUnique($tableName === 'comment_votes' ? 'comment_votes_comment_id_user_id_unique' : 'article_comment_votes_article_comment_id_user_id_unique');
                $table->foreignId('user_id')->nullable()->change();
                $table->string('guest_token', 64)->nullable();
                $table->unique([$parentColumn, 'guest_token'], $tableName.'_guest_unique');
                $table->unique([$parentColumn, 'user_id'], $tableName.'_user_unique');
            });
        }

        foreach ([
            'story_ratings' => 'story_id',
            'article_ratings' => 'article_id',
            'coloring_ratings' => 'coloring_page_id',
        ] as $tableName => $parentColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $parentColumn): void {
                $table->dropUnique($tableName === 'story_ratings' ? 'story_ratings_story_id_user_id_unique' : ($tableName === 'article_ratings' ? 'article_ratings_article_id_user_id_unique' : 'coloring_ratings_user_id_coloring_page_id_unique'));
                $table->foreignId('user_id')->nullable()->change();
                $table->string('guest_token', 64)->nullable();
                $table->unique([$parentColumn, 'guest_token'], $tableName.'_guest_unique');
                $table->unique([$parentColumn, 'user_id'], $tableName.'_user_unique');
            });
        }
    }

    public function down(): void
    {
        foreach (['comment_votes', 'article_comment_votes', 'story_ratings', 'article_ratings', 'coloring_ratings'] as $tableName) {
            \Illuminate\Support\Facades\DB::table($tableName)->whereNull('user_id')->delete();
        }
        foreach (['comments', 'article_comments', 'coloring_work_comments'] as $tableName) {
            \Illuminate\Support\Facades\DB::table($tableName)->whereNull('user_id')->delete();
        }

        foreach ([
            'comment_votes' => 'comment_id',
            'article_comment_votes' => 'article_comment_id',
        ] as $tableName => $parentColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $parentColumn): void {
                $table->dropUnique($tableName.'_guest_unique');
                $table->dropUnique($tableName.'_user_unique');
                $table->dropColumn('guest_token');
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->unique([$parentColumn, 'user_id']);
            });
        }

        foreach ([
            'story_ratings' => 'story_id',
            'article_ratings' => 'article_id',
            'coloring_ratings' => 'coloring_page_id',
        ] as $tableName => $parentColumn) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName, $parentColumn): void {
                $table->dropUnique($tableName.'_guest_unique');
                $table->dropUnique($tableName.'_user_unique');
                $table->dropColumn('guest_token');
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->unique([$parentColumn, 'user_id']);
            });
        }

        foreach (['comments', 'article_comments', 'coloring_work_comments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('guest_name');
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
            });
        }
    }
};
