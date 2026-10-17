<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InteractiveGame extends Model
{
    protected $fillable = ['type', 'title', 'slug', 'description', 'image_a', 'image_b', 'data', 'is_published', 'created_by', 'updated_by'];
    protected $casts = ['data' => 'array', 'is_published' => 'boolean'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getTypeLabelAttribute(): string
    {
        return ['differences' => 'Найди отличия', 'mosaics' => 'Мозаики'][$this->type] ?? 'Игра';
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
