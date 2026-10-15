<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoryRating extends Model
{
    protected $fillable = ['user_id', 'rating'];

    public function story(): BelongsTo { return $this->belongsTo(Story::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
