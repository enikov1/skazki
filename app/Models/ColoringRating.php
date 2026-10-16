<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColoringRating extends Model
{
    protected $fillable = ['user_id', 'coloring_page_id', 'rating'];

    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(ColoringPage::class, 'coloring_page_id');
    }
}
