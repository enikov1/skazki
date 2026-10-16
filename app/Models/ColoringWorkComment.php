<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColoringWorkComment extends Model
{
    protected $fillable = ['user_id', 'coloring_submission_id', 'coloring_page_id', 'body', 'status', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['moderated_at' => 'datetime'];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(ColoringSubmission::class, 'coloring_submission_id');
    }

    public function coloringPage(): BelongsTo
    {
        return $this->belongsTo(ColoringPage::class, 'coloring_page_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
