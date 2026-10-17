<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryFavorite extends Model
{
    protected $fillable = ['story_id'];

    public function story(): BelongsTo { return $this->belongsTo(Story::class); }
}
