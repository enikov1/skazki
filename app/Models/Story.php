<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Story extends Model
{
    protected $fillable = ['title', 'slug', 'author', 'series_name', 'source_name', 'source_url', 'rights_holder', 'rights_note', 'tags', 'genre', 'summary', 'body', 'age_min', 'age_max', 'reading_minutes', 'cover_path', 'audio_path', 'allow_download', 'status', 'seo_title', 'seo_description', 'created_by', 'updated_by', 'published_at'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'allow_download' => 'boolean', 'tags' => 'array'];
    }

    protected static function booted(): void
    {
        static::saving(function (Story $story): void {
            if (blank($story->slug) && filled($story->title)) {
                $story->slug = Str::slug($story->title);
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function readingHistories(): HasMany
    {
        return $this->hasMany(ReadingHistory::class);
    }

    public function audioPlaybackStates(): HasMany
    {
        return $this->hasMany(AudioPlaybackState::class);
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(StoryRating::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(StoryQuizQuestion::class);
    }
}
