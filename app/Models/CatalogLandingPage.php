<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CatalogLandingPage extends Model
{
    public const TYPES = ['story_genre', 'audio_genre', 'author', 'age', 'audio_age'];

    protected $fillable = ['type', 'title', 'slug', 'filter_value', 'age_min', 'age_max', 'description', 'cover_path', 'seo_title', 'seo_description', 'status'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    protected static function booted(): void
    {
        static::saving(function (CatalogLandingPage $page): void {
            if (blank($page->slug) && filled($page->title)) {
                $page->slug = Str::slug($page->title);
            }
        });
    }

    public function publicUrl(): string
    {
        return match ($this->type) {
            'story_genre' => route('catalog-pages.story-genre', $this->slug),
            'audio_genre' => route('catalog-pages.audio-genre', $this->slug),
            'author' => route('catalog-pages.author', $this->slug),
            'age' => route('catalog-pages.age', $this->slug),
            'audio_age' => route('catalog-pages.audio-age', $this->slug),
        };
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(CatalogLandingRedirect::class);
    }
}
