<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SlugRedirect extends Model
{
    protected $fillable = ['old_slug'];

    public function redirectable(): MorphTo
    {
        return $this->morphTo();
    }
}
