<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColoringSubmission extends Model
{
    protected $fillable = ['user_id', 'coloring_page_id', 'title', 'slug', 'file_path', 'status', 'guardian_consent_at', 'moderated_by', 'moderated_at'];

    protected function casts(): array
    {
        return ['guardian_consent_at' => 'datetime', 'moderated_at' => 'datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(ColoringPage::class, 'coloring_page_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function likes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ColoringWorkLike::class, 'coloring_submission_id');
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ColoringWorkComment::class, 'coloring_submission_id');
    }
}
