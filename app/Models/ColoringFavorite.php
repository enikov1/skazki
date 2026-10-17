<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColoringFavorite extends Model
{
    protected $fillable = ['user_id', 'coloring_page_id'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(ColoringPage::class, 'coloring_page_id');
    }
}
