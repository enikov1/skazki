<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = ['provider', 'provider_user_id', 'email', 'display_name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
