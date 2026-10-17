<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentVote extends Model
{
    protected $fillable = ['comment_id', 'user_id', 'guest_token', 'vote'];
    public function comment(): BelongsTo { return $this->belongsTo(Comment::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
