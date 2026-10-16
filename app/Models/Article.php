<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Article extends Model
{
    protected $fillable = ['title', 'slug', 'category', 'summary', 'body', 'reading_minutes', 'cover_path', 'status', 'seo_title', 'seo_description', 'created_by', 'updated_by', 'published_at'];

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
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function comments(): HasMany { return $this->hasMany(ArticleComment::class); }
    public function ratings(): HasMany { return $this->hasMany(ArticleRating::class); }
    public function slugRedirects(): MorphMany { return $this->morphMany(SlugRedirect::class, 'redirectable'); }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if ($field === 'slug') {
            return $this->newQuery()->where('slug', $value)->first()
                ?? $this->newQuery()->whereHas('slugRedirects', fn ($query) => $query->where('old_slug', $value))->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }
}
