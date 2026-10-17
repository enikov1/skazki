<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Genre extends Model
{
    protected $fillable = ['name'];

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class, 'genre', 'name');
    }
}
