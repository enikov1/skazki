<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
