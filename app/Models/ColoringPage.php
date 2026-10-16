<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColoringPage extends Model
{
    protected $fillable = ['category_id', 'title', 'slug', 'preview_path', 'outline_path', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ColoringCategory::class, 'category_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ColoringRating::class, 'coloring_page_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ColoringWorkComment::class, 'coloring_page_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ColoringFavorite::class, 'coloring_page_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ColoringProgress::class, 'coloring_page_id');
    }
}
