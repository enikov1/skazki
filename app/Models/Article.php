<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'summary', 'body', 'reading_minutes', 'cover_path', 'status', 'seo_title', 'seo_description', 'created_by', 'published_at'];

    protected function casts(): array { return ['published_at' => 'datetime']; }

    protected static function booted(): void
    {
        static::saving(function (Article $article): void {
            if (blank($article->slug) && filled($article->title)) $article->slug = Str::slug($article->title);
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function comments(): HasMany { return $this->hasMany(ArticleComment::class); }
    public function ratings(): HasMany { return $this->hasMany(ArticleRating::class); }
}
