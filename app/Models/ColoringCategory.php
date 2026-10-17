<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ColoringCategory extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'cover_path', 'is_published'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function pages(): HasMany
    {
        return $this->hasMany(ColoringPage::class, 'category_id');
    }
}
