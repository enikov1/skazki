<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    protected $fillable = ['story_id', 'user_id', 'guest_name', 'body', 'status', 'likes_count', 'dislikes_count', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['moderated_at' => 'datetime'];
    }

    public function story(): BelongsTo { return $this->belongsTo(Story::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function votes(): HasMany { return $this->hasMany(CommentVote::class); }
}
