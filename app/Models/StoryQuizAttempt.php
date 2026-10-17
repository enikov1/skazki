<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryQuizAttempt extends Model
{
    protected $fillable = ['story_id', 'user_id', 'score', 'question_count', 'answers'];

    protected function casts(): array
    {
        return ['answers' => 'array'];
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
