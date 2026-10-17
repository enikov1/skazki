<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArticleComment extends Model
{
    protected $fillable = ['article_id', 'user_id', 'guest_name', 'body', 'status', 'likes_count', 'dislikes_count', 'moderated_by', 'moderated_at'];
    protected function casts(): array { return ['moderated_at' => 'datetime']; }
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function votes(): HasMany { return $this->hasMany(ArticleCommentVote::class); }
}
