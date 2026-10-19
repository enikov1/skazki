<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryQuizQuestion extends Model
{
    protected $fillable = ['story_id', 'prompt', 'options', 'correct_option', 'position'];

    protected function casts(): array
    {
        return ['options' => 'array', 'correct_option' => 'integer'];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
