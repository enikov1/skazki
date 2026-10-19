<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AudioPlaybackState extends Model
{
    public $timestamps = false;

    protected $fillable = ['story_id', 'position_seconds', 'queue_position', 'updated_at'];

    protected function casts(): array
    {
        return ['updated_at' => 'datetime', 'position_seconds' => 'integer', 'queue_position' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }
}
