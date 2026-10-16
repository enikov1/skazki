<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InteractiveGame extends Model
{
    protected $fillable = ['type', 'title', 'slug', 'description', 'image_a', 'image_b', 'data', 'is_published'];
    protected $casts = ['data' => 'array', 'is_published' => 'boolean'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return ['differences' => 'Найди отличия', 'mosaics' => 'Мозаики'][$this->type] ?? 'Игра';
    }
}
