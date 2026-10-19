<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArticleRating extends Model
{
    protected $fillable = ['user_id', 'guest_token', 'rating'];
    public function article(): BelongsTo { return $this->belongsTo(Article::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
