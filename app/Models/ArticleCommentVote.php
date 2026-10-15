<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleCommentVote extends Model
{
    protected $fillable = ['user_id', 'vote'];
    public function comment(): BelongsTo { return $this->belongsTo(ArticleComment::class, 'article_comment_id'); }
}
